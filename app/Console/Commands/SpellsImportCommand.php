<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Spells\Actions\SpellsImportAction;
use Illuminate\Console\Command;
use Throwable;

class SpellsImportCommand extends Command
{
    protected $signature = 'spells:import';

    protected $description = 'Import all spell YAML files from database/spells/ into the database';

    public function handle(SpellsImportAction $action): int
    {
        try {
            $count = $action->execute(database_path('spells'));

            $this->info("Imported {$count} spell(s).");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Import failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
