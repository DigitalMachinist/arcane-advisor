<?php

declare(strict_types=1);

use App\Domain\Spells\Enums\Targeting;
use App\Domain\Spells\Models\Spell;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

covers(Spell::class);

test('targeting enum round-trips on the spell itself', function (): void {
    $spell = Spell::factory()->create(['targeting' => Targeting::Creature]);

    $fresh = Spell::find($spell->id);

    expect($fresh->targeting)->toBe(Targeting::Creature)
        ->and($fresh->targeting)->toBeInstanceOf(Targeting::class);
})->group('mysql');

test('targeting self round-trips correctly', function (): void {
    $spell = Spell::factory()->create(['targeting' => Targeting::Self]);

    $fresh = Spell::find($spell->id);

    expect($fresh->targeting)->toBe(Targeting::Self);
})->group('mysql');

test('targeting stores camelCase value in the database', function (): void {
    $spell = Spell::factory()->create(['targeting' => Targeting::Creatures]);

    $this->assertDatabaseHas('spells', [
        'id' => $spell->id,
        'targeting' => 'creatures',
    ]);
})->group('mysql');
