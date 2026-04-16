<?php

declare(strict_types=1);

use App\Http\Controllers\Controller;
use Tests\TestCase;

uses(TestCase::class);

arch('API controllers are invokable')
    ->expect('App\Http\Controllers\Api')
    ->toBeInvokable();

arch('API controllers do not extend the base Controller')
    ->expect('App\Http\Controllers\Api')
    ->not->toExtend(Controller::class);

test('every API controller exposes only __construct and __invoke', function (): void {
    $namespace = 'App\\Http\\Controllers\\Api\\';
    $directory = app_path('Http/Controllers/Api');

    if (! is_dir($directory)) {
        $this->markTestSkipped('No Api controllers yet.');
    }

    foreach (glob($directory.'/*.php') as $file) {
        $class = $namespace.pathinfo($file, PATHINFO_FILENAME);
        expect(class_exists($class))->toBeTrue("Expected class {$class}");

        $reflection = new ReflectionClass($class);
        $ownPublicMethods = array_values(array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            fn (ReflectionMethod $m): bool => $m->class === $class,
        ));
        $names = array_map(fn (ReflectionMethod $m): string => $m->getName(), $ownPublicMethods);
        sort($names);

        expect($names)->toBe(['__construct', '__invoke'], "{$class} must expose only __construct and __invoke");
    }
});
