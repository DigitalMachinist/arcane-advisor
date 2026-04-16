<?php

declare(strict_types=1);

arch('domain classes do not import Illuminate\Http')
    ->expect('App\Domain')
    ->not->toUse('Illuminate\Http');

arch('domain classes do not import Laravel HTTP facades or client')
    ->expect('App\Domain')
    ->not->toUse([
        'Illuminate\Support\Facades\Http',
        'Illuminate\Http\Client\PendingRequest',
        'Illuminate\Http\Request',
        'Illuminate\Http\Response',
        'Illuminate\Http\JsonResponse',
    ]);
