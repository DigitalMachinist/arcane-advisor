<?php

declare(strict_types=1);

namespace App\Domain\Spells\Parsers;

use App\Domain\Spells\Enums\DurationCategory;

final class DurationParser
{
    private const string CONCENTRATION_PREFIX = 'Concentration, ';

    public function parse(string $duration): DurationCategory
    {
        // Strip leading "Concentration, " prefix before mapping
        $normalized = str_starts_with($duration, self::CONCENTRATION_PREFIX)
            ? substr($duration, strlen(self::CONCENTRATION_PREFIX))
            : $duration;

        return match (true) {
            strcasecmp($normalized, 'Instantaneous') === 0 => DurationCategory::Instantaneous,
            strcasecmp($normalized, 'Until dispelled') === 0 => DurationCategory::UntilDispelled,
            strcasecmp($normalized, 'Permanent') === 0 => DurationCategory::Permanent,
            default => DurationCategory::Timed,
        };
    }
}
