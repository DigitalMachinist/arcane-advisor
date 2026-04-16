<?php

declare(strict_types=1);

namespace App\Domain\Llm\Clients;

use App\Domain\Llm\Exceptions\FixtureNotFoundException;
use App\Domain\Llm\LlmClient;
use App\Domain\Llm\LlmResponse;

final class FixtureClient implements LlmClient
{
    /** @var array<string, LlmResponse> */
    private array $completions = [];

    /** @var array<string, list<float>> */
    private array $embeddings = [];

    public function registerCompletion(string $key, LlmResponse $response): void
    {
        $this->completions[$key] = $response;
    }

    /**
     * @param  list<float>  $vector
     */
    public function registerEmbedding(string $key, array $vector): void
    {
        $this->embeddings[$key] = $vector;
    }

    public function embed(string $text): array
    {
        return $this->embeddings[$text]
            ?? throw new FixtureNotFoundException("No embedding fixture registered for key: {$text}");
    }

    public function complete(string $prompt): LlmResponse
    {
        return $this->completions[$prompt]
            ?? throw new FixtureNotFoundException("No completion fixture registered for key: {$prompt}");
    }
}
