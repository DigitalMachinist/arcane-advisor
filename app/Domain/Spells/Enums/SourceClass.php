<?php

declare(strict_types=1);

namespace App\Domain\Spells\Enums;

enum SourceClass: string
{
    case Wizard = 'wizard';
    case Sorcerer = 'sorcerer';
    case Cleric = 'cleric';
    case Druid = 'druid';
    case Bard = 'bard';
    case Paladin = 'paladin';
    case Ranger = 'ranger';
    case Warlock = 'warlock';
    case Artificer = 'artificer';
}
