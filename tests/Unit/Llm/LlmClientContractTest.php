<?php

declare(strict_types=1);

use App\Domain\Llm\Clients\CloudflareClient;
use App\Domain\Llm\Clients\FixtureClient;
use App\Domain\Llm\LlmClient;

test('FixtureClient implements the LlmClient interface', function (): void {
    expect(new FixtureClient)->toBeInstanceOf(LlmClient::class);
});

test('CloudflareClient implements the LlmClient interface', function (): void {
    expect(new CloudflareClient)->toBeInstanceOf(LlmClient::class);
});

test('LlmClient interface declares embed(string): list<float>', function (): void {
    $reflection = new ReflectionMethod(LlmClient::class, 'embed');

    expect($reflection->isPublic())->toBeTrue()
        ->and($reflection->getParameters())->toHaveCount(1)
        ->and($reflection->getParameters()[0]->getName())->toBe('text')
        ->and((string) $reflection->getParameters()[0]->getType())->toBe('string')
        ->and((string) $reflection->getReturnType())->toBe('array');
});

test('LlmClient interface declares complete(string): LlmResponse', function (): void {
    $reflection = new ReflectionMethod(LlmClient::class, 'complete');

    expect($reflection->isPublic())->toBeTrue()
        ->and($reflection->getParameters())->toHaveCount(1)
        ->and($reflection->getParameters()[0]->getName())->toBe('prompt')
        ->and((string) $reflection->getParameters()[0]->getType())->toBe('string')
        ->and((string) $reflection->getReturnType())->toBe('App\Domain\Llm\LlmResponse');
});
