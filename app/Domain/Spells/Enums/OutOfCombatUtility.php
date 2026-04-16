<?php

declare(strict_types=1);

namespace App\Domain\Spells\Enums;

enum OutOfCombatUtility: string
{
    case Explore = 'explore';
    case Influence = 'influence';
    case Deceive = 'deceive';
    case Obfuscate = 'obfuscate';
    case Communicate = 'communicate';
    case Travel = 'travel';
    case Learn = 'learn';
    case Create = 'create';
    case Shape = 'shape';
    case Heal = 'heal';
    case Ward = 'ward';
}
