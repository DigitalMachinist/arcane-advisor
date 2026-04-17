<?php

declare(strict_types=1);

use App\Domain\Spells\Enums\SourceClass;
use App\Domain\Spells\Models\Spell;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

covers(Spell::class);

test('spell can be associated with multiple classes', function (): void {
    // Factory inserts wizard; add a second class.
    $spell = Spell::factory()->create();

    DB::table('spell_classes')->insert([
        'spell_id' => $spell->id,
        'class' => SourceClass::Sorcerer->value,
    ]);

    expect($spell->classes()->count())->toBe(2);
})->group('mysql');

test('class can be detached from spell', function (): void {
    // Factory inserts wizard; add bard, then detach bard.
    $spell = Spell::factory()->create();

    DB::table('spell_classes')->insert([
        'spell_id' => $spell->id,
        'class' => SourceClass::Bard->value,
    ]);

    DB::table('spell_classes')
        ->where('spell_id', $spell->id)
        ->where('class', SourceClass::Bard->value)
        ->delete();

    expect($spell->classes()->count())->toBe(1);
    $this->assertDatabaseHas('spell_classes', [
        'spell_id' => $spell->id,
        'class' => SourceClass::Wizard->value,
    ]);
    $this->assertDatabaseMissing('spell_classes', [
        'spell_id' => $spell->id,
        'class' => SourceClass::Bard->value,
    ]);
})->group('mysql');

test('deleting a spell cascades to spell classes', function (): void {
    $spell = Spell::factory()->create();
    $spellId = $spell->id;

    $spell->delete();

    $this->assertDatabaseMissing('spell_classes', ['spell_id' => $spellId]);
})->group('mysql');
