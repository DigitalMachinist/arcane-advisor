<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Spells\Actions\SpellsScrapeAction;
use Illuminate\Console\Command;
use RuntimeException;

final class SpellsScrapeCommand extends Command
{
    protected $signature = 'spells:scrape
        {--dry-run : Log what would be scraped without writing any files}
        {--delay=500 : Milliseconds to wait between requests}
        {--output=storage/app/spells/raw : Directory to write raw JSON files}';

    protected $description = 'Scrape wizard spells from dnd5e.wikidot.com and output raw JSON files';

    public function handle(SpellsScrapeAction $action): int
    {
        $outputDir = (string) $this->option('output');
        $dryRun = (bool) $this->option('dry-run');
        $delayMs = (int) $this->option('delay');

        $this->info($dryRun
            ? "[dry-run] Would scrape wizard spells to: {$outputDir}"
            : "Scraping wizard spells to: {$outputDir}");

        try {
            $action->execute(
                outputDir: $outputDir,
                dryRun: $dryRun,
                delayMs: $delayMs,
            );
        } catch (RuntimeException $e) {
            $this->error('Scrape failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info($dryRun ? '[dry-run] Done.' : 'Done.');

        return self::SUCCESS;
    }
}
