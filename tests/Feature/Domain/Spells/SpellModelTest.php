<?php

declare(strict_types=1);

use App\Domain\Spells\Enums\ActionEconomy;
use App\Domain\Spells\Enums\DurationCategory;
use App\Domain\Spells\Enums\School;
use App\Domain\Spells\Enums\Targeting;
use App\Domain\Spells\Models\Spell;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

covers(Spell::class);

test('factory creates a valid spell', function (): void {
    $spell = Spell::factory()->create();

    expect($spell)->toBeInstanceOf(Spell::class)
        ->and($spell->exists)->toBeTrue()
        ->and($spell->slug)->toBeString()
        ->and($spell->name)->toBeString()
        ->and($spell->level)->toBeInt();
})->group('mysql');

test('timestamps populate on create', function (): void {
    $spell = Spell::factory()->create();

    expect($spell->created_at)->not->toBeNull()
        ->and($spell->updated_at)->not->toBeNull();
})->group('mysql');

test('school enum cast round-trips correctly', function (): void {
    $spell = Spell::factory()->create(['school' => School::Evocation]);

    $fresh = Spell::find($spell->id);

    expect($fresh->school)->toBe(School::Evocation);
})->group('mysql');

test('targeting enum cast round-trips correctly', function (): void {
    $spell = Spell::factory()->create(['targeting' => Targeting::Area]);

    $fresh = Spell::find($spell->id);

    expect($fresh->targeting)->toBe(Targeting::Area);
})->group('mysql');

test('action economy enum cast round-trips correctly', function (): void {
    $spell = Spell::factory()->create(['action_economy' => ActionEconomy::BonusAction]);

    $fresh = Spell::find($spell->id);

    expect($fresh->action_economy)->toBe(ActionEconomy::BonusAction);
})->group('mysql');

test('duration category enum cast round-trips correctly', function (): void {
    $spell = Spell::factory()->create(['duration_category' => DurationCategory::Timed]);

    $fresh = Spell::find($spell->id);

    expect($fresh->duration_category)->toBe(DurationCategory::Timed);
})->group('mysql');

test('slug is unique', function (): void {
    Spell::factory()->create(['slug' => 'fireball']);

    expect(fn () => Spell::factory()->create(['slug' => 'fireball']))
        ->toThrow(\Illuminate\Database\QueryException::class);
})->group('mysql');

test('nullable fields accept null', function (): void {
    $spell = Spell::factory()->create([
        'component_material' => null,
        'area_shape' => null,
        'area_size' => null,
        'attack_roll' => null,
        'embedding' => null,
    ]);

    $fresh = Spell::find($spell->id);

    expect($fresh->component_material)->toBeNull()
        ->and($fresh->area_shape)->toBeNull()
        ->and($fresh->area_size)->toBeNull()
        ->and($fresh->attack_roll)->toBeNull()
        ->and($fresh->embedding)->toBeNull();
})->group('mysql');
