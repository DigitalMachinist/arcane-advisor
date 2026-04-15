<?php

declare(strict_types=1);
use Pest\Mixins\Expectation;

/**
 * Database Compatibility Tests
 *
 * These tests detect usage of MySQL-specific features that are not supported
 * by SQLite (our default test database). Code using these features must be
 * tagged with ->group('mysql') so it runs against MySQL in CI.
 *
 * See docs/testing-strategy.md "Database Compatibility" for the full reference.
 */
arch('no whereJsonContains in app code')
    ->expect('App')
    ->not
    ->toUse(['whereJsonContains']);

arch('no whereJsonLength in app code')
    ->expect('App')
    ->not
    ->toUse(['whereJsonLength']);

/**
 * Scan for MySQL-only SQL patterns in raw database calls.
 *
 * This test greps the application source for DB::raw(), DB::statement(),
 * and DB::unprepared() calls containing MySQL-specific functions that
 * do not work in SQLite.
 */
test('no MySQL-only SQL in raw database calls', function (): void {
    $mysqlPatterns = [
        'FULLTEXT',
        'GROUP_CONCAT',
        'JSON_ARRAYAGG',
        'JSON_OBJECT',
        'DATE_FORMAT',
        'YEAR(',
        'MONTH(',
        'DAY(',
        'REGEXP',
        'RLIKE',
        'FOR UPDATE',
        'LOCK IN SHARE MODE',
    ];

    $appPath = dirname(__DIR__, 2).'/app';
    $violations = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($appPath, RecursiveDirectoryIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        // Only check files that use raw SQL
        if (! preg_match('/DB::(raw|statement|unprepared|select)\s*\(/', $contents)) {
            continue;
        }

        foreach ($mysqlPatterns as $pattern) {
            if (stripos($contents, $pattern) !== false) {
                $relativePath = str_replace(dirname(__DIR__, 2).'/', '', $file->getPathname());
                $violations[] = "{$relativePath} contains MySQL-only pattern: {$pattern}";
            }
        }
    }

    expect($violations)
        ->toBeEmpty()
        ->when(
            count($violations) > 0,
            fn ($expectation): Expectation => $expectation->and(implode("\n", $violations))
                ->toBe('No MySQL-only patterns should exist in app code. Tag related tests with ->group(\'mysql\').'),
        );
});
