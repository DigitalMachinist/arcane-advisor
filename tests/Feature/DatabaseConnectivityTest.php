<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('default database connection resolves', function (): void {
    $result = DB::select('SELECT 1 AS connected');

    expect($result)->toHaveCount(1)
        ->and($result[0]->connected)->toBe(1);
})->group('mysql');

test('a trivial migration applies and rolls back cleanly', function (): void {
    $table = 'pr_0_4_migration_probe';

    expect(Schema::hasTable($table))->toBeFalse();

    Schema::create($table, function (Blueprint $blueprint): void {
        $blueprint->id();
        $blueprint->string('label');
        $blueprint->timestamps();
    });

    expect(Schema::hasTable($table))->toBeTrue()
        ->and(Schema::hasColumn($table, 'label'))->toBeTrue();

    Schema::drop($table);

    expect(Schema::hasTable($table))->toBeFalse();
})->group('mysql');
