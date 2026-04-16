<?php

declare(strict_types=1);

test('Vite build emits a CSS asset listed in the manifest', function (): void {
    $manifestPath = public_path('build/manifest.json');

    expect($manifestPath)->toBeFile(
        'public/build/manifest.json is missing; composer run check should have produced it via npm run build.',
    );

    /** @var array<string, array{file?: string, css?: array<int, string>}> $manifest */
    $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);

    $cssAssets = collect($manifest)
        ->flatMap(fn (array $entry): array => [
            ...($entry['css'] ?? []),
            ...(isset($entry['file']) && str_ends_with($entry['file'], '.css') ? [$entry['file']] : []),
        ])
        ->unique()
        ->values();

    expect($cssAssets)->not->toBeEmpty();

    foreach ($cssAssets as $cssAsset) {
        expect(public_path('build/'.$cssAsset))->toBeFile();
    }
})->group('build');
