<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

test('no test file calls Http::get/post/send without Http::fake or Http::preventStrayRequests in the same file', function (): void {
    $testsPath = base_path('tests');
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testsPath));

    $offenders = [];

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = (string) file_get_contents($file->getPathname());

        if (! preg_match('/\bHttp::(?:get|post|send|put|patch|delete|head)\b/', $contents)) {
            continue;
        }

        $hasGuard = preg_match('/\bHttp::(?:fake|preventStrayRequests)\b/', $contents) === 1;

        if (! $hasGuard) {
            $offenders[] = $file->getPathname();
        }
    }

    expect($offenders)->toBe([], 'Test files hit Http without Http::fake()/preventStrayRequests: '.implode(', ', $offenders));
});
