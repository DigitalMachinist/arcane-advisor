<?php

declare(strict_types=1);

namespace App\Domain\Spells\Enums;

enum SourceCode: string
{
    case Phb = 'phb';
    case Xge = 'xge';
    case Tce = 'tce';
    case Scag = 'scag';
    case Ftd = 'ftd';
    case Aag = 'aag';
}
