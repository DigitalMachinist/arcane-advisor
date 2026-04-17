<?php

declare(strict_types=1);

use App\Console\Commands\SpellsImportCommand;
use App\Domain\Spells\Actions\SpellsImportAction;
use Tests\TestCase;

covers(SpellsImportCommand::class);

uses(TestCase::class);

test('command delegates to SpellsImportAction and formats output', function (): void {
    $mock = Mockery::mock(SpellsImportAction::class);
    $mock->shouldReceive('execute')
        ->once()
        ->with(Mockery::type('string'))
        ->andReturn(42);

    $this->app->instance(SpellsImportAction::class, $mock);

    $this->artisan('spells:import')
        ->expectsOutputToContain('42')
        ->assertExitCode(0);
});

test('command returns exit code 1 when action throws', function (): void {
    $mock = Mockery::mock(SpellsImportAction::class);
    $mock->shouldReceive('execute')
        ->once()
        ->andThrow(new RuntimeException('Something went wrong'));

    $this->app->instance(SpellsImportAction::class, $mock);

    $this->artisan('spells:import')
        ->assertExitCode(1);
});
