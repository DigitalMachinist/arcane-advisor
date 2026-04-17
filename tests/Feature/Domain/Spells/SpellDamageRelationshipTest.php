<?php

declare(strict_types=1);

use App\Domain\Spells\Enums\DamageType;
use App\Domain\Spells\Models\Spell;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

covers(Spell::class);

test('spell can have multiple damage entries', function (): void {
    $spell = Spell::factory()->create();

    DB::table('spell_damage')->insert([
        ['spell_id' => $spell->id, 'dice' => '8d6', 'type' => DamageType::Fire->value],
        ['spell_id' => $spell->id, 'dice' => '2d4', 'type' => DamageType::Cold->value],
    ]);

    expect($spell->damage()->count())->toBe(2);
})->group('mysql');

test('damage entry stores dice and type correctly', function (): void {
    $spell = Spell::factory()->create();

    DB::table('spell_damage')->insert([
        'spell_id' => $spell->id,
        'dice' => '6d6',
        'type' => DamageType::Lightning->value,
    ]);

    $entry = $spell->damage()->first();

    expect($entry->dice)->toBe('6d6')
        ->and($entry->type)->toBe(DamageType::Lightning);
})->group('mysql');

test('deleting a spell cascades to spell damage', function (): void {
    $spell = Spell::factory()->create();

    DB::table('spell_damage')->insert([
        'spell_id' => $spell->id,
        'dice' => '8d6',
        'type' => DamageType::Fire->value,
    ]);

    $spellId = $spell->id;
    $spell->delete();

    $this->assertDatabaseMissing('spell_damage', ['spell_id' => $spellId]);
})->group('mysql');
