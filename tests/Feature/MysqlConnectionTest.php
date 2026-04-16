<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

test('mysql connection is active', function (): void {
    $result = DB::select('SELECT 1 AS connected');

    expect($result)->toHaveCount(1)
        ->and($result[0]->connected)->toBe(1);
})->group('mysql');
