<?php

declare(strict_types=1);

use App\Domain\Llm\Clients\CloudflareClient;
use App\Domain\Llm\Clients\FixtureClient;
use App\Domain\Llm\LlmClient;
use Tests\TestCase;

uses(TestCase::class);

test('container resolves the fixture driver by default', function (): void {
    config()->set('llm.driver', 'fixture');

    expect(app(LlmClient::class))->toBeInstanceOf(FixtureClient::class);
});

test('container resolves the cloudflare driver when configured', function (): void {
    config()->set('llm.driver', 'cloudflare');

    expect(app(LlmClient::class))->toBeInstanceOf(CloudflareClient::class);
});

test('container defaults to fixture when no driver is configured', function (): void {
    config()->offsetUnset('llm.driver');

    expect(app(LlmClient::class))->toBeInstanceOf(FixtureClient::class);
});
