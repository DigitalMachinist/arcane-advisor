<?php

declare(strict_types=1);

function composerScripts(): array
{
    $composer = json_decode(
        (string) file_get_contents(base_path('composer.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($composer)->toHaveKey('scripts');

    return $composer['scripts'];
}

test('composer scripts.check runs the local gate', function (): void {
    $scripts = composerScripts();

    expect($scripts)->toHaveKey('check');

    $check = collect($scripts['check']);

    $expected = [
        'pint' => 'pint --test',
        'phpstan' => 'phpstan analyse',
        'artisan test' => 'artisan test',
        'vitest' => 'vitest run',
    ];

    foreach ($expected as $label => $needle) {
        expect($check->contains(fn (string $step): bool => str_contains($step, $needle)))
            ->toBeTrue("composer run check must invoke {$label}");
    }
});

test('composer scripts.check:ci adds the deferred heavy gates', function (): void {
    $scripts = composerScripts();

    expect($scripts)->toHaveKey('check:ci');

    $ci = collect($scripts['check:ci']);

    expect($ci->contains(fn (string $step): bool => str_contains($step, '@composer check')))
        ->toBeTrue('check:ci must delegate to the local check script');

    $expected = [
        'rector' => 'rector process --dry-run',
        'vite build' => 'npm run build',
        'build group' => 'artisan test --group=build',
        'type coverage' => 'pest --type-coverage --min=100',
        'mutation testing' => 'pest --mutate --everything --covered-only',
    ];

    foreach ($expected as $label => $needle) {
        expect($ci->contains(fn (string $step): bool => str_contains($step, $needle)))
            ->toBeTrue("composer run check:ci must invoke {$label}");
    }
});
