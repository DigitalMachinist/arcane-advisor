<?php

declare(strict_types=1);

use App\Domain\Spells\Enums\AbilityScore;
use App\Domain\Spells\Enums\ActionEconomy;
use App\Domain\Spells\Enums\AreaShape;
use App\Domain\Spells\Enums\AttackRoll;
use App\Domain\Spells\Enums\CombatRole;
use App\Domain\Spells\Enums\Condition;
use App\Domain\Spells\Enums\DamageType;
use App\Domain\Spells\Enums\DurationCategory;
use App\Domain\Spells\Enums\OutOfCombatUtility;
use App\Domain\Spells\Enums\Qualifier;
use App\Domain\Spells\Enums\School;
use App\Domain\Spells\Enums\SourceClass;
use App\Domain\Spells\Enums\SourceCode;
use App\Domain\Spells\Enums\Targeting;

// School
test('School has 8 cases', function (): void {
    expect(School::cases())->toHaveCount(8);
});

test('School contains known values', function (): void {
    expect(School::from('abjuration'))->toBe(School::Abjuration)
        ->and(School::from('evocation'))->toBe(School::Evocation)
        ->and(School::from('necromancy'))->toBe(School::Necromancy);
});

test('School::from() rejects unknown values', function (): void {
    expect(fn () => School::from('unknown'))->toThrow(ValueError::class);
});

test('School::tryFrom() returns null for unknown values', function (): void {
    expect(School::tryFrom('unknown'))->toBeNull();
});

// SourceCode
test('SourceCode has 6 cases', function (): void {
    expect(SourceCode::cases())->toHaveCount(6);
});

test('SourceCode contains known values', function (): void {
    expect(SourceCode::from('phb'))->toBe(SourceCode::PlayersHandbook)
        ->and(SourceCode::from('xge'))->toBe(SourceCode::XanatharsGuideToEverything)
        ->and(SourceCode::from('tce'))->toBe(SourceCode::TashasCauldronOfEverything);
});

test('SourceCode::from() rejects unknown values', function (): void {
    expect(fn () => SourceCode::from('unknown'))->toThrow(ValueError::class);
});

test('SourceCode::tryFrom() returns null for unknown values', function (): void {
    expect(SourceCode::tryFrom('unknown'))->toBeNull();
});

// SourceClass
test('SourceClass has 9 cases', function (): void {
    expect(SourceClass::cases())->toHaveCount(9);
});

test('SourceClass contains known values', function (): void {
    expect(SourceClass::from('wizard'))->toBe(SourceClass::Wizard)
        ->and(SourceClass::from('cleric'))->toBe(SourceClass::Cleric)
        ->and(SourceClass::from('artificer'))->toBe(SourceClass::Artificer);
});

test('SourceClass::from() rejects unknown values', function (): void {
    expect(fn () => SourceClass::from('unknown'))->toThrow(ValueError::class);
});

test('SourceClass::tryFrom() returns null for unknown values', function (): void {
    expect(SourceClass::tryFrom('unknown'))->toBeNull();
});

// Qualifier
test('Qualifier has 2 cases', function (): void {
    expect(Qualifier::cases())->toHaveCount(2);
});

test('Qualifier contains known values', function (): void {
    expect(Qualifier::from('concentration'))->toBe(Qualifier::Concentration)
        ->and(Qualifier::from('ritual'))->toBe(Qualifier::Ritual);
});

test('Qualifier::from() rejects unknown values', function (): void {
    expect(fn () => Qualifier::from('unknown'))->toThrow(ValueError::class);
});

test('Qualifier::tryFrom() returns null for unknown values', function (): void {
    expect(Qualifier::tryFrom('unknown'))->toBeNull();
});

// DamageType
test('DamageType has 13 cases', function (): void {
    expect(DamageType::cases())->toHaveCount(13);
});

test('DamageType contains known values', function (): void {
    expect(DamageType::from('fire'))->toBe(DamageType::Fire)
        ->and(DamageType::from('psychic'))->toBe(DamageType::Psychic)
        ->and(DamageType::from('thunder'))->toBe(DamageType::Thunder);
});

test('DamageType::from() rejects unknown values', function (): void {
    expect(fn () => DamageType::from('unknown'))->toThrow(ValueError::class);
});

test('DamageType::tryFrom() returns null for unknown values', function (): void {
    expect(DamageType::tryFrom('unknown'))->toBeNull();
});

// Condition
test('Condition has 15 cases', function (): void {
    expect(Condition::cases())->toHaveCount(15);
});

test('Condition contains known values', function (): void {
    expect(Condition::from('blinded'))->toBe(Condition::Blinded)
        ->and(Condition::from('paralyzed'))->toBe(Condition::Paralyzed)
        ->and(Condition::from('unconscious'))->toBe(Condition::Unconscious);
});

test('Condition::from() rejects unknown values', function (): void {
    expect(fn () => Condition::from('unknown'))->toThrow(ValueError::class);
});

test('Condition::tryFrom() returns null for unknown values', function (): void {
    expect(Condition::tryFrom('unknown'))->toBeNull();
});

// Targeting
test('Targeting has 6 cases', function (): void {
    expect(Targeting::cases())->toHaveCount(6);
});

test('Targeting contains known values', function (): void {
    expect(Targeting::from('point'))->toBe(Targeting::Point)
        ->and(Targeting::from('self'))->toBe(Targeting::Self)
        ->and(Targeting::from('touch'))->toBe(Targeting::Touch);
});

test('Targeting::from() rejects unknown values', function (): void {
    expect(fn () => Targeting::from('unknown'))->toThrow(ValueError::class);
});

test('Targeting::tryFrom() returns null for unknown values', function (): void {
    expect(Targeting::tryFrom('unknown'))->toBeNull();
});

// AreaShape
test('AreaShape has 6 cases', function (): void {
    expect(AreaShape::cases())->toHaveCount(6);
});

test('AreaShape contains known values', function (): void {
    expect(AreaShape::from('sphere'))->toBe(AreaShape::Sphere)
        ->and(AreaShape::from('cone'))->toBe(AreaShape::Cone)
        ->and(AreaShape::from('wall'))->toBe(AreaShape::Wall);
});

test('AreaShape::from() rejects unknown values', function (): void {
    expect(fn () => AreaShape::from('unknown'))->toThrow(ValueError::class);
});

test('AreaShape::tryFrom() returns null for unknown values', function (): void {
    expect(AreaShape::tryFrom('unknown'))->toBeNull();
});

// AbilityScore
test('AbilityScore has 6 cases', function (): void {
    expect(AbilityScore::cases())->toHaveCount(6);
});

test('AbilityScore contains known values', function (): void {
    expect(AbilityScore::from('strength'))->toBe(AbilityScore::Strength)
        ->and(AbilityScore::from('intelligence'))->toBe(AbilityScore::Intelligence)
        ->and(AbilityScore::from('charisma'))->toBe(AbilityScore::Charisma);
});

test('AbilityScore::from() rejects unknown values', function (): void {
    expect(fn () => AbilityScore::from('unknown'))->toThrow(ValueError::class);
});

test('AbilityScore::tryFrom() returns null for unknown values', function (): void {
    expect(AbilityScore::tryFrom('unknown'))->toBeNull();
});

// AttackRoll
test('AttackRoll has 2 cases', function (): void {
    expect(AttackRoll::cases())->toHaveCount(2);
});

test('AttackRoll contains known values', function (): void {
    expect(AttackRoll::from('melee'))->toBe(AttackRoll::Melee)
        ->and(AttackRoll::from('ranged'))->toBe(AttackRoll::Ranged);
});

test('AttackRoll::from() rejects unknown values', function (): void {
    expect(fn () => AttackRoll::from('unknown'))->toThrow(ValueError::class);
});

test('AttackRoll::tryFrom() returns null for unknown values', function (): void {
    expect(AttackRoll::tryFrom('unknown'))->toBeNull();
});

// ActionEconomy
test('ActionEconomy has 7 cases', function (): void {
    expect(ActionEconomy::cases())->toHaveCount(7);
});

test('ActionEconomy contains known values', function (): void {
    expect(ActionEconomy::from('action'))->toBe(ActionEconomy::Action)
        ->and(ActionEconomy::from('bonusAction'))->toBe(ActionEconomy::BonusAction)
        ->and(ActionEconomy::from('reaction'))->toBe(ActionEconomy::Reaction);
});

test('ActionEconomy::from() rejects unknown values', function (): void {
    expect(fn () => ActionEconomy::from('unknown'))->toThrow(ValueError::class);
});

test('ActionEconomy::tryFrom() returns null for unknown values', function (): void {
    expect(ActionEconomy::tryFrom('unknown'))->toBeNull();
});

// DurationCategory
test('DurationCategory has 4 cases', function (): void {
    expect(DurationCategory::cases())->toHaveCount(4);
});

test('DurationCategory contains known values', function (): void {
    expect(DurationCategory::from('instantaneous'))->toBe(DurationCategory::Instantaneous)
        ->and(DurationCategory::from('timed'))->toBe(DurationCategory::Timed)
        ->and(DurationCategory::from('permanent'))->toBe(DurationCategory::Permanent);
});

test('DurationCategory::from() rejects unknown values', function (): void {
    expect(fn () => DurationCategory::from('unknown'))->toThrow(ValueError::class);
});

test('DurationCategory::tryFrom() returns null for unknown values', function (): void {
    expect(DurationCategory::tryFrom('unknown'))->toBeNull();
});

// CombatRole
test('CombatRole has 20 cases', function (): void {
    expect(CombatRole::cases())->toHaveCount(20);
});

test('CombatRole contains known values', function (): void {
    expect(CombatRole::from('areaDamage'))->toBe(CombatRole::AreaDamage)
        ->and(CombatRole::from('control'))->toBe(CombatRole::Control)
        ->and(CombatRole::from('summon'))->toBe(CombatRole::Summon);
});

test('CombatRole::from() rejects unknown values', function (): void {
    expect(fn () => CombatRole::from('unknown'))->toThrow(ValueError::class);
});

test('CombatRole::tryFrom() returns null for unknown values', function (): void {
    expect(CombatRole::tryFrom('unknown'))->toBeNull();
});

// OutOfCombatUtility
test('OutOfCombatUtility has 11 cases', function (): void {
    expect(OutOfCombatUtility::cases())->toHaveCount(11);
});

test('OutOfCombatUtility contains known values', function (): void {
    expect(OutOfCombatUtility::from('explore'))->toBe(OutOfCombatUtility::Explore)
        ->and(OutOfCombatUtility::from('influence'))->toBe(OutOfCombatUtility::Influence)
        ->and(OutOfCombatUtility::from('ward'))->toBe(OutOfCombatUtility::Ward);
});

test('OutOfCombatUtility::from() rejects unknown values', function (): void {
    expect(fn () => OutOfCombatUtility::from('unknown'))->toThrow(ValueError::class);
});

test('OutOfCombatUtility::tryFrom() returns null for unknown values', function (): void {
    expect(OutOfCombatUtility::tryFrom('unknown'))->toBeNull();
});
