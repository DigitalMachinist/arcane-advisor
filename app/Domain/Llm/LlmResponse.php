<?php

declare(strict_types=1);

namespace App\Domain\Llm;

final readonly class LlmResponse
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $text,
        public array $meta = [],
    ) {}
}
