<?php

declare(strict_types=1);

test('CLAUDE.md references implementation-plan, checklist, and all three schema docs', function (): void {
    $claude = (string) file_get_contents(base_path('CLAUDE.md'));

    $expected = [
        'implementation-plan.md',
        'checklist.md',
        'api-consult.md',
        'spell-yaml.md',
        'enums.md',
    ];

    foreach ($expected as $needle) {
        expect($claude)->toContain($needle);
    }
});
