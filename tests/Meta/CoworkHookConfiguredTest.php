<?php

declare(strict_types=1);

test('Claude Code TaskCompleted hook runs composer run check', function (): void {
    $settingsPath = base_path('.claude/settings.json');

    expect($settingsPath)->toBeFile();

    /** @var array<string, mixed> $settings */
    $settings = json_decode((string) file_get_contents($settingsPath), true, flags: JSON_THROW_ON_ERROR);

    expect($settings)->toHaveKey('hooks')
        ->and($settings['hooks'])->toHaveKey('TaskCompleted');

    $commands = collect($settings['hooks']['TaskCompleted'])
        ->flatMap(fn (array $entry): array => $entry['hooks'] ?? [])
        ->filter(fn (array $hook): bool => ($hook['type'] ?? null) === 'command')
        ->pluck('command')
        ->filter();

    expect($commands)->not->toBeEmpty();

    expect($commands->contains(fn (string $command): bool => str_contains($command, 'composer run check')))
        ->toBeTrue('TaskCompleted must run composer run check');
});
