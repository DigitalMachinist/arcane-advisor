<?php

declare(strict_types=1);

namespace App\Domain\Spells\Enums;

enum AttackRoll: string
{
    case Melee = 'melee';
    case Ranged = 'ranged';
}
