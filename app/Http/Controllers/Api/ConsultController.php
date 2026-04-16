<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Consult\Actions\ConsultAction;
use App\Http\Requests\ConsultRequest;
use Illuminate\Http\JsonResponse;

final class ConsultController
{
    public function __construct(private readonly ConsultAction $action) {}

    public function __invoke(ConsultRequest $request): JsonResponse
    {
        return new JsonResponse($this->action->execute($request->validated()));
    }
}
