<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Redis;

test('Redis::ping() returns PONG', function (): void {
    $response = Redis::connection()->ping();

    // phpredis returns true on success; predis returns 'PONG'.
    expect($response === true || $response === 'PONG')->toBeTrue();
})->group('redis');
