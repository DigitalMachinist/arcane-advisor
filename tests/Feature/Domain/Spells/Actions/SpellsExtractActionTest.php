<?php

declare(strict_types=1);

use App\Domain\Llm\Clients\FixtureClient;
use App\Domain\Llm\LlmResponse;
use App\Domain\Spells\Actions\SpellsExtractAction;

covers(SpellsExtractAction::class);

function fixtureRaw(string $slug): array
{
    $path = base_path("tests/Fixtures/extract/raw/{$slug}.json");

    return json_decode(file_get_contents($path), associative: true);
}

function fixtureResponse(string $slug): string
{
    return file_get_contents(base_path("tests/Fixtures/extract/responses/{$slug}.txt"));
}

function makeClient(string $slug, SpellsExtractAction $action): FixtureClient
{
    $rawSpell = fixtureRaw($slug);
    $responseText = fixtureResponse($slug);

    $client = new FixtureClient;

    $prompt = $action->renderPrompt($rawSpell);
    $client->registerCompletion($prompt, new LlmResponse($responseText));

    return $client;
}

test('damage spell produces damage, targeting, and combatRoles', function (): void {
    $action = new SpellsExtractAction(new FixtureClient);
    $client = makeClient('fireball', $action);

    $action = new SpellsExtractAction($client);
    $result = $action->execute(fixtureRaw('fireball'));

    expect($result)
        ->toHaveKey('damage')
        ->toHaveKey('targeting')
        ->toHaveKey('combatRoles');

    expect($result['damage'])->toHaveCount(1);
    expect($result['damage'][0]['type'])->toBe('fire');
    expect($result['damage'][0]['dice'])->toBe('8d6');
    expect($result['targeting'])->toBe('point');
    expect($result['combatRoles'])->toContain('areaDamage');
    expect($result['areaShape'])->toBe('sphere');
    expect($result['areaSize'])->toBe('20 feet');
});

test('fireball result includes savingThrow', function (): void {
    $action = new SpellsExtractAction(new FixtureClient);
    $client = makeClient('fireball', $action);

    $action = new SpellsExtractAction($client);
    $result = $action->execute(fixtureRaw('fireball'));

    expect($result['savingThrow'])->toBeArray();
    expect($result['savingThrow']['ability'])->toBe('dexterity');
    expect($result['attackRoll'])->toBeNull();
});

test('cantrip result has level 0 and other fields populated', function (): void {
    $action = new SpellsExtractAction(new FixtureClient);
    $client = makeClient('chill-touch', $action);

    $action = new SpellsExtractAction($client);
    $rawSpell = fixtureRaw('chill-touch');
    $result = $action->execute($rawSpell);

    expect($result['level'])->toBe(0);
    expect($result['damage'])->toHaveCount(1);
    expect($result['damage'][0]['type'])->toBe('necrotic');
    expect($result['targeting'])->toBe('creature');
    expect($result['attackRoll'])->toBe('ranged');
});

test('conditions spell has conditions populated', function (): void {
    $action = new SpellsExtractAction(new FixtureClient);
    $client = makeClient('hold-person', $action);

    $action = new SpellsExtractAction($client);
    $result = $action->execute(fixtureRaw('hold-person'));

    expect($result['conditions'])->toContain('paralyzed');
    expect($result['combatRoles'])->toContain('control');
});

test('concentration spell has concentration in qualifiers', function (): void {
    $action = new SpellsExtractAction(new FixtureClient);
    $client = makeClient('hold-person', $action);

    $action = new SpellsExtractAction($client);
    $result = $action->execute(fixtureRaw('hold-person'));

    expect($result['qualifiers'])->toContain('concentration');
});

test('result merges raw spell fields with extracted fields', function (): void {
    $action = new SpellsExtractAction(new FixtureClient);
    $client = makeClient('fireball', $action);

    $action = new SpellsExtractAction($client);
    $result = $action->execute(fixtureRaw('fireball'));

    // Raw fields should be preserved
    expect($result['name'])->toBe('Fireball');
    expect($result['slug'])->toBe('fireball');
    expect($result['level'])->toBe(3);
    expect($result['school'])->toBe('evocation');

    // Extracted fields should be present
    expect($result)->toHaveKey('damage');
    expect($result)->toHaveKey('conditions');
    expect($result)->toHaveKey('targeting');
    expect($result)->toHaveKey('areaShape');
    expect($result)->toHaveKey('areaSize');
    expect($result)->toHaveKey('savingThrow');
    expect($result)->toHaveKey('attackRoll');
    expect($result)->toHaveKey('combatRoles');
    expect($result)->toHaveKey('utilities');
    expect($result)->toHaveKey('qualifiers');
});

test('invalid enum value in LLM response throws exception', function (): void {
    $badResponseText = json_encode([
        'damage' => [],
        'conditions' => [],
        'targeting' => 'INVALID_VALUE',
        'areaShape' => null,
        'areaSize' => null,
        'savingThrow' => null,
        'attackRoll' => null,
        'combatRoles' => [],
        'utilities' => [],
        'qualifiers' => [],
    ]);

    $action = new SpellsExtractAction(new FixtureClient);
    $rawSpell = fixtureRaw('fireball');
    $prompt = $action->renderPrompt($rawSpell);

    $client = new FixtureClient;
    $client->registerCompletion($prompt, new LlmResponse($badResponseText));

    $action = new SpellsExtractAction($client);

    expect(fn () => $action->execute($rawSpell))
        ->toThrow(RuntimeException::class);
});
