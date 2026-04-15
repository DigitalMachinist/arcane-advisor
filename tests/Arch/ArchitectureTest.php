<?php

declare(strict_types=1);
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

arch()->preset()->php();
arch()->preset()->security();

arch('no debugging functions')
    ->expect([
        'dd',
        'dump',
        'ray',
        'var_dump',
        'print_r',
    ])
    ->not
    ->toBeUsed();

arch('controllers have Controller suffix')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('models extend Eloquent Model')
    ->expect('App\Models')
    ->toExtend(Model::class);

arch('form requests extend FormRequest')
    ->expect('App\Http\Requests')
    ->toHaveSuffix('Request')
    ->toExtend(FormRequest::class);

arch('jobs implement ShouldQueue')
    ->expect('App\Jobs')
    ->toImplement(ShouldQueue::class);
