<?php

declare(strict_types=1);

namespace App\Domain\Spells\Data;

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

final readonly class SpellData
{
    /**
     * @param  list<Qualifier>  $qualifiers
     * @param  list<SourceClass>  $classes
     * @param  list<array{dice: string, type: DamageType}>  $damage
     * @param  list<Condition>  $conditions
     * @param  list<CombatRole>  $combatRoles
     * @param  list<OutOfCombatUtility>  $utilities
     * @param  list<array{code: SourceCode, page: int}>  $sources
     * @param  array{ability: AbilityScore}|null  $savingThrow
     */
    public function __construct(
        public string $slug,
        public string $name,
        public int $level,
        public School $school,
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
        public Targeting $targeting,
        public ?AreaShape $areaShape,
        public ?string $areaSize,
        public ?array $savingThrow,
        public ?AttackRoll $attackRoll,
        public array $combatRoles,
        public array $utilities,
        public array $sources,
        public string $personalityBlurb,
    ) {}
}
