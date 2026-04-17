<?php

declare(strict_types=1);

namespace App\Domain\Spells\Enums;

enum Targeting: string
{
    case Point = 'point';
    case Self = 'self';
    case Creature = 'creature';
    case Creatures = 'creatures';
    case Area = 'area';
    case Touch = 'touch';
}
