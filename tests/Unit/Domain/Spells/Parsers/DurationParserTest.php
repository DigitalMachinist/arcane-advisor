<?php

declare(strict_types=1);

use App\Domain\Spells\Enums\DurationCategory;
use App\Domain\Spells\Parsers\DurationParser;

covers(DurationParser::class);

test('"Instantaneous" maps to Instantaneous', function (): void {
    $parser = new DurationParser;

    expect($parser->parse('Instantaneous'))->toBe(DurationCategory::Instantaneous);
});

test('"Until dispelled" maps to UntilDispelled', function (): void {
    $parser = new DurationParser;

    expect($parser->parse('Until dispelled'))->toBe(DurationCategory::UntilDispelled);
});

test('"Permanent" maps to Permanent', function (): void {
    $parser = new DurationParser;

    expect($parser->parse('Permanent'))->toBe(DurationCategory::Permanent);
});

test('"1 minute" maps to Timed', function (): void {
    $parser = new DurationParser;

    expect($parser->parse('1 minute'))->toBe(DurationCategory::Timed);
});

test('"8 hours" maps to Timed', function (): void {
    $parser = new DurationParser;

    expect($parser->parse('8 hours'))->toBe(DurationCategory::Timed);
});

test('"1 hour" maps to Timed', function (): void {
    $parser = new DurationParser;

    expect($parser->parse('1 hour'))->toBe(DurationCategory::Timed);
});

test('"24 hours" maps to Timed', function (): void {
    $parser = new DurationParser;

    expect($parser->parse('24 hours'))->toBe(DurationCategory::Timed);
});

test('"Concentration, up to 1 hour" strips prefix and maps to Timed', function (): void {
    $parser = new DurationParser;

    expect($parser->parse('Concentration, up to 1 hour'))->toBe(DurationCategory::Timed);
});

test('"Concentration, up to 10 minutes" strips prefix and maps to Timed', function (): void {
    $parser = new DurationParser;

    expect($parser->parse('Concentration, up to 10 minutes'))->toBe(DurationCategory::Timed);
});

test('"Concentration, until dispelled" strips prefix and maps to UntilDispelled', function (): void {
    $parser = new DurationParser;

    expect($parser->parse('Concentration, until dispelled'))->toBe(DurationCategory::UntilDispelled);
});

test('"Up to 1 minute" maps to Timed', function (): void {
    $parser = new DurationParser;

    expect($parser->parse('Up to 1 minute'))->toBe(DurationCategory::Timed);
});
