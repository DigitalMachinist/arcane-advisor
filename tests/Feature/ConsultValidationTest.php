<?php

declare(strict_types=1);

test('POST /api/consult without a prompt returns 422', function (): void {
    $response = $this->postJson('/api/consult', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['prompt']);
});

test('POST /api/consult with an empty prompt returns 422', function (): void {
    $response = $this->postJson('/api/consult', ['prompt' => '']);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['prompt']);
});
