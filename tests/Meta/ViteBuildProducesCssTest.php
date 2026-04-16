<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;

test('Vite build emits a CSS asset listed in the manifest', function (): void {
    $manifestPath = public_path('build/manifest.json');

    if (! file_exists($manifestPath)) {
        Process::timeout(300)->path(base_path())->run('npm run build')->throw();
    }

    expect($manifestPath)->toBeFile();

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
});
