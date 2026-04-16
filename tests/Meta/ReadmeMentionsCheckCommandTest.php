<?php

declare(strict_types=1);

test('README references composer run check as the validation entry point', function (): void {
    $readme = (string) file_get_contents(base_path('README.md'));

    expect($readme)->toContain('composer run check');
});
