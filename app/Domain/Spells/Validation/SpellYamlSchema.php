<?php

declare(strict_types=1);

namespace App\Domain\Spells\Validation;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class SpellYamlSchema
{
    private const array REQUIRED_KEYS = [
        'slug',
        'name',
        'level',
        'school',
        'castingTime',
        'range',
        'components',
        'duration',
        'qualifiers',
        'classes',
        'damage',
        'conditions',
        'targeting',
        'areaShape',
        'areaSize',
        'savingThrow',
        'attackRoll',
        'combatRoles',
        'utilities',
        'sources',
        'personalityBlurb',
    ];

    private const array SCHOOLS = [
        'abjuration',
        'conjuration',
        'divination',
        'enchantment',
        'evocation',
        'illusion',
        'necromancy',
        'transmutation',
    ];

    private const array SOURCE_CODES = ['phb', 'xge', 'tce', 'scag', 'ftd', 'aag'];

    private const array SOURCE_CLASSES = [
        'wizard',
        'sorcerer',
        'cleric',
        'druid',
        'bard',
        'paladin',
        'ranger',
        'warlock',
        'artificer',
    ];

    private const array QUALIFIERS = ['concentration', 'ritual'];

    private const array DAMAGE_TYPES = [
        'acid',
        'bludgeoning',
        'cold',
        'fire',
        'force',
        'lightning',
        'necrotic',
        'piercing',
        'poison',
        'psychic',
        'radiant',
        'slashing',
        'thunder',
    ];

    private const array CONDITIONS = [
        'blinded',
        'charmed',
        'deafened',
        'exhaustion',
        'frightened',
        'grappled',
        'incapacitated',
        'invisible',
        'paralyzed',
        'petrified',
        'poisoned',
        'prone',
        'restrained',
        'stunned',
        'unconscious',
    ];

    private const array TARGETING = ['point', 'self', 'creature', 'creatures', 'area', 'touch'];

    private const array AREA_SHAPES = ['sphere', 'cube', 'cone', 'line', 'cylinder', 'wall'];

    private const array ABILITY_SCORES = [
        'strength',
        'dexterity',
        'constitution',
        'intelligence',
        'wisdom',
        'charisma',
    ];

    private const array ATTACK_ROLLS = ['melee', 'ranged'];

    private const array COMBAT_ROLES = [
        'areaDamage',
        'singleTargetDamage',
        'sustainedDamage',
        'control',
        'debuff',
        'hinder',
        'buff',
        'expedite',
        'defend',
        'heal',
        'move',
        'escape',
        'summon',
        'counter',
        'transform',
        'obfuscate',
        'deceive',
        'sense',
        'alert',
        'communicate',
    ];

    private const array UTILITIES = [
        'explore',
        'influence',
        'deceive',
        'obfuscate',
        'communicate',
        'travel',
        'learn',
        'create',
        'shape',
        'heal',
        'ward',
    ];

    public function validateFile(string $path): ValidationResult
    {
        $raw = file_get_contents($path);

        if ($raw === false) {
            return ValidationResult::fail(["Could not read YAML file: '{$path}'"]);
        }

        // Split frontmatter from markdown body on the first `---` separator line.
        $frontmatter = $this->extractFrontmatter($raw);

        try {
            $data = Yaml::parse($frontmatter);
        } catch (ParseException $e) {
            return ValidationResult::fail(["Could not parse YAML file: '{$path}': {$e->getMessage()}"]);
        }

        if (! is_array($data)) {
            return ValidationResult::fail(['YAML file must contain a mapping, not a scalar']);
        }

        return $this->validate($data);
    }

    /** @param array<mixed> $data */
    public function validate(array $data): ValidationResult
    {
        $errors = [
            ...$this->checkRequiredKeys($data),
            ...$this->checkStringFields($data),
            ...$this->checkIntFields($data),
            ...$this->checkEnumFields($data),
            ...$this->checkArrayFields($data),
            ...$this->checkComponents($data),
            ...$this->checkDamageEntries($data),
            ...$this->checkSources($data),
            ...$this->checkSavingThrow($data),
        ];

        return $errors === []
            ? ValidationResult::ok()
            : ValidationResult::fail($errors);
    }

    private function extractFrontmatter(string $raw): string
    {
        // The optional `---` after the frontmatter marks the start of the markdown body.
        // Everything before the first standalone `---` separator (or the whole file) is frontmatter.
        $matched = preg_match('/^---\s*$/m', $raw, matches: $matches, offset: 1, flags: PREG_OFFSET_CAPTURE);

        if ($matched !== 1) {
            return $raw;
        }

        return substr($raw, 0, (int) $matches[0][1]);
    }

    /**
     * @param  array<mixed>  $data
     * @return list<string>
     */
    private function checkRequiredKeys(array $data): array
    {
        $errors = [];

        foreach (self::REQUIRED_KEYS as $key) {
            if (! array_key_exists($key, $data)) {
                $errors[] = "Missing required field: '{$key}'";
            }
        }

        return $errors;
    }

    /**
     * @param  array<mixed>  $data
     * @return list<string>
     */
    private function checkStringFields(array $data): array
    {
        $errors = [];

        foreach (['slug', 'name', 'castingTime', 'range', 'duration', 'personalityBlurb'] as $key) {
            if (array_key_exists($key, $data) && ! is_string($data[$key])) {
                $errors[] = "Field '{$key}' must be a string";
            }
        }

        return $errors;
    }

    /**
     * @param  array<mixed>  $data
     * @return list<string>
     */
    private function checkIntFields(array $data): array
    {
        if (array_key_exists('level', $data) && ! is_int($data['level'])) {
            return ["Field 'level' must be an integer"];
        }

        return [];
    }

    /**
     * @param  array<mixed>  $data
     * @return list<string>
     */
    private function checkEnumFields(array $data): array
    {
        return [
            ...$this->checkEnumField('school', $data, self::SCHOOLS),
            ...$this->checkEnumField('targeting', $data, self::TARGETING),
            ...$this->checkNullableEnumField('areaShape', $data, self::AREA_SHAPES),
            ...$this->checkNullableEnumField('attackRoll', $data, self::ATTACK_ROLLS),
            ...$this->checkEnumArray('qualifiers', $data, self::QUALIFIERS),
            ...$this->checkEnumArray('classes', $data, self::SOURCE_CLASSES),
            ...$this->checkEnumArray('conditions', $data, self::CONDITIONS),
            ...$this->checkEnumArray('combatRoles', $data, self::COMBAT_ROLES),
            ...$this->checkEnumArray('utilities', $data, self::UTILITIES),
        ];
    }

    /**
     * @param  array<mixed>  $data
     * @param  list<string>  $allowedValues
     * @return list<string>
     */
    private function checkEnumField(string $key, array $data, array $allowedValues): array
    {
        if (! array_key_exists($key, $data) || ! is_string($data[$key])) {
            return [];
        }

        if (! in_array($data[$key], $allowedValues, strict: true)) {
            return ["Invalid value '{$data[$key]}' for field '{$key}'"];
        }

        return [];
    }

    /**
     * @param  array<mixed>  $data
     * @param  list<string>  $allowedValues
     * @return list<string>
     */
    private function checkNullableEnumField(string $key, array $data, array $allowedValues): array
    {
        if (! array_key_exists($key, $data) || $data[$key] === null) {
            return [];
        }

        if (! is_string($data[$key])) {
            return ["Field '{$key}' must be a string or null"];
        }

        if (! in_array($data[$key], $allowedValues, strict: true)) {
            return ["Invalid value '{$data[$key]}' for field '{$key}'"];
        }

        return [];
    }

    /**
     * @param  array<mixed>  $data
     * @param  list<string>  $allowedValues
     * @return list<string>
     */
    private function checkEnumArray(string $key, array $data, array $allowedValues): array
    {
        if (! array_key_exists($key, $data) || ! is_array($data[$key])) {
            return [];
        }

        $errors = [];

        foreach ($data[$key] as $value) {
            if (! in_array($value, $allowedValues, strict: true)) {
                $errors[] = "Invalid value '{$value}' in array field '{$key}'";
            }
        }

        return $errors;
    }

    /**
     * @param  array<mixed>  $data
     * @return list<string>
     */
    private function checkArrayFields(array $data): array
    {
        $errors = [];

        foreach (['qualifiers', 'classes', 'damage', 'conditions', 'combatRoles', 'utilities', 'sources'] as $key) {
            if (array_key_exists($key, $data) && ! is_array($data[$key])) {
                $errors[] = "Field '{$key}' must be an array";
            }
        }

        return $errors;
    }

    /**
     * @param  array<mixed>  $data
     * @return list<string>
     */
    private function checkComponents(array $data): array
    {
        if (! array_key_exists('components', $data)) {
            return [];
        }

        if (! is_array($data['components'])) {
            return ["Field 'components' must be a mapping"];
        }

        $components = $data['components'];
        $errors = [];

        foreach (['verbal', 'somatic'] as $boolKey) {
            if (! array_key_exists($boolKey, $components)) {
                $errors[] = "Missing required field: 'components.{$boolKey}'";

                continue;
            }

            if (! is_bool($components[$boolKey])) {
                $errors[] = "Field 'components.{$boolKey}' must be a boolean";
            }
        }

        if (array_key_exists('material', $components) && $components['material'] !== null && ! is_string($components['material'])) {
            $errors[] = "Field 'components.material' must be a string or null";
        }

        return $errors;
    }

    /**
     * @param  array<mixed>  $data
     * @return list<string>
     */
    private function checkDamageEntries(array $data): array
    {
        if (! array_key_exists('damage', $data) || ! is_array($data['damage'])) {
            return [];
        }

        $errors = [];

        foreach ($data['damage'] as $index => $entry) {
            if (! is_array($entry)) {
                $errors[] = "Field 'damage[{$index}]' must be a mapping";

                continue;
            }

            if (! array_key_exists('dice', $entry) || ! is_string($entry['dice'])) {
                $errors[] = "Field 'damage[{$index}].dice' must be a string";
            }

            if (! array_key_exists('type', $entry)) {
                $errors[] = "Field 'damage[{$index}].type' is required";

                continue;
            }

            if (! is_string($entry['type'])) {
                $errors[] = "Field 'damage[{$index}].type' must be a string";

                continue;
            }

            if (! in_array($entry['type'], self::DAMAGE_TYPES, strict: true)) {
                $errors[] = "Invalid value '{$entry['type']}' for field 'damage[{$index}].type'";
            }
        }

        return $errors;
    }

    /**
     * @param  array<mixed>  $data
     * @return list<string>
     */
    private function checkSources(array $data): array
    {
        if (! array_key_exists('sources', $data) || ! is_array($data['sources'])) {
            return [];
        }

        $errors = [];

        foreach ($data['sources'] as $index => $entry) {
            if (! is_array($entry)) {
                $errors[] = "Field 'sources[{$index}]' must be a mapping";

                continue;
            }

            if (! array_key_exists('code', $entry) || ! is_string($entry['code'])) {
                $errors[] = "Field 'sources[{$index}].code' must be a string";
            } elseif (! in_array($entry['code'], self::SOURCE_CODES, strict: true)) {
                $errors[] = "Invalid value '{$entry['code']}' for field 'sources[{$index}].code'";
            }

            if (! array_key_exists('page', $entry)) {
                $errors[] = "Field 'sources[{$index}].page' is required";
            } elseif (! is_int($entry['page'])) {
                $errors[] = "Field 'sources[{$index}].page' must be an integer";
            }
        }

        return $errors;
    }

    /**
     * @param  array<mixed>  $data
     * @return list<string>
     */
    private function checkSavingThrow(array $data): array
    {
        if (! array_key_exists('savingThrow', $data) || $data['savingThrow'] === null) {
            return [];
        }

        if (! is_array($data['savingThrow'])) {
            return ["Field 'savingThrow' must be a mapping or null"];
        }

        $savingThrow = $data['savingThrow'];

        if (! array_key_exists('ability', $savingThrow)) {
            return ["Missing required field: 'savingThrow.ability'"];
        }

        if (! is_string($savingThrow['ability'])) {
            return ["Field 'savingThrow.ability' must be a string"];
        }

        if (! in_array($savingThrow['ability'], self::ABILITY_SCORES, strict: true)) {
            return ["Invalid value '{$savingThrow['ability']}' for field 'savingThrow.ability'"];
        }

        return [];
    }
}
