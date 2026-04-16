<?php

declare(strict_types=1);

namespace App\Domain\Consult\Actions;

class ConsultAction
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function execute(array $payload): array
    {
        return [
            'data' => [
                'type' => 'recommendations',
                'roundId' => 'stub-round-id',
                'round' => 1,
                'recommendations' => [],
                'message' => null,
            ],
            'meta' => [
                'requestId' => 'stub-request-id',
                'modelVersion' => 'stub',
                'timingMs' => 0,
            ],
        ];
    }
}
