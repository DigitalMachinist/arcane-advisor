<?php

declare(strict_types=1);

use App\Domain\Consult\Actions\ConsultAction;
use App\Http\Controllers\Api\ConsultController;
use App\Http\Requests\ConsultRequest;
use Illuminate\Http\JsonResponse;

test('controller delegates to ConsultAction::execute with the validated payload', function (): void {
    $validated = ['prompt' => 'fireball, please'];
    $actionResult = ['data' => ['type' => 'recommendations'], 'meta' => []];

    $request = Mockery::mock(ConsultRequest::class);
    $request->shouldReceive('validated')->once()->andReturn($validated);

    $action = Mockery::mock(ConsultAction::class);
    $action->shouldReceive('execute')->with($validated)->once()->andReturn($actionResult);

    $controller = new ConsultController($action);
    $response = $controller($request);

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getData(true))->toBe($actionResult);
});

test('controller has no public methods beyond __invoke and the constructor', function (): void {
    $reflection = new ReflectionClass(ConsultController::class);

    $publicMethods = array_values(array_filter(
        $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
        fn (ReflectionMethod $m): bool => $m->class === ConsultController::class,
    ));

    $names = array_map(fn (ReflectionMethod $m): string => $m->getName(), $publicMethods);
    sort($names);

    expect($names)->toBe(['__construct', '__invoke']);
});
