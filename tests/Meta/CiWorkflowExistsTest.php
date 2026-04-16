<?php

declare(strict_types=1);

test('GitHub Actions check workflow exists and invokes composer run check:ci', function (): void {
    $workflowPath = base_path('.github/workflows/check.yml');

    expect($workflowPath)->toBeFile();

    $contents = (string) file_get_contents($workflowPath);

    expect($contents)->toContain('composer run check:ci');
});
