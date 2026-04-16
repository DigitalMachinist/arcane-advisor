<?php

declare(strict_types=1);
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

arch('domain classes do not import Illuminate\Http')
    ->expect('App\Domain')
    ->not->toUse('Illuminate\Http');

arch('domain classes do not import Laravel HTTP facades or client')
    ->expect('App\Domain')
    ->not->toUse([
        Http::class,
        PendingRequest::class,
        Request::class,
        Response::class,
        JsonResponse::class,
    ]);
