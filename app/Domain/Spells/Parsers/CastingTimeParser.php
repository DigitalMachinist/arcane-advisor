<?php

declare(strict_types=1);

namespace App\Domain\Spells\Parsers;

use App\Domain\Spells\Enums\ActionEconomy;

final class CastingTimeParser
{
    public function parse(string $castingTime): ActionEconomy
    {
        return match (true) {
            $castingTime === '1 action' => ActionEconomy::Action,
            $castingTime === '1 bonus action' => ActionEconomy::BonusAction,
            str_starts_with($castingTime, '1 reaction') => ActionEconomy::Reaction,
            $castingTime === '1 minute' => ActionEconomy::Minute,
            $castingTime === '10 minutes' => ActionEconomy::TenMinutes,
            $castingTime === '1 hour' => ActionEconomy::Hour,
            default => ActionEconomy::Longer,
        };
    }
}
