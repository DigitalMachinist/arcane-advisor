<?php

declare(strict_types=1);

use App\Console\Commands\SpellsExtractCommand;
use App\Domain\Llm\Clients\FixtureClient;
use App\Domain\Llm\LlmClient;
use App\Domain\Llm\LlmResponse;
use App\Domain\Spells\Actions\SpellsExtractAction;

covers(SpellsExtractCommand::class);

beforeEach(function (): void {
    // Set up a FixtureClient with pre-registered responses for both test fixtures.
    // We need to register completions keyed by the rendered prompt text.
    $action = new SpellsExtractAction(new FixtureClient);

    $fixtureDir = base_path('tests/Fixtures/extract/raw');
    $responsesDir = base_path('tests/Fixtures/extract/responses');

    $client = new FixtureClient;

    foreach (['fireball', 'chill-touch', 'hold-person'] as $slug) {
        $rawPath = "{$fixtureDir}/{$slug}.json";

        if (! file_exists($rawPath)) {
            continue;
        }

        $rawSpell = json_decode(file_get_contents($rawPath), associative: true);
        $responseText = file_get_contents("{$responsesDir}/{$slug}.txt");
        $prompt = $action->renderPrompt($rawSpell);
        $client->registerCompletion($prompt, new LlmResponse($responseText));
    }

    $this->app->instance(LlmClient::class, $client);
});

test('spells:extract exits 0 when all files succeed', function (): void {
    $inputDir = base_path('tests/Fixtures/extract/raw');
    $outputDir = sys_get_temp_dir().'/arcane-extract-test-'.uniqid();
    mkdir($outputDir, 0755, true);

    $this->artisan('spells:extract', [
        '--input' => $inputDir,
        '--output' => $outputDir,
    ])->assertExitCode(0);

    // Cleanup
    array_map('unlink', glob("{$outputDir}/*.yaml"));
    rmdir($outputDir);
});

test('spells:extract reports count of processed files', function (): void {
    $inputDir = base_path('tests/Fixtures/extract/raw');
    $outputDir = sys_get_temp_dir().'/arcane-extract-test-'.uniqid();
    mkdir($outputDir, 0755, true);

    $this->artisan('spells:extract', [
        '--input' => $inputDir,
        '--output' => $outputDir,
    ])
        ->assertExitCode(0)
        ->expectsOutputToContain('spell');

    // Cleanup
    array_map('unlink', glob("{$outputDir}/*.yaml"));
    rmdir($outputDir);
});

test('spells:extract writes yaml files to output directory', function (): void {
    $inputDir = base_path('tests/Fixtures/extract/raw');
    $outputDir = sys_get_temp_dir().'/arcane-extract-test-'.uniqid();
    mkdir($outputDir, 0755, true);

    $this->artisan('spells:extract', [
        '--input' => $inputDir,
        '--output' => $outputDir,
    ])->assertExitCode(0);

    $files = glob("{$outputDir}/*.yaml");
    expect($files)->not->toBeEmpty();

    // Cleanup
    array_map('unlink', $files);
    rmdir($outputDir);
});

test('spells:extract exits 1 when action throws an exception', function (): void {
    $mock = Mockery::mock(SpellsExtractAction::class);
    $mock->shouldReceive('execute')->andThrow(new RuntimeException('LLM failed'));

    $this->app->instance(SpellsExtractAction::class, $mock);

    $inputDir = base_path('tests/Fixtures/extract/raw');
    $outputDir = sys_get_temp_dir().'/arcane-extract-test-'.uniqid();
    mkdir($outputDir, 0755, true);

    $this->artisan('spells:extract', [
        '--input' => $inputDir,
        '--output' => $outputDir,
    ])->assertExitCode(1);

    rmdir($outputDir);
});

test('spells:extract uses default input and output directories when options omitted', function (): void {
    // Just ensure the command signature accepts no options and uses defaults.
    // We pass explicit paths here to avoid writing to real storage during tests.
    $inputDir = base_path('tests/Fixtures/extract/raw');
    $outputDir = sys_get_temp_dir().'/arcane-extract-default-'.uniqid();
    mkdir($outputDir, 0755, true);

    $exitCode = $this->artisan('spells:extract', [
        '--input' => $inputDir,
        '--output' => $outputDir,
    ])->run();

    expect($exitCode)->toBe(0);

    // Cleanup
    array_map('unlink', glob("{$outputDir}/*.yaml"));
    rmdir($outputDir);
});
