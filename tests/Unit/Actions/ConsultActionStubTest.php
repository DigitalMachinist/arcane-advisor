<?php

declare(strict_types=1);

use App\Domain\Consult\Actions\ConsultAction;

test('ConsultAction::execute returns the stub envelope', function (): void {
    $result = (new ConsultAction)->execute(['prompt' => 'anything']);

    expect($result)->toHaveKeys(['data', 'meta'])
        ->and($result['data'])->toHaveKeys(['type', 'roundId', 'round', 'recommendations', 'message'])
        ->and($result['data']['type'])->toBe('recommendations')
        ->and($result['data']['recommendations'])->toBeArray()
        ->and($result['meta'])->toHaveKeys(['requestId', 'modelVersion', 'timingMs']);
});
