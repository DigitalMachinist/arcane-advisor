<?php

declare(strict_types=1);

namespace App\Domain\Spells\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class SpellsScrapeAction
{
    private const BASE_URL = 'https://dnd5e.wikidot.com';

    private const INDEX_PATH = '/spells:wizard';

    private const USER_AGENT = 'ArcaneAdvisor/1.0 (https://github.com/DigitalMachinist/arcane-advisor; spell scraper)';

    public function execute(string $outputDir, bool $dryRun = false, int $delayMs = 500): void
    {
        $indexResponse = Http::withHeaders(['User-Agent' => self::USER_AGENT])
            ->get(self::BASE_URL.self::INDEX_PATH);

        if (! $indexResponse->successful()) {
            throw new RuntimeException(
                sprintf('Failed to fetch wizard spell index: HTTP %d', $indexResponse->status()),
            );
        }

        $slugs = $this->parseIndexSlugs($indexResponse->body());

        if (! $dryRun && ! is_dir($outputDir)) {
            mkdir($outputDir, 0o755, true);
        }

        foreach ($slugs as $slug) {
            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }

            $url = self::BASE_URL.'/'.$slug;

            if ($dryRun) {
                Log::info('[dry-run] Would fetch spell', [
                    'slug' => $slug,
                    'url' => $url,
                ]);

                continue;
            }

            $detailResponse = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->get($url);

            if (! $detailResponse->successful()) {
                Log::warning('Failed to fetch spell detail page', [
                    'slug' => $slug,
                    'status' => $detailResponse->status(),
                ]);

                continue;
            }

            $record = $this->parseDetailPage($detailResponse->body(), $slug);

            file_put_contents(
                $outputDir.'/'.$slug.'.json',
                json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            );
        }
    }

    /** @return list<string> */
    public function parseIndexSlugs(string $html): array
    {
        $dom = new \DOMDocument;

        // Suppress HTML5 parsing warnings.
        libxml_use_internal_errors(true);
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Find all <a href="..."> elements that look like spell paths (single-segment, lowercase, hyphenated).
        /** @var \DOMNodeList<\DOMElement> $nodes */
        $nodes = $xpath->query('//a[@href]');

        $slugs = [];

        foreach ($nodes as $node) {
            $href = $node->getAttribute('href');

            // Valid spell hrefs are /slug-name — leading slash, no extra slashes, no colons, no dots.
            if (! preg_match('#^/([a-z0-9][a-z0-9\-]*)$#', $href, $matches)) {
                continue;
            }

            $slugs[] = $matches[1];
        }

        return array_values(array_unique($slugs));
    }

    /**
     * @return array{
     *     name: string,
     *     level: int,
     *     school: string,
     *     castingTime: string,
     *     range: string,
     *     components: array{verbal: bool, somatic: bool, material: string|null},
     *     duration: string,
     *     concentration: bool,
     *     ritual: bool,
     *     classes: list<string>,
     *     description: string,
     * }
     */
    public function parseDetailPage(string $html, string $slug): array
    {
        $dom = new \DOMDocument;

        libxml_use_internal_errors(true);
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Extract the spell name from <h1>.
        /** @var \DOMNodeList<\DOMElement> $h1Nodes */
        $h1Nodes = $xpath->query('//div[@id="page-content"]//h1');
        $name = $h1Nodes->count() > 0 ? trim($h1Nodes->item(0)->textContent) : $slug;

        // Extract the level/school line from <em> inside a <p>.
        /** @var \DOMNodeList<\DOMElement> $emNodes */
        $emNodes = $xpath->query('//div[@id="page-content"]//p/em');
        $levelSchoolText = $emNodes->count() > 0 ? trim($emNodes->item(0)->textContent) : '';

        [$level, $school] = $this->parseLevelAndSchool($levelSchoolText);

        // Extract stat block rows from the table.
        /** @var \DOMNodeList<\DOMElement> $rowNodes */
        $rowNodes = $xpath->query('//div[@id="page-content"]//table//tr/td');

        $statBlock = [];

        foreach ($rowNodes as $td) {
            $text = trim($td->textContent);
            if (str_contains($text, ':')) {
                [$label, $value] = explode(':', $text, 2);
                $statBlock[strtolower(trim($label))] = trim($value);
            }
        }

        $castingTime = $statBlock['casting time'] ?? '';
        $range = $statBlock['range'] ?? '';
        $componentsRaw = $statBlock['components'] ?? '';
        $duration = $statBlock['duration'] ?? '';
        $classesRaw = $statBlock['classes'] ?? '';

        $components = $this->parseComponents($componentsRaw);
        $concentration = str_contains(strtolower($duration), 'concentration');
        $ritual = str_contains(strtolower($castingTime), 'ritual')
            || str_contains(strtolower($levelSchoolText), 'ritual');

        $classes = array_values(array_filter(
            array_map(
                fn (string $c): string => strtolower(trim($c)),
                explode(',', $classesRaw),
            ),
            fn (string $c): bool => $c !== '',
        ));

        // Extract description text: all <p> nodes after the stat block table.
        $description = $this->parseDescription($xpath, $name);

        return [
            'name' => $name,
            'level' => $level,
            'school' => $school,
            'castingTime' => $castingTime,
            'range' => $range,
            'components' => $components,
            'duration' => $duration,
            'concentration' => $concentration,
            'ritual' => $ritual,
            'classes' => $classes,
            'description' => $description,
        ];
    }

    /** @return array{0: int, 1: string} */
    private function parseLevelAndSchool(string $text): array
    {
        // Examples: "3rd-level evocation", "Conjuration cantrip", "1st-level abjuration (ritual)"
        $level = 0;
        $school = 'unknown';

        $text = strtolower($text);

        // Strip ritual tag from school line for parsing.
        $text = str_replace('(ritual)', '', $text);
        $text = trim($text);

        if (str_contains($text, 'cantrip')) {
            $level = 0;
            // School is before "cantrip".
            if (preg_match('/([a-z]+)\s+cantrip/', $text, $m)) {
                $school = $m[1];
            }
        } elseif (preg_match('/(\d+)(?:st|nd|rd|th)-level\s+([a-z]+)/', $text, $m)) {
            $level = (int) $m[1];
            $school = $m[2];
        }

        return [$level, $school];
    }

    /**
     * @return array{verbal: bool, somatic: bool, material: string|null}
     */
    private function parseComponents(string $raw): array
    {
        // Examples: "V, S, M (a tiny ball of bat guano and sulfur)", "V, S", "S, M (some component)"
        $verbal = str_contains($raw, 'V');
        $somatic = str_contains($raw, 'S');
        $material = null;

        if (preg_match('/M\s*\(([^)]+)\)/', $raw, $m)) {
            $material = trim($m[1]);
        }

        return [
            'verbal' => $verbal,
            'somatic' => $somatic,
            'material' => $material,
        ];
    }

    private function parseDescription(\DOMXPath $xpath, string $spellName): string
    {
        // Collect all <p> nodes after the stat block table within page-content.
        // We skip the first <p> which typically holds the level/school em tag.
        /** @var \DOMNodeList<\DOMElement> $pNodes */
        $pNodes = $xpath->query('//div[@id="page-content"]//table/following-sibling::p');

        $parts = [];

        foreach ($pNodes as $p) {
            $text = trim($p->textContent);
            if ($text !== '') {
                $parts[] = $text;
            }
        }

        return implode("\n\n", $parts);
    }
}
