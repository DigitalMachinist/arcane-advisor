<?php

declare(strict_types=1);

namespace App\Domain\Spells;

use App\Domain\Spells\Data\SpellData;
use App\Domain\Spells\Enums\AbilityScore;
use App\Domain\Spells\Enums\AreaShape;
use App\Domain\Spells\Enums\AttackRoll;
use App\Domain\Spells\Enums\CombatRole;
use App\Domain\Spells\Enums\Condition;
use App\Domain\Spells\Enums\DamageType;
use App\Domain\Spells\Enums\OutOfCombatUtility;
use App\Domain\Spells\Enums\Qualifier;
use App\Domain\Spells\Enums\School;
use App\Domain\Spells\Enums\SourceClass;
use App\Domain\Spells\Enums\SourceCode;
use App\Domain\Spells\Enums\Targeting;
use App\Domain\Spells\Validation\SpellYamlSchema;
use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class YamlLoader
{
    public function __construct(
        private readonly SpellYamlSchema $schema = new SpellYamlSchema,
    ) {}

    public function load(string $path): SpellData
    {
        if (! file_exists($path)) {
            throw new RuntimeException("Spell YAML file not found: '{$path}'");
        }

        $result = $this->schema->validateFile($path);

        if (! $result->isValid()) {
            $summary = implode('; ', $result->errors());
            throw new RuntimeException("Spell YAML validation failed: {$summary}");
        }

        $raw = file_get_contents($path);

        if ($raw === false) {
            throw new RuntimeException("Could not read spell YAML file: '{$path}'");
        }

        try {
            $data = Yaml::parse($this->extractFrontmatter($raw));
        } catch (ParseException $e) {
            throw new RuntimeException("Failed to parse spell YAML: {$e->getMessage()}", previous: $e);
        }

        if (! is_array($data)) {
            throw new RuntimeException('Spell YAML must contain a mapping');
        }

        return $this->hydrate($data);
    }

    private function extractFrontmatter(string $raw): string
    {
        $matched = preg_match('/^---\s*$/m', $raw, matches: $matches, offset: 1, flags: PREG_OFFSET_CAPTURE);

        if ($matched !== 1) {
            return $raw;
        }

        return substr($raw, 0, (int) $matches[0][1]);
    }

    /** @param array<mixed> $data */
    private function hydrate(array $data): SpellData
    {
        /** @var array{verbal: bool, somatic: bool, material: string|null} $components */
        $components = $data['components'];

        /** @var array{ability: string}|null $rawSavingThrow */
        $rawSavingThrow = $data['savingThrow'];

        $savingThrow = $rawSavingThrow !== null
            ? ['ability' => AbilityScore::from($rawSavingThrow['ability'])]
            : null;

        return new SpellData(
            slug: $data['slug'],
            name: $data['name'],
            level: $data['level'],
            school: School::from($data['school']),
            castingTime: $data['castingTime'],
            range: $data['range'],
            componentVerbal: $components['verbal'],
            componentSomatic: $components['somatic'],
            componentMaterial: $components['material'],
            duration: $data['duration'],
            qualifiers: array_map(fn (string $v) => Qualifier::from($v), $data['qualifiers']),
            classes: array_map(fn (string $v) => SourceClass::from($v), $data['classes']),
            damage: array_map(fn (array $d) => ['dice' => $d['dice'], 'type' => DamageType::from($d['type'])], $data['damage']),
            conditions: array_map(fn (string $v) => Condition::from($v), $data['conditions']),
            targeting: Targeting::from($data['targeting']),
            areaShape: $data['areaShape'] !== null ? AreaShape::from($data['areaShape']) : null,
            areaSize: $data['areaSize'],
            savingThrow: $savingThrow,
            attackRoll: $data['attackRoll'] !== null ? AttackRoll::from($data['attackRoll']) : null,
            combatRoles: array_map(fn (string $v) => CombatRole::from($v), $data['combatRoles']),
            utilities: array_map(fn (string $v) => OutOfCombatUtility::from($v), $data['utilities']),
            sources: array_map(fn (array $s) => ['code' => SourceCode::from($s['code']), 'page' => $s['page']], $data['sources']),
            personalityBlurb: $data['personalityBlurb'],
        );
    }
}
