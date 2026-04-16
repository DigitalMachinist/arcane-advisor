<?php

declare(strict_types=1);

namespace App\Domain\Spells\Enums;

enum AreaShape: string
{
    case Sphere = 'sphere';
    case Cube = 'cube';
    case Cone = 'cone';
    case Line = 'line';
    case Cylinder = 'cylinder';
    case Wall = 'wall';
}
