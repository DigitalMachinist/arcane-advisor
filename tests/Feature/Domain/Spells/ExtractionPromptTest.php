<?php

declare(strict_types=1);

use App\Domain\Llm\Clients\FixtureClient;
use App\Domain\Spells\Actions\SpellsExtractAction;

covers(SpellsExtractAction::class);

test('prompt template file exists', function (): void {
    expect(file_exists(base_path('resources/prompts/spell-extraction.txt')))->toBeTrue();
});

test('prompt template contains required slot for name', function (): void {
    $prompt = file_get_contents(base_path('resources/prompts/spell-extraction.txt'));
    expect($prompt)->toContain('{{ name }}');
});

test('prompt template contains required slot for description', function (): void {
    $prompt = file_get_contents(base_path('resources/prompts/spell-extraction.txt'));
    expect($prompt)->toContain('{{ description }}');
});

test('prompt template contains required slot for castingTime', function (): void {
    $prompt = file_get_contents(base_path('resources/prompts/spell-extraction.txt'));
    expect($prompt)->toContain('{{ castingTime }}');
});

test('prompt template contains required slot for range', function (): void {
    $prompt = file_get_contents(base_path('resources/prompts/spell-extraction.txt'));
    expect($prompt)->toContain('{{ range }}');
});

test('prompt template contains required slot for duration', function (): void {
    $prompt = file_get_contents(base_path('resources/prompts/spell-extraction.txt'));
    expect($prompt)->toContain('{{ duration }}');
});

test('prompt template contains damage type vocabulary', function (): void {
    $prompt = file_get_contents(base_path('resources/prompts/spell-extraction.txt'));

    expect($prompt)
        ->toContain('fire')
        ->toContain('necrotic')
        ->toContain('lightning')
        ->toContain('acid');
});

test('prompt template contains condition vocabulary', function (): void {
    $prompt = file_get_contents(base_path('resources/prompts/spell-extraction.txt'));

    expect($prompt)
        ->toContain('paralyzed')
        ->toContain('charmed')
        ->toContain('stunned')
        ->toContain('frightened');
});

test('prompt template contains targeting vocabulary', function (): void {
    $prompt = file_get_contents(base_path('resources/prompts/spell-extraction.txt'));

    expect($prompt)
        ->toContain('point')
        ->toContain('creature')
        ->toContain('area')
        ->toContain('touch');
});

test('prompt template contains combat role vocabulary', function (): void {
    $prompt = file_get_contents(base_path('resources/prompts/spell-extraction.txt'));

    expect($prompt)
        ->toContain('areaDamage')
        ->toContain('control')
        ->toContain('singleTargetDamage');
});

test('prompt template contains qualifier vocabulary', function (): void {
    $prompt = file_get_contents(base_path('resources/prompts/spell-extraction.txt'));

    expect($prompt)
        ->toContain('concentration')
        ->toContain('ritual');
});

test('prompt template contains utility vocabulary', function (): void {
    $prompt = file_get_contents(base_path('resources/prompts/spell-extraction.txt'));

    expect($prompt)
        ->toContain('explore')
        ->toContain('learn')
        ->toContain('communicate');
});

test('prompt template contains area shape vocabulary', function (): void {
    $prompt = file_get_contents(base_path('resources/prompts/spell-extraction.txt'));

    expect($prompt)
        ->toContain('sphere')
        ->toContain('cone')
        ->toContain('cylinder');
});

test('action renders prompt slots', function (): void {
    $action = new SpellsExtractAction(new FixtureClient);

    $rendered = $action->renderPrompt([
        'name' => 'Fireball',
        'rawDescription' => 'A bright streak flashes...',
        'castingTime' => '1 action',
        'range' => '150 feet',
        'duration' => 'Instantaneous',
    ]);

    expect($rendered)
        ->toContain('Fireball')
        ->toContain('A bright streak flashes...')
        ->toContain('1 action')
        ->toContain('150 feet')
        ->toContain('Instantaneous')
        ->not->toContain('{{ name }}')
        ->not->toContain('{{ description }}')
        ->not->toContain('{{ castingTime }}')
        ->not->toContain('{{ range }}')
        ->not->toContain('{{ duration }}');
});
