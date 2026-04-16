<?php

declare(strict_types=1);

namespace App\Domain\Spells\Enums;

enum SourceCode: string
{
    case PlayersHandbook = 'phb';
    case XanatharsGuideToEverything = 'xge';
    case TashasCauldronOfEverything = 'tce';
    case SwordCoastAdventurersGuide = 'scag';
    case FizbansTreasuryOfDragons = 'ftd';
    case AstralAdventurersGuide = 'aag';
}
