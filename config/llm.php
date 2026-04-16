<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | LLM Driver
    |--------------------------------------------------------------------------
    |
    | Selects which LlmClient implementation the container resolves. Valid
    | values: "fixture" (canned responses for tests / offline dev) or
    | "cloudflare" (stub — real Workers AI calls land in a later stage).
    |
    */

    'driver' => env('LLM_DRIVER', 'fixture'),

    'cloudflare' => [
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'completion_model' => env('CLOUDFLARE_COMPLETION_MODEL', '@cf/google/gemma-4-26b-a4b-it'),
        'embedding_model' => env('CLOUDFLARE_EMBEDDING_MODEL', '@cf/baai/bge-base-en-v1.5'),
    ],
];
