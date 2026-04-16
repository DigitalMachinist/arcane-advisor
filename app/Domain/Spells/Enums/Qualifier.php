<?php

declare(strict_types=1);

namespace App\Domain\Spells\Enums;

enum Qualifier: string
{
    case Concentration = 'concentration';
    case Ritual = 'ritual';
}
