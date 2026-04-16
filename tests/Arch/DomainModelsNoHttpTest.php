<?php

declare(strict_types=1);

arch('domain spell models do not import Illuminate\Http')
    ->expect('App\Domain\Spells\Models')
    ->not->toUse('Illuminate\Http');
