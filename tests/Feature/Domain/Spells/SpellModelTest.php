<?php

declare(strict_types=1);

use App\Casts\UnixMillisecondsCast;
use App\Domain\Spells\Enums\ActionEconomy;
use App\Domain\Spells\Enums\DurationCategory;
use App\Domain\Spells\Enums\School;
use App\Domain\Spells\Enums\Targeting;
use App\Domain\Spells\Models\Spell;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

covers(Spell::class, UnixMillisecondsCast::class);

test('factory creates a valid spell', function (): void {
    $spell = Spell::factory()->create();

    expect($spell)->toBeInstanceOf(Spell::class)
        ->and($spell->exists)->toBeTrue()
        ->and($spell->slug)->toBeString()
        ->and($spell->name)->toBeString()
        ->and($spell->level)->toBeInt();
})->group('mysql');

test('uuid is auto-generated on create and is unique', function (): void {
    $a = Spell::factory()->create();
    $b = Spell::factory()->create();

    expect($a->uuid)->toBeString()->toMatch('/^[0-9a-f-]{36}$/')
        ->and($b->uuid)->toBeString()
        ->and($a->uuid)->not->toBe($b->uuid);
})->group('mysql');

test('uuid column has a unique index', function (): void {
    $spell = Spell::factory()->create();

    expect(fn () => Spell::factory()->create(['uuid' => $spell->uuid]))
        ->toThrow(QueryException::class);
})->group('mysql');

test('ms timestamps populate on create and cast to Carbon', function (): void {
    $before = now()->valueOf();
    $spell = Spell::factory()->create();
    $after = now()->valueOf();

    $fresh = Spell::find($spell->id);

    expect($fresh->created_at_ms)->toBeInstanceOf(Carbon::class)
        ->and($fresh->updated_at_ms)->toBeInstanceOf(Carbon::class)
        ->and($fresh->created_at_ms->valueOf())->toBeGreaterThanOrEqual($before)
        ->and($fresh->created_at_ms->valueOf())->toBeLessThanOrEqual($after);
})->group('mysql');

test('updated_at_ms advances on update', function (): void {
    $spell = Spell::factory()->create();
    $createdMs = $spell->created_at_ms->valueOf();

    usleep(2000);
    $spell->update(['name' => 'Renamed Spell']);

    $fresh = Spell::find($spell->id);

    expect($fresh->updated_at_ms->valueOf())->toBeGreaterThan($createdMs);
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
        ->toThrow(QueryException::class);
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
