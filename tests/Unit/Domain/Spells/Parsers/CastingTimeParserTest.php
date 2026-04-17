<?php

declare(strict_types=1);

use App\Domain\Spells\Enums\ActionEconomy;
use App\Domain\Spells\Parsers\CastingTimeParser;

covers(CastingTimeParser::class);

test('"1 action" maps to Action', function (): void {
    $parser = new CastingTimeParser;

    expect($parser->parse('1 action'))->toBe(ActionEconomy::Action);
});

test('"1 bonus action" maps to BonusAction', function (): void {
    $parser = new CastingTimeParser;

    expect($parser->parse('1 bonus action'))->toBe(ActionEconomy::BonusAction);
});

test('"1 reaction, which you take..." maps to Reaction', function (): void {
    $parser = new CastingTimeParser;

    expect($parser->parse('1 reaction, which you take when you or a creature within 60 feet of you falls'))->toBe(ActionEconomy::Reaction);
});

test('"1 reaction" maps to Reaction', function (): void {
    $parser = new CastingTimeParser;

    expect($parser->parse('1 reaction'))->toBe(ActionEconomy::Reaction);
});

test('"1 minute" maps to Minute', function (): void {
    $parser = new CastingTimeParser;

    expect($parser->parse('1 minute'))->toBe(ActionEconomy::Minute);
});

test('"10 minutes" maps to TenMinutes', function (): void {
    $parser = new CastingTimeParser;

    expect($parser->parse('10 minutes'))->toBe(ActionEconomy::TenMinutes);
});

test('"1 hour" maps to Hour', function (): void {
    $parser = new CastingTimeParser;

    expect($parser->parse('1 hour'))->toBe(ActionEconomy::Hour);
});

test('"8 hours" maps to Longer', function (): void {
    $parser = new CastingTimeParser;

    expect($parser->parse('8 hours'))->toBe(ActionEconomy::Longer);
});

test('unknown value maps to Longer as fallback', function (): void {
    $parser = new CastingTimeParser;

    expect($parser->parse('some unknown casting time'))->toBe(ActionEconomy::Longer);
});

test('"24 hours" maps to Longer', function (): void {
    $parser = new CastingTimeParser;

    expect($parser->parse('24 hours'))->toBe(ActionEconomy::Longer);
});
