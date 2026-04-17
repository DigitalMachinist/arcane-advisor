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
    case ExplorersGuideToWildemount = 'egw';
    case ElementalEvilPlayersCompanion = 'eepc';
    case AcquisitionsIncorporated = 'acq';
    case LostLaboratoryOfKwalish = 'llk';
    case StrixhavenCurriculumOfChaos = 'scc';
    case TheBookOfManyThings = 'bmt';
    case PlaneScapeAdventuresInTheMultiverse = 'pam';
    case IcewindDaleRimeOfTheFrostmaiden = 'idrf';
    case UnearthedArcana = 'ua';
}
