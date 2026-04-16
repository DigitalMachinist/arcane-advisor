<?php

declare(strict_types=1);

namespace App\Domain\Spells\Enums;

enum DurationCategory: string
{
    case Instantaneous = 'instantaneous';
    case Timed = 'timed';
    case UntilDispelled = 'untilDispelled';
    case Permanent = 'permanent';
}
