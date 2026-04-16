<?php

declare(strict_types=1);

use App\Domain\Spells\Enums\ActionEconomy;
use App\Domain\Spells\Models\Spell;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

covers(Spell::class);

test('action economy enum round-trips correctly', function (): void {
    $spell = Spell::factory()->create(['action_economy' => ActionEconomy::Action]);

    $fresh = Spell::find($spell->id);

    expect($fresh->action_economy)->toBe(ActionEconomy::Action)
        ->and($fresh->action_economy)->toBeInstanceOf(ActionEconomy::class);
})->group('mysql');

test('bonus action economy round-trips correctly', function (): void {
    $spell = Spell::factory()->create(['action_economy' => ActionEconomy::BonusAction]);

    $fresh = Spell::find($spell->id);

    expect($fresh->action_economy)->toBe(ActionEconomy::BonusAction);
})->group('mysql');

test('action economy stores camelCase value in the database', function (): void {
    $spell = Spell::factory()->create(['action_economy' => ActionEconomy::TenMinutes]);

    $this->assertDatabaseHas('spells', [
        'id' => $spell->id,
        'action_economy' => 'tenMinutes',
    ]);
})->group('mysql');
