<?php

declare(strict_types=1);

namespace App\Domain\Spells\Enums;

enum School: string
{
    case Abjuration = 'abjuration';
    case Conjuration = 'conjuration';
    case Divination = 'divination';
    case Enchantment = 'enchantment';
    case Evocation = 'evocation';
    case Illusion = 'illusion';
    case Necromancy = 'necromancy';
    case Transmutation = 'transmutation';
}
