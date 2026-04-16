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

test('POST /api/consult with a non-string prompt returns 422', function (): void {
    $response = $this->postJson('/api/consult', ['prompt' => 42]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['prompt']);
});

test('POST /api/consult with a prompt over 500 characters returns 422', function (): void {
    $response = $this->postJson('/api/consult', ['prompt' => str_repeat('a', 501)]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['prompt']);
});

test('POST /api/consult with a single character prompt returns 200', function (): void {
    $response = $this->postJson('/api/consult', ['prompt' => 'a']);

    $response->assertStatus(200);
});
