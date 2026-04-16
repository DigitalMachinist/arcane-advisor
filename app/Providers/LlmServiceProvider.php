<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Llm\Clients\CloudflareClient;
use App\Domain\Llm\Clients\FixtureClient;
use App\Domain\Llm\LlmClient;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

final class LlmServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->bind(LlmClient::class, function (): LlmClient {
            $driver = config('llm.driver') ?: 'fixture';

            return match ($driver) {
                'fixture' => new FixtureClient,
                'cloudflare' => new CloudflareClient,
                default => throw new InvalidArgumentException("Unknown LLM driver: {$driver}"),
            };
        });
    }
}
