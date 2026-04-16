<?php

declare(strict_types=1);

use App\Domain\Spells\Enums\OutOfCombatUtility;
use App\Domain\Spells\Models\Spell;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

covers(Spell::class);

test('spell can have multiple utility tags', function (): void {
    $spell = Spell::factory()->create();

    DB::table('spell_utilities')->insert([
        ['spell_id' => $spell->id, 'utility' => OutOfCombatUtility::Explore->value],
        ['spell_id' => $spell->id, 'utility' => OutOfCombatUtility::Learn->value],
    ]);

    expect($spell->utilities()->count())->toBe(2);
})->group('mysql');

test('utility value is stored correctly', function (): void {
    $spell = Spell::factory()->create();

    DB::table('spell_utilities')->insert([
        'spell_id' => $spell->id,
        'utility' => OutOfCombatUtility::Communicate->value,
    ]);

    $this->assertDatabaseHas('spell_utilities', [
        'spell_id' => $spell->id,
        'utility' => 'communicate',
    ]);
})->group('mysql');

test('deleting a spell cascades to spell utilities', function (): void {
    $spell = Spell::factory()->create();

    DB::table('spell_utilities')->insert([
        'spell_id' => $spell->id,
        'utility' => OutOfCombatUtility::Ward->value,
    ]);

    $spellId = $spell->id;
    $spell->delete();

    $this->assertDatabaseMissing('spell_utilities', ['spell_id' => $spellId]);
})->group('mysql');
