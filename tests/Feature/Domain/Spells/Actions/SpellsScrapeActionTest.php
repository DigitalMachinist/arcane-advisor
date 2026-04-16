<?php

declare(strict_types=1);

use App\Domain\Spells\Actions\SpellsScrapeAction;
use Illuminate\Support\Facades\Http;

covers(SpellsScrapeAction::class);

beforeEach(function (): void {
    $this->indexHtml = file_get_contents(__DIR__.'/../../../../Fixtures/scrape/index.html');
    $this->fireballHtml = file_get_contents(__DIR__.'/../../../../Fixtures/scrape/fireball.html');
    $this->mageHandHtml = file_get_contents(__DIR__.'/../../../../Fixtures/scrape/mage-hand.html');
    $this->alarmHtml = file_get_contents(__DIR__.'/../../../../Fixtures/scrape/alarm.html');
});

test('scraping fireball detail page extracts expected raw fields', function (): void {
    Http::fake([
        'dnd5e.wikidot.com/spells:wizard' => Http::response($this->indexHtml, 200),
        'dnd5e.wikidot.com/fireball' => Http::response($this->fireballHtml, 200),
        'dnd5e.wikidot.com/mage-hand' => Http::response($this->mageHandHtml, 200),
        'dnd5e.wikidot.com/alarm' => Http::response($this->alarmHtml, 200),
    ]);

    $outputDir = sys_get_temp_dir().'/arcane-scrape-test-'.uniqid();
    mkdir($outputDir, 0o755, true);

    $action = new SpellsScrapeAction;
    $action->execute(outputDir: $outputDir, dryRun: false, delayMs: 0);

    $fireballJson = json_decode(file_get_contents($outputDir.'/fireball.json'), associative: true);

    expect($fireballJson)
        ->toBeArray()
        ->toHaveKey('name')
        ->toHaveKey('level')
        ->toHaveKey('school')
        ->toHaveKey('castingTime')
        ->toHaveKey('range')
        ->toHaveKey('components')
        ->toHaveKey('duration')
        ->toHaveKey('concentration')
        ->toHaveKey('ritual')
        ->toHaveKey('classes')
        ->toHaveKey('description');

    expect($fireballJson['name'])->toBe('Fireball');
    expect($fireballJson['level'])->toBe(3);
    expect($fireballJson['school'])->toBe('evocation');
    expect($fireballJson['castingTime'])->toBe('1 action');
    expect($fireballJson['range'])->toBe('150 feet');
    expect($fireballJson['duration'])->toBe('Instantaneous');
    expect($fireballJson['concentration'])->toBeFalse();
    expect($fireballJson['ritual'])->toBeFalse();
    expect($fireballJson['components']['verbal'])->toBeTrue();
    expect($fireballJson['components']['somatic'])->toBeTrue();
    expect($fireballJson['components']['material'])->toBe('a tiny ball of bat guano and sulfur');
    expect($fireballJson['classes'])->toContain('wizard');
    expect($fireballJson['description'])->toContain('20-foot-radius sphere');

    // cleanup
    array_map('unlink', glob($outputDir.'/*.json'));
    rmdir($outputDir);
});

test('scraping mage-hand detail page extracts cantrip with no material component', function (): void {
    Http::fake([
        'dnd5e.wikidot.com/spells:wizard' => Http::response($this->indexHtml, 200),
        'dnd5e.wikidot.com/fireball' => Http::response($this->fireballHtml, 200),
        'dnd5e.wikidot.com/mage-hand' => Http::response($this->mageHandHtml, 200),
        'dnd5e.wikidot.com/alarm' => Http::response($this->alarmHtml, 200),
    ]);

    $outputDir = sys_get_temp_dir().'/arcane-scrape-test-'.uniqid();
    mkdir($outputDir, 0o755, true);

    $action = new SpellsScrapeAction;
    $action->execute(outputDir: $outputDir, dryRun: false, delayMs: 0);

    $mageHandJson = json_decode(file_get_contents($outputDir.'/mage-hand.json'), associative: true);

    expect($mageHandJson['name'])->toBe('Mage Hand');
    expect($mageHandJson['level'])->toBe(0);
    expect($mageHandJson['school'])->toBe('conjuration');
    expect($mageHandJson['concentration'])->toBeFalse();
    expect($mageHandJson['ritual'])->toBeFalse();
    expect($mageHandJson['components']['verbal'])->toBeTrue();
    expect($mageHandJson['components']['somatic'])->toBeTrue();
    expect($mageHandJson['components']['material'])->toBeNull();
    expect($mageHandJson['classes'])->toContain('wizard');

    // cleanup
    array_map('unlink', glob($outputDir.'/*.json'));
    rmdir($outputDir);
});

test('scraping alarm detail page detects ritual flag', function (): void {
    Http::fake([
        'dnd5e.wikidot.com/spells:wizard' => Http::response($this->indexHtml, 200),
        'dnd5e.wikidot.com/fireball' => Http::response($this->fireballHtml, 200),
        'dnd5e.wikidot.com/mage-hand' => Http::response($this->mageHandHtml, 200),
        'dnd5e.wikidot.com/alarm' => Http::response($this->alarmHtml, 200),
    ]);

    $outputDir = sys_get_temp_dir().'/arcane-scrape-test-'.uniqid();
    mkdir($outputDir, 0o755, true);

    $action = new SpellsScrapeAction;
    $action->execute(outputDir: $outputDir, dryRun: false, delayMs: 0);

    $alarmJson = json_decode(file_get_contents($outputDir.'/alarm.json'), associative: true);

    expect($alarmJson['name'])->toBe('Alarm');
    expect($alarmJson['level'])->toBe(1);
    expect($alarmJson['school'])->toBe('abjuration');
    expect($alarmJson['ritual'])->toBeTrue();
    expect($alarmJson['concentration'])->toBeFalse();

    // cleanup
    array_map('unlink', glob($outputDir.'/*.json'));
    rmdir($outputDir);
});

test('scraping in dry-run mode writes no files', function (): void {
    Http::fake([
        'dnd5e.wikidot.com/spells:wizard' => Http::response($this->indexHtml, 200),
        'dnd5e.wikidot.com/fireball' => Http::response($this->fireballHtml, 200),
        'dnd5e.wikidot.com/mage-hand' => Http::response($this->mageHandHtml, 200),
        'dnd5e.wikidot.com/alarm' => Http::response($this->alarmHtml, 200),
    ]);

    $outputDir = sys_get_temp_dir().'/arcane-scrape-dryrun-'.uniqid();
    mkdir($outputDir, 0o755, true);

    $action = new SpellsScrapeAction;
    $action->execute(outputDir: $outputDir, dryRun: true, delayMs: 0);

    $files = glob($outputDir.'/*.json');
    expect($files)->toBeEmpty();

    rmdir($outputDir);
});

test('parse detail page extracts concentration from duration field', function (): void {
    $html = <<<'HTML'
        <div id="page-content">
        <h1>Concentration Spell</h1>
        <p><em>1st-level illusion</em></p>
        <table class="wiki-content-table">
        <tr><td><strong>Casting Time:</strong> 1 action</td></tr>
        <tr><td><strong>Range:</strong> 30 feet</td></tr>
        <tr><td><strong>Components:</strong> V, S</td></tr>
        <tr><td><strong>Duration:</strong> Concentration, up to 1 minute</td></tr>
        <tr><td><strong>Classes:</strong> Wizard</td></tr>
        </table>
        <p>A test concentration spell.</p>
        </div>
        HTML;

    $action = new SpellsScrapeAction;
    $record = $action->parseDetailPage($html, 'concentration-spell');

    expect($record['concentration'])->toBeTrue();
    expect($record['duration'])->toBe('Concentration, up to 1 minute');
});
