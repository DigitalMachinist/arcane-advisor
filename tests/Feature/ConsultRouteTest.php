<?php

declare(strict_types=1);

test('POST /api/consult returns the stub envelope shape', function (): void {
    $response = $this->postJson('/api/consult', [
        'prompt' => 'I want to keep my party safe during a long rest.',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'type',
                'roundId',
                'round',
                'recommendations',
                'message',
            ],
            'meta' => [
                'requestId',
                'modelVersion',
                'timingMs',
            ],
        ])
        ->assertJsonPath('data.type', 'recommendations');
});
