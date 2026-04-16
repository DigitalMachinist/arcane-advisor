<?php

declare(strict_types=1);

namespace App\Domain\Llm;

interface LlmClient
{
    /**
     * @return list<float>
     */
    public function embed(string $text): array;

    public function complete(string $prompt): LlmResponse;
}
