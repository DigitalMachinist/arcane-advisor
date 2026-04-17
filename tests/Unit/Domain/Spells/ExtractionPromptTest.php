<?php

declare(strict_types=1);

use App\Domain\Spells\Actions\SpellsExtractAction;

covers(SpellsExtractAction::class);

// Unit tests do not boot Laravel, so we use a raw path resolved from __DIR__.
function promptPath(): string
{
    return dirname(__DIR__, 4).'/resources/prompts/spell-extraction.txt';
}

test('prompt template file exists', function (): void {
    expect(file_exists(promptPath()))->toBeTrue();
});

test('prompt template contains required slot for name', function (): void {
    $prompt = file_get_contents(promptPath());
    expect($prompt)->toContain('{{ name }}');
});

test('prompt template contains required slot for description', function (): void {
    $prompt = file_get_contents(promptPath());
    expect($prompt)->toContain('{{ description }}');
});

test('prompt template contains required slot for castingTime', function (): void {
    $prompt = file_get_contents(promptPath());
    expect($prompt)->toContain('{{ castingTime }}');
});

test('prompt template contains required slot for range', function (): void {
    $prompt = file_get_contents(promptPath());
    expect($prompt)->toContain('{{ range }}');
});

test('prompt template contains required slot for duration', function (): void {
    $prompt = file_get_contents(promptPath());
    expect($prompt)->toContain('{{ duration }}');
});

test('prompt template contains damage type vocabulary', function (): void {
    $prompt = file_get_contents(promptPath());

    expect($prompt)
        ->toContain('fire')
        ->toContain('necrotic')
        ->toContain('lightning')
        ->toContain('acid');
});

test('prompt template contains condition vocabulary', function (): void {
    $prompt = file_get_contents(promptPath());

    expect($prompt)
        ->toContain('paralyzed')
        ->toContain('charmed')
        ->toContain('stunned')
        ->toContain('frightened');
});

test('prompt template contains targeting vocabulary', function (): void {
    $prompt = file_get_contents(promptPath());

    expect($prompt)
        ->toContain('point')
        ->toContain('creature')
        ->toContain('area')
        ->toContain('touch');
});

test('prompt template contains combat role vocabulary', function (): void {
    $prompt = file_get_contents(promptPath());

    expect($prompt)
        ->toContain('areaDamage')
        ->toContain('control')
        ->toContain('singleTargetDamage');
});

test('prompt template contains qualifier vocabulary', function (): void {
    $prompt = file_get_contents(promptPath());

    expect($prompt)
        ->toContain('concentration')
        ->toContain('ritual');
});

test('prompt template contains utility vocabulary', function (): void {
    $prompt = file_get_contents(promptPath());

    expect($prompt)
        ->toContain('explore')
        ->toContain('learn')
        ->toContain('communicate');
});

test('prompt template contains area shape vocabulary', function (): void {
    $prompt = file_get_contents(promptPath());

    expect($prompt)
        ->toContain('sphere')
        ->toContain('cone')
        ->toContain('cylinder');
});

// The renderPrompt slot-filling test lives in Feature where base_path() is available.
// See tests/Feature/Domain/Spells/ExtractionPromptTest.php.
