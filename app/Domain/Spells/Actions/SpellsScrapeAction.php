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

    /** @var array<string, string> Maps wikidot source strings to SourceCode values. */
    private const SOURCE_MAP = [
        "Player's Handbook" => 'phb',
        "Xanathar's Guide to Everything" => 'xge',
        "Tasha's Cauldron of Everything" => 'tce',
        "Sword Coast Adventurer's Guide" => 'scag',
        "Fizban's Treasury of Dragons" => 'ftd',
        "Spelljammer: Adventures in Space - Astral Adventurer's Guide" => 'aag',
        "Explorer's Guide to Wildemount" => 'egw',
        "Elemental Evil Player's Companion" => 'eepc',
        'Acquisitions Inc.' => 'acq',
        'Lost Laboratory of Kwalish' => 'llk',
        'Strixhaven: A Curriculum of Chaos' => 'scc',
        'The Book of Many Things' => 'bmt',
        'Planescape - Adventures in the Multiverse' => 'pam',
        'Icewind Dale - Rime of the Frostmaiden' => 'idrf',
    ];

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

            $url = self::BASE_URL.'/spell:'.$slug;

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

        libxml_use_internal_errors(true);
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        /** @var \DOMNodeList<\DOMElement> $nodes */
        $nodes = $xpath->query('//a[@href]');

        $slugs = [];

        foreach ($nodes as $node) {
            $href = $node->getAttribute('href');

            // Real wikidot spell hrefs are /spell:slug-name.
            if (! preg_match('#^/spell:([a-z0-9][a-z0-9\-]*)$#', $href, $matches)) {
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
     *     sources: list<array{code: string, page: null}>,
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

        // Spell name from the page-title header div (outside #page-content on real wikidot pages).
        /** @var \DOMNodeList<\DOMElement> $titleNodes */
        $titleNodes = $xpath->query('//div[contains(@class,"page-title")]//span');
        $name = $titleNodes->count() > 0 ? trim($titleNodes->item(0)->textContent) : $slug;

        // Level and school from <em> inside a <p>.
        /** @var \DOMNodeList<\DOMElement> $emNodes */
        $emNodes = $xpath->query('//div[@id="page-content"]//p/em');
        $levelSchoolText = $emNodes->count() > 0 ? trim($emNodes->item(0)->textContent) : '';

        [$level, $school] = $this->parseLevelAndSchool($levelSchoolText);

        // Stat block: a <p> containing <strong>Casting Time:</strong> with <br /> separators.
        $statBlock = $this->parseStatBlock($xpath);

        $castingTime = $statBlock['casting time'] ?? '';
        $range = $statBlock['range'] ?? '';
        $componentsRaw = $statBlock['components'] ?? '';
        $duration = $statBlock['duration'] ?? '';

        $components = $this->parseComponents($componentsRaw);
        $concentration = str_contains(strtolower($duration), 'concentration');
        $ritual = str_contains(strtolower($levelSchoolText), 'ritual');

        // Classes from the "Spell Lists." paragraph's anchor links.
        $classes = $this->parseSpellListClasses($xpath);

        // Source book(s) from the "Source: ..." paragraph.
        $sources = $this->parseSource($xpath);

        // Description: all <p> nodes after the stat block, excluding the Spell Lists paragraph.
        $description = $this->parseDescription($xpath);

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
            'sources' => $sources,
            'description' => $description,
        ];
    }

    /** @return array<string, string> */
    private function parseStatBlock(\DOMXPath $xpath): array
    {
        /** @var \DOMNodeList<\DOMElement> $pNodes */
        $pNodes = $xpath->query(
            '//div[@id="page-content"]//p[.//strong[contains(., "Casting Time")]]',
        );

        if ($pNodes->count() === 0) {
            return [];
        }

        $statBlock = [];
        $currentLabel = null;
        $currentValue = '';

        foreach ($pNodes->item(0)->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'strong') {
                if ($currentLabel !== null) {
                    $statBlock[strtolower(trim($currentLabel))] = trim($currentValue);
                }

                $currentLabel = rtrim(trim($child->textContent), ':');
                $currentValue = '';
            } elseif ($child->nodeType === XML_TEXT_NODE) {
                $currentValue .= $child->textContent;
            }
            // <br /> nodes are separators; the next <strong> flushes the current value.
        }

        if ($currentLabel !== null) {
            $statBlock[strtolower(trim($currentLabel))] = trim($currentValue);
        }

        return $statBlock;
    }

    /** @return list<string> */
    private function parseSpellListClasses(\DOMXPath $xpath): array
    {
        // The "Spell Lists." paragraph links to each class spell list.
        // href format: http://dnd5e.wikidot.com/spells:wizard → extract "wizard".
        /** @var \DOMNodeList<\DOMElement> $pNodes */
        $pNodes = $xpath->query(
            '//div[@id="page-content"]//p[contains(., "Spell Lists")]',
        );

        if ($pNodes->count() === 0) {
            return [];
        }

        $classes = [];

        /** @var \DOMNodeList<\DOMElement> $anchors */
        $anchors = $xpath->query('.//a[@href]', $pNodes->item(0));

        foreach ($anchors as $a) {
            $href = $a->getAttribute('href');

            if (preg_match('#/spells:([a-z\-]+)$#', $href, $m)) {
                $classes[] = $m[1];
            }
        }

        return $classes;
    }

    /** @return list<array{code: string, page: null}> */
    private function parseSource(\DOMXPath $xpath): array
    {
        /** @var \DOMNodeList<\DOMElement> $pNodes */
        $pNodes = $xpath->query('//div[@id="page-content"]//p[starts-with(normalize-space(.), "Source:")]');

        if ($pNodes->count() === 0) {
            return [];
        }

        $raw = trim(preg_replace('/^Source:\s*/i', '', trim($pNodes->item(0)->textContent)) ?? '');

        $sources = [];

        foreach (explode('/', $raw) as $part) {
            $part = trim($part);

            $code = self::SOURCE_MAP[$part]
                ?? (str_starts_with($part, 'Unearthed Arcana') ? 'ua' : null);

            if ($code !== null) {
                $sources[] = ['code' => $code, 'page' => null];
            } else {
                Log::warning('Unknown spell source encountered during scrape', ['source' => $part]);
            }
        }

        return $sources;
    }

    private function parseDescription(\DOMXPath $xpath): string
    {
        // All <p> nodes after the stat block paragraph, excluding the Spell Lists paragraph.
        /** @var \DOMNodeList<\DOMElement> $pNodes */
        $pNodes = $xpath->query(
            '//div[@id="page-content"]//p[.//strong[contains(., "Casting Time")]]/following-sibling::p[not(contains(., "Spell Lists"))]',
        );

        $parts = [];

        foreach ($pNodes as $p) {
            $text = trim($p->textContent);

            if ($text !== '') {
                $parts[] = $text;
            }
        }

        return implode("\n\n", $parts);
    }

    /** @return array{0: int, 1: string} */
    private function parseLevelAndSchool(string $text): array
    {
        $level = 0;
        $school = 'unknown';

        $text = strtolower($text);
        $text = str_replace('(ritual)', '', $text);
        $text = trim($text);

        if (str_contains($text, 'cantrip')) {
            $level = 0;

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
}
