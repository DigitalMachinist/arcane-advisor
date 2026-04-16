<?php

declare(strict_types=1);

namespace App\Domain\Spells\Enums;

enum ActionEconomy: string
{
    case Action = 'action';
    case BonusAction = 'bonusAction';
    case Reaction = 'reaction';
    case Minute = 'minute';
    case TenMinutes = 'tenMinutes';
    case Hour = 'hour';
    case Longer = 'longer';
}
