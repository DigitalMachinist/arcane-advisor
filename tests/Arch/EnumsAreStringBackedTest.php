<?php

declare(strict_types=1);

arch('spell enums are string-backed enums')
    ->expect('App\Domain\Spells\Enums')
    ->toBeStringBackedEnum();
