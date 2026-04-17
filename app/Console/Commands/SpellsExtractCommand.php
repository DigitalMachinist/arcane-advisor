<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Spells\Actions\SpellsExtractAction;
use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;
use Throwable;

final class SpellsExtractCommand extends Command
{
    protected $signature = 'spells:extract
        {--input= : Directory containing scraped raw JSON files (default: storage/app/spells/scraped)}
        {--output= : Directory to write merged YAML files (default: database/spells)}';

    protected $description = 'Extract structured spell fields via LLM and write YAML files';

    public function handle(SpellsExtractAction $action): int
    {
        $inputDir = (string) ($this->option('input') ?: storage_path('app/spells/scraped'));
        $outputDir = (string) ($this->option('output') ?: database_path('spells'));

        if (! is_dir($inputDir)) {
            $this->error("Input directory does not exist: {$inputDir}");

            return self::FAILURE;
        }

        if (! is_dir($outputDir) && ! mkdir($outputDir, 0755, recursive: true)) {
            $this->error("Could not create output directory: {$outputDir}");

            return self::FAILURE;
        }

        $files = glob("{$inputDir}/*.json");

        if ($files === false || $files === []) {
            $this->warn("No JSON files found in: {$inputDir}");

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($files as $file) {
            $slug = basename($file, '.json');

            try {
                $rawSpell = $this->readRawSpell($file);
                $merged = $action->execute($rawSpell);
                $this->writeYaml($merged, $outputDir, $slug);
                $this->line("  Extracted: {$slug}");
                $count++;
            } catch (Throwable $e) {
                $this->error("Failed to extract '{$slug}': {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        $this->info("Extracted {$count} spell(s).");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function readRawSpell(string $path): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException("Could not read file: {$path}");
        }

        $data = json_decode($contents, associative: true);

        if (! is_array($data)) {
            throw new \RuntimeException("Invalid JSON in file: {$path}");
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function writeYaml(array $data, string $outputDir, string $slug): void
    {
        // Remove rawDescription from YAML output — it lives in the markdown body.
        $rawDescription = (string) ($data['rawDescription'] ?? '');
        unset($data['rawDescription'], $data['concentration'], $data['ritual']);

        // Ensure personalityBlurb exists as an empty string placeholder.
        if (! array_key_exists('personalityBlurb', $data)) {
            $data['personalityBlurb'] = '';
        }

        $yaml = Yaml::dump($data, inline: 4, indent: 2);

        // Append the markdown body with the raw description.
        $output = $yaml."\n---\n\n".$rawDescription;

        file_put_contents("{$outputDir}/{$slug}.yaml", $output);
    }
}
