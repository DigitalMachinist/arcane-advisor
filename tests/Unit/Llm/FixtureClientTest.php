<?php

declare(strict_types=1);

use App\Domain\Llm\Clients\FixtureClient;
use App\Domain\Llm\Exceptions\FixtureNotFoundException;
use App\Domain\Llm\LlmResponse;

test('returns the registered completion fixture for a known key', function (): void {
    $client = new FixtureClient;
    $client->registerCompletion('hello', new LlmResponse('world'));

    expect($client->complete('hello'))
        ->toBeInstanceOf(LlmResponse::class)
        ->text->toBe('world');
});

test('returns the registered embedding fixture for a known key', function (): void {
    $client = new FixtureClient;
    $client->registerEmbedding('fireball', [0.1, 0.2, 0.3]);

    expect($client->embed('fireball'))->toBe([0.1, 0.2, 0.3]);
});

test('throws FixtureNotFoundException for an unknown completion key', function (): void {
    $client = new FixtureClient;

    expect(fn (): LlmResponse => $client->complete('missing'))
        ->toThrow(FixtureNotFoundException::class, 'No completion fixture registered for key: missing');
});

test('throws FixtureNotFoundException for an unknown embedding key', function (): void {
    $client = new FixtureClient;

    expect(fn (): array => $client->embed('missing'))
        ->toThrow(FixtureNotFoundException::class, 'No embedding fixture registered for key: missing');
});
