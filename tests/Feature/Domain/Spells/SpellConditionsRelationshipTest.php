<?php

declare(strict_types=1);

use App\Domain\Spells\Enums\Condition;
use App\Domain\Spells\Models\Spell;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

covers(Spell::class);

test('spell can have multiple conditions', function (): void {
    $spell = Spell::factory()->create();

    DB::table('spell_conditions')->insert([
        ['spell_id' => $spell->id, 'condition' => Condition::Charmed->value],
        ['spell_id' => $spell->id, 'condition' => Condition::Frightened->value],
    ]);

    expect($spell->conditions()->count())->toBe(2);
})->group('mysql');

test('condition value is stored correctly', function (): void {
    $spell = Spell::factory()->create();

    DB::table('spell_conditions')->insert([
        'spell_id' => $spell->id,
        'condition' => Condition::Paralyzed->value,
    ]);

    $this->assertDatabaseHas('spell_conditions', [
        'spell_id' => $spell->id,
        'condition' => 'paralyzed',
    ]);
})->group('mysql');

test('deleting a spell cascades to spell conditions', function (): void {
    $spell = Spell::factory()->create();

    DB::table('spell_conditions')->insert([
        'spell_id' => $spell->id,
        'condition' => Condition::Stunned->value,
    ]);

    $spellId = $spell->id;
    $spell->delete();

    $this->assertDatabaseMissing('spell_conditions', ['spell_id' => $spellId]);
})->group('mysql');
