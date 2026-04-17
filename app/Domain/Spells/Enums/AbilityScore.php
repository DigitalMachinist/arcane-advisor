<?php

declare(strict_types=1);

namespace App\Domain\Spells\Enums;

enum AbilityScore: string
{
    case Strength = 'strength';
    case Dexterity = 'dexterity';
    case Constitution = 'constitution';
    case Intelligence = 'intelligence';
    case Wisdom = 'wisdom';
    case Charisma = 'charisma';
}
