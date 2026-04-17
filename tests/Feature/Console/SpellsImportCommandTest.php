<?php

declare(strict_types=1);

use App\Domain\Spells\Actions\SpellsImportAction;
use App\Domain\Spells\Models\Spell;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('spells:import command exits 0 on success and outputs count', function (): void {
    $this->artisan('spells:import')
        ->assertExitCode(0)
        ->expectsOutputToContain('3');
})->group('mysql');

test('spells:import command exits non-zero on failure', function (): void {
    // Point the command at a non-existent directory by mocking the action
    $mock = Mockery::mock(SpellsImportAction::class);
    $mock->shouldReceive('execute')->andThrow(new RuntimeException('Import failed'));

    $this->app->instance(SpellsImportAction::class, $mock);

    $this->artisan('spells:import')
        ->assertExitCode(1);
})->group('mysql');

test('spells:import command imports into the database', function (): void {
    $this->artisan('spells:import')->assertExitCode(0);

    expect(Spell::count())->toBe(3);
})->group('mysql');
