<?php

declare(strict_types=1);

namespace App\Domain\Spells\Enums;

enum CombatRole: string
{
    case AreaDamage = 'areaDamage';
    case SingleTargetDamage = 'singleTargetDamage';
    case SustainedDamage = 'sustainedDamage';
    case Control = 'control';
    case Debuff = 'debuff';
    case Hinder = 'hinder';
    case Buff = 'buff';
    case Expedite = 'expedite';
    case Defend = 'defend';
    case Heal = 'heal';
    case Move = 'move';
    case Escape = 'escape';
    case Summon = 'summon';
    case Counter = 'counter';
    case Transform = 'transform';
    case Obfuscate = 'obfuscate';
    case Deceive = 'deceive';
    case Sense = 'sense';
    case Alert = 'alert';
    case Communicate = 'communicate';
}
