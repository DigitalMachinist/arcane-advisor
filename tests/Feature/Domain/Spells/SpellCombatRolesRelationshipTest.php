<?php

declare(strict_types=1);

use App\Domain\Spells\Enums\CombatRole;
use App\Domain\Spells\Models\Spell;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

covers(Spell::class);

test('spell can have multiple combat roles', function (): void {
    $spell = Spell::factory()->create();

    DB::table('spell_combat_roles')->insert([
        ['spell_id' => $spell->id, 'role' => CombatRole::AreaDamage->value],
        ['spell_id' => $spell->id, 'role' => CombatRole::Control->value],
    ]);

    expect($spell->combatRoles()->count())->toBe(2);
})->group('mysql');

test('combat role camelCase value is stored correctly', function (): void {
    $spell = Spell::factory()->create();

    DB::table('spell_combat_roles')->insert([
        'spell_id' => $spell->id,
        'role' => CombatRole::SingleTargetDamage->value,
    ]);

    $this->assertDatabaseHas('spell_combat_roles', [
        'spell_id' => $spell->id,
        'role' => 'singleTargetDamage',
    ]);
})->group('mysql');

test('deleting a spell cascades to spell combat roles', function (): void {
    $spell = Spell::factory()->create();

    DB::table('spell_combat_roles')->insert([
        'spell_id' => $spell->id,
        'role' => CombatRole::Buff->value,
    ]);

    $spellId = $spell->id;
    $spell->delete();

    $this->assertDatabaseMissing('spell_combat_roles', ['spell_id' => $spellId]);
})->group('mysql');
