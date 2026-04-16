<?php

declare(strict_types=1);

use App\Console\Commands\SpellsScrapeCommand;
use Illuminate\Support\Facades\Http;

covers(SpellsScrapeCommand::class);

beforeEach(function (): void {
    $this->indexHtml = file_get_contents(__DIR__.'/../../Fixtures/scrape/index.html');
    $this->fireballHtml = file_get_contents(__DIR__.'/../../Fixtures/scrape/fireball.html');
    $this->mageHandHtml = file_get_contents(__DIR__.'/../../Fixtures/scrape/mage-hand.html');
    $this->alarmHtml = file_get_contents(__DIR__.'/../../Fixtures/scrape/alarm.html');
});

test('command exits with code 0 on success', function (): void {
    Http::fake([
        'dnd5e.wikidot.com/spells:wizard' => Http::response($this->indexHtml, 200),
        'dnd5e.wikidot.com/fireball' => Http::response($this->fireballHtml, 200),
        'dnd5e.wikidot.com/mage-hand' => Http::response($this->mageHandHtml, 200),
        'dnd5e.wikidot.com/alarm' => Http::response($this->alarmHtml, 200),
    ]);

    $outputDir = sys_get_temp_dir().'/arcane-cmd-success-'.uniqid();
    mkdir($outputDir, 0o755, true);

    $this->artisan('spells:scrape', [
        '--output' => $outputDir,
        '--delay' => 0,
    ])->assertExitCode(0);

    array_map('unlink', glob($outputDir.'/*.json'));
    rmdir($outputDir);
});

test('command exits with non-zero code on http failure', function (): void {
    Http::fake([
        'dnd5e.wikidot.com/spells:wizard' => Http::response('Not Found', 404),
    ]);

    $outputDir = sys_get_temp_dir().'/arcane-cmd-fail-'.uniqid();
    mkdir($outputDir, 0o755, true);

    $this->artisan('spells:scrape', [
        '--output' => $outputDir,
        '--delay' => 0,
    ])->assertExitCode(1);

    rmdir($outputDir);
});

test('command with --dry-run flag writes no files', function (): void {
    Http::fake([
        'dnd5e.wikidot.com/spells:wizard' => Http::response($this->indexHtml, 200),
        'dnd5e.wikidot.com/fireball' => Http::response($this->fireballHtml, 200),
        'dnd5e.wikidot.com/mage-hand' => Http::response($this->mageHandHtml, 200),
        'dnd5e.wikidot.com/alarm' => Http::response($this->alarmHtml, 200),
    ]);

    $outputDir = sys_get_temp_dir().'/arcane-cmd-dryrun-'.uniqid();
    mkdir($outputDir, 0o755, true);

    $this->artisan('spells:scrape', [
        '--output' => $outputDir,
        '--dry-run' => true,
        '--delay' => 0,
    ])->assertExitCode(0);

    $files = glob($outputDir.'/*.json');
    expect($files)->toBeEmpty();

    rmdir($outputDir);
});

test('command accepts --delay option', function (): void {
    Http::fake([
        'dnd5e.wikidot.com/spells:wizard' => Http::response($this->indexHtml, 200),
        'dnd5e.wikidot.com/fireball' => Http::response($this->fireballHtml, 200),
        'dnd5e.wikidot.com/mage-hand' => Http::response($this->mageHandHtml, 200),
        'dnd5e.wikidot.com/alarm' => Http::response($this->alarmHtml, 200),
    ]);

    $outputDir = sys_get_temp_dir().'/arcane-cmd-delay-'.uniqid();
    mkdir($outputDir, 0o755, true);

    $this->artisan('spells:scrape', [
        '--output' => $outputDir,
        '--delay' => 0,
        '--dry-run' => true,
    ])->assertExitCode(0);

    rmdir($outputDir);
});

test('command uses no live network calls', function (): void {
    Http::fake([
        'dnd5e.wikidot.com/spells:wizard' => Http::response($this->indexHtml, 200),
        'dnd5e.wikidot.com/fireball' => Http::response($this->fireballHtml, 200),
        'dnd5e.wikidot.com/mage-hand' => Http::response($this->mageHandHtml, 200),
        'dnd5e.wikidot.com/alarm' => Http::response($this->alarmHtml, 200),
    ]);

    $outputDir = sys_get_temp_dir().'/arcane-cmd-nolive-'.uniqid();
    mkdir($outputDir, 0o755, true);

    $this->artisan('spells:scrape', [
        '--output' => $outputDir,
        '--delay' => 0,
    ])->assertExitCode(0);

    // All requests were faked — no live network access occurred.
    Http::assertSentCount(4); // index + 3 spell pages

    array_map('unlink', glob($outputDir.'/*.json'));
    rmdir($outputDir);
});
