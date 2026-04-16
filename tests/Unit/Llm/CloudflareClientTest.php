<?php

declare(strict_types=1);

use App\Domain\Llm\Clients\CloudflareClient;
use App\Domain\Llm\Exceptions\UnsupportedOperationException;
use App\Domain\Llm\LlmResponse;

test('CloudflareClient::embed throws UnsupportedOperationException (stubbed)', function (): void {
    $client = new CloudflareClient;

    expect(fn (): array => $client->embed('anything'))
        ->toThrow(UnsupportedOperationException::class);
});

test('CloudflareClient::complete throws UnsupportedOperationException (stubbed)', function (): void {
    $client = new CloudflareClient;

    expect(fn (): LlmResponse => $client->complete('anything'))
        ->toThrow(UnsupportedOperationException::class);
});
