<?php

declare(strict_types=1);

test('GET /up returns 200', function (): void {
    $response = $this->get('/up');

    $response->assertStatus(200);
});
