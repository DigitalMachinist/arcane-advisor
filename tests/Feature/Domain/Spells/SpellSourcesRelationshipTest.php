<?php

declare(strict_types=1);

use App\Domain\Spells\Enums\SourceCode;
use App\Domain\Spells\Models\Spell;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

covers(Spell::class);

test('spell can have multiple source records', function (): void {
    // Factory inserts PHB; add a second source book.
    $spell = Spell::factory()->create();

    DB::table('spell_sources')->insert([
        'spell_id' => $spell->id,
        'code' => SourceCode::XanatharsGuideToEverything->value,
        'page' => 15,
    ]);

    expect($spell->sources()->count())->toBe(2);
})->group('mysql');

test('spell sources returns correct data', function (): void {
    $spell = Spell::factory()->create();

    // Factory inserts PHB; retrieve it to verify the data shape.
    $source = $spell->sources()->first();

    expect($source->code)->toBe(SourceCode::PlayersHandbook)
        ->and($source->page)->toBeInt();
})->group('mysql');

test('deleting a spell cascades to spell sources', function (): void {
    $spell = Spell::factory()->create();
    $spellId = $spell->id;

    $spell->delete();

    $this->assertDatabaseMissing('spell_sources', ['spell_id' => $spellId]);
})->group('mysql');
