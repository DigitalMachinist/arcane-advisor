<?php

declare(strict_types=1);

namespace App\Domain\Spells\Actions;

use App\Domain\Llm\LlmClient;
use App\Domain\Spells\Enums\AreaShape;
use App\Domain\Spells\Enums\AttackRoll;
use App\Domain\Spells\Enums\CombatRole;
use App\Domain\Spells\Enums\Condition;
use App\Domain\Spells\Enums\DamageType;
use App\Domain\Spells\Enums\OutOfCombatUtility;
use App\Domain\Spells\Enums\Qualifier;
use App\Domain\Spells\Enums\Targeting;
use RuntimeException;

class SpellsExtractAction
{
    public function __construct(
        private readonly LlmClient $llmClient,
    ) {}

    /**
     * Extract structured fields from a raw scraped spell array via LLM.
     *
     * @param  array<string, mixed>  $rawSpell
     * @return array<string, mixed>
     */
    public function execute(array $rawSpell): array
    {
        $prompt = $this->renderPrompt($rawSpell);
        $response = $this->llmClient->complete($prompt);
        $extracted = $this->parseResponse($response->text);
        $this->validateExtracted($extracted);

        return array_merge($rawSpell, $extracted);
    }

    /**
     * Render the prompt template with values from a raw spell array.
     *
     * @param  array<string, mixed>  $rawSpell
     */
    public function renderPrompt(array $rawSpell): string
    {
        $template = $this->loadTemplate();

        return strtr($template, [
            '{{ name }}' => (string) ($rawSpell['name'] ?? ''),
            '{{ description }}' => (string) ($rawSpell['rawDescription'] ?? ''),
            '{{ castingTime }}' => (string) ($rawSpell['castingTime'] ?? ''),
            '{{ range }}' => (string) ($rawSpell['range'] ?? ''),
            '{{ duration }}' => (string) ($rawSpell['duration'] ?? ''),
        ]);
    }

    private function loadTemplate(): string
    {
        $path = base_path('resources/prompts/spell-extraction.txt');

        if (! file_exists($path)) {
            throw new RuntimeException("Prompt template not found: '{$path}'");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Could not read prompt template: '{$path}'");
        }

        return $contents;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseResponse(string $responseText): array
    {
        // Strip any markdown code fences the LLM may have wrapped the JSON in.
        $cleaned = preg_replace('/^```(?:json)?\s*/m', '', $responseText);
        $cleaned = preg_replace('/\s*```\s*$/m', '', (string) $cleaned);
        $cleaned = trim((string) $cleaned);

        $data = json_decode($cleaned, associative: true);

        if (! is_array($data)) {
            throw new RuntimeException('LLM response is not valid JSON: '.$responseText);
        }

        return $data;
    }

    /**
     * Validate that extracted values are within the controlled vocabularies.
     *
     * @param  array<string, mixed>  $extracted
     */
    private function validateExtracted(array $extracted): void
    {
        $this->validateEnum('targeting', $extracted, Targeting::class);
        $this->validateNullableEnum('areaShape', $extracted, AreaShape::class);
        $this->validateNullableEnum('attackRoll', $extracted, AttackRoll::class);
        $this->validateEnumArray('combatRoles', $extracted, CombatRole::class);
        $this->validateEnumArray('conditions', $extracted, Condition::class);
        $this->validateEnumArray('utilities', $extracted, OutOfCombatUtility::class);
        $this->validateEnumArray('qualifiers', $extracted, Qualifier::class);
        $this->validateDamageEntries($extracted);
    }

    /**
     * @param  class-string  $enumClass
     * @param  array<string, mixed>  $data
     */
    private function validateEnum(string $key, array $data, string $enumClass): void
    {
        if (! isset($data[$key]) || ! is_string($data[$key])) {
            throw new RuntimeException("Missing or invalid field '{$key}' in LLM response");
        }

        if ($enumClass::tryFrom($data[$key]) === null) {
            throw new RuntimeException("Invalid value '{$data[$key]}' for field '{$key}'");
        }
    }

    /**
     * @param  class-string  $enumClass
     * @param  array<string, mixed>  $data
     */
    private function validateNullableEnum(string $key, array $data, string $enumClass): void
    {
        if (! array_key_exists($key, $data) || $data[$key] === null) {
            return;
        }

        if (! is_string($data[$key])) {
            throw new RuntimeException("Field '{$key}' must be a string or null");
        }

        if ($enumClass::tryFrom($data[$key]) === null) {
            throw new RuntimeException("Invalid value '{$data[$key]}' for field '{$key}'");
        }
    }

    /**
     * @param  class-string  $enumClass
     * @param  array<string, mixed>  $data
     */
    private function validateEnumArray(string $key, array $data, string $enumClass): void
    {
        if (! isset($data[$key]) || ! is_array($data[$key])) {
            throw new RuntimeException("Field '{$key}' must be an array in LLM response");
        }

        foreach ($data[$key] as $value) {
            if (! is_string($value) || $enumClass::tryFrom($value) === null) {
                throw new RuntimeException("Invalid value '{$value}' in array field '{$key}'");
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateDamageEntries(array $data): void
    {
        if (! isset($data['damage']) || ! is_array($data['damage'])) {
            throw new RuntimeException("Field 'damage' must be an array in LLM response");
        }

        foreach ($data['damage'] as $index => $entry) {
            if (! is_array($entry)) {
                throw new RuntimeException("Damage entry [{$index}] must be an object");
            }

            if (! isset($entry['dice']) || ! is_string($entry['dice'])) {
                throw new RuntimeException("Damage entry [{$index}] missing 'dice' string");
            }

            if (! isset($entry['type']) || ! is_string($entry['type'])) {
                throw new RuntimeException("Damage entry [{$index}] missing 'type' string");
            }

            if (DamageType::tryFrom($entry['type']) === null) {
                throw new RuntimeException("Invalid damage type '{$entry['type']}' in damage entry [{$index}]");
            }
        }
    }
}
