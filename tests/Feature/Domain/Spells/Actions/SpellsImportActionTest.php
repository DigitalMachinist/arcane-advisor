<?php

declare(strict_types=1);

use App\Domain\Spells\Actions\SpellsImportAction;
use App\Domain\Spells\Enums\ActionEconomy;
use App\Domain\Spells\Enums\DurationCategory;
use App\Domain\Spells\Models\Spell;
use App\Domain\Spells\Models\SpellClass;
use App\Domain\Spells\Models\SpellCondition;
use App\Domain\Spells\Models\SpellCombatRole;
use App\Domain\Spells\Models\SpellDamage;
use App\Domain\Spells\Models\SpellQualifier;
use App\Domain\Spells\Models\SpellSource;
use App\Domain\Spells\Models\SpellUtility;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(SpellsImportAction::class);

uses(RefreshDatabase::class);

test('import from fixture YAML directory populates spells table', function (): void {
    $action = new SpellsImportAction;
    $count = $action->execute(database_path('spells'));

    expect($count)->toBe(3);
    expect(Spell::count())->toBe(3);
    expect(Spell::where('slug', 'fireball')->exists())->toBeTrue();
    expect(Spell::where('slug', 'mage-hand')->exists())->toBeTrue();
    expect(Spell::where('slug', 'alarm')->exists())->toBeTrue();
})->group('mysql');

test('import populates child tables', function (): void {
    $action = new SpellsImportAction;
    $action->execute(database_path('spells'));

    $fireball = Spell::where('slug', 'fireball')->firstOrFail();

    expect(SpellDamage::where('spell_id', $fireball->id)->count())->toBe(1);
    expect(SpellSource::where('spell_id', $fireball->id)->count())->toBe(1);
    expect(SpellClass::where('spell_id', $fireball->id)->count())->toBeGreaterThanOrEqual(1);
})->group('mysql');

test('import populates action_economy from CastingTimeParser', function (): void {
    $action = new SpellsImportAction;
    $action->execute(database_path('spells'));

    $fireball = Spell::where('slug', 'fireball')->firstOrFail();
    expect($fireball->action_economy)->toBe(ActionEconomy::Action);
})->group('mysql');

test('import populates duration_category from DurationParser', function (): void {
    $action = new SpellsImportAction;
    $action->execute(database_path('spells'));

    $fireball = Spell::where('slug', 'fireball')->firstOrFail();
    expect($fireball->duration_category)->toBe(DurationCategory::Instantaneous);
})->group('mysql');

test('import populates duration_category as Timed for concentration spell', function (): void {
    $action = new SpellsImportAction;
    $action->execute(database_path('spells'));

    // alarm has duration "8 hours" which is timed
    $alarm = Spell::where('slug', 'alarm')->firstOrFail();
    expect($alarm->duration_category)->toBe(DurationCategory::Timed);
})->group('mysql');

test('re-running import is idempotent - no duplicate spells', function (): void {
    $action = new SpellsImportAction;
    $action->execute(database_path('spells'));
    $action->execute(database_path('spells'));

    expect(Spell::count())->toBe(3);
})->group('mysql');

test('re-running import is idempotent - no duplicate child rows', function (): void {
    $action = new SpellsImportAction;
    $action->execute(database_path('spells'));
    $action->execute(database_path('spells'));

    $fireball = Spell::where('slug', 'fireball')->firstOrFail();
    expect(SpellDamage::where('spell_id', $fireball->id)->count())->toBe(1);
})->group('mysql');

test('invalid YAML aborts the whole import transactionally', function (): void {
    // Create a temporary directory with one valid and one invalid YAML file
    $tempDir = sys_get_temp_dir().'/spell_import_test_'.uniqid();
    mkdir($tempDir);

    // Copy a valid fixture
    copy(database_path('spells/fireball.yaml'), $tempDir.'/fireball.yaml');

    // Write an invalid YAML file
    file_put_contents($tempDir.'/invalid-spell.yaml', "slug: invalid-spell\nname: Invalid\n# missing required fields");

    $action = new SpellsImportAction;

    expect(fn () => $action->execute($tempDir))
        ->toThrow(RuntimeException::class);

    // No spells should have been persisted
    expect(Spell::count())->toBe(0);

    // Cleanup
    array_map('unlink', glob($tempDir.'/*') ?: []);
    rmdir($tempDir);
})->group('mysql');
