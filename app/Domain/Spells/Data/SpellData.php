<?php

declare(strict_types=1);

namespace App\Domain\Spells\Data;

final readonly class SpellData
{
    /**
     * @param  list<string>  $qualifiers
     * @param  list<string>  $classes
     * @param  list<array{dice: string, type: string}>  $damage
     * @param  list<string>  $conditions
     * @param  list<string>  $combatRoles
     * @param  list<string>  $utilities
     * @param  list<array{code: string, page: int}>  $sources
     * @param  array{ability: string}|null  $savingThrow
     */
    public function __construct(
        public string $slug,
        public string $name,
        public int $level,
        public string $school,
        public string $castingTime,
        public string $range,
        public bool $componentVerbal,
        public bool $componentSomatic,
        public ?string $componentMaterial,
        public string $duration,
        public array $qualifiers,
        public array $classes,
        public array $damage,
        public array $conditions,
        public string $targeting,
        public ?string $areaShape,
        public ?string $areaSize,
        public ?array $savingThrow,
        public ?string $attackRoll,
        public array $combatRoles,
        public array $utilities,
        public array $sources,
        public string $personalityBlurb,
    ) {}
}
