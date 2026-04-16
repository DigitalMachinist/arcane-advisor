<?php

declare(strict_types=1);

test('GitHub Actions check workflow exists and runs all required gates', function (): void {
    $workflowPath = base_path('.github/workflows/check.yml');

    expect($workflowPath)->toBeFile();

    $contents = (string) file_get_contents($workflowPath);

    $expected = [
        'rector' => 'rector process --dry-run',
        'pint' => 'pint --test',
        'phpstan' => 'phpstan analyse',
        'vite build' => 'npm run build',
        'vitest' => 'vitest run',
        'build-group tests' => 'artisan test --group=build',
        'pest' => 'artisan test',
        'type coverage' => 'pest --type-coverage --min=100',
        'mysql group' => 'artisan test --group=mysql',
        'redis group' => 'artisan test --group=redis',
    ];

    foreach ($expected as $label => $needle) {
        expect(str_contains($contents, $needle))->toBeTrue("CI workflow must invoke {$label}");
    }
});
