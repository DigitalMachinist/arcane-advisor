<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

test('every class under App\Domain\\**\\Actions exposes a single public execute', function (): void {
    $actionClasses = [];
    $domainPath = app_path('Domain');

    if (is_dir($domainPath)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($domainPath));

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $path = $file->getPathname();
            if (! str_contains((string) $path, '/Actions/')) {
                continue;
            }

            $relative = str_replace($domainPath.'/', '', $path);
            $classPath = substr($relative, 0, -4);
            $class = 'App\\Domain\\'.str_replace('/', '\\', $classPath);

            if (class_exists($class)) {
                $actionClasses[] = $class;
            }
        }
    }

    expect($actionClasses)->not->toBeEmpty('At least one Action class must exist under app/Domain');

    foreach ($actionClasses as $class) {
        $reflection = new ReflectionClass($class);
        $ownPublicMethods = array_values(array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            fn (ReflectionMethod $m): bool => $m->class === $class && $m->getName() !== '__construct',
        ));
        $names = array_map(fn (ReflectionMethod $m): string => $m->getName(), $ownPublicMethods);

        expect($names)->toBe(['execute'], "{$class} must expose a single public execute method");
    }
});
