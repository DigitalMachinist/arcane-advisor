<?php

declare(strict_types=1);

namespace App\Domain\Llm\Clients;

use App\Domain\Llm\Exceptions\UnsupportedOperationException;
use App\Domain\Llm\LlmClient;
use App\Domain\Llm\LlmResponse;

final class CloudflareClient implements LlmClient
{
    public function embed(string $text): array
    {
        throw new UnsupportedOperationException(
            'CloudflareClient::embed is a stub; a Cloudflare Workers AI call lands in a later stage.',
        );
    }

    public function complete(string $prompt): LlmResponse
    {
        throw new UnsupportedOperationException(
            'CloudflareClient::complete is a stub; a Cloudflare Workers AI call lands in a later stage.',
        );
    }
}
