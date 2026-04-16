<?php

declare(strict_types=1);

test('GET / returns 200 and exposes the Vue mount point', function (): void {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('id="app"', false);
})->group('build');
