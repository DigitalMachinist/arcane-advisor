<?php

declare(strict_types=1);

use App\Domain\Spells\Actions\SpellsScrapeAction;

covers(SpellsScrapeAction::class);

test('wizard index page yields all spell slugs including fireball, mage-hand, and alarm', function (): void {
    $indexHtml = file_get_contents(__DIR__.'/../../../../Fixtures/scrape/index.html');

    expect($indexHtml)->not->toBeFalse();

    $action = new SpellsScrapeAction;
    $slugs = $action->parseIndexSlugs($indexHtml);

    expect($slugs)
        ->toBeArray()
        ->toContain('fireball')
        ->toContain('mage-hand')
        ->toContain('alarm');
});

test('wizard index parser extracts slug from href attribute', function (): void {
    $html = <<<'HTML'
        <div id="page-content">
            <a href="/detect-magic">Detect Magic</a>
            <a href="/shield">Shield</a>
        </div>
        HTML;

    $action = new SpellsScrapeAction;
    $slugs = $action->parseIndexSlugs($html);

    expect($slugs)
        ->toContain('detect-magic')
        ->toContain('shield');
});

test('wizard index parser ignores non-spell hrefs', function (): void {
    $html = <<<'HTML'
        <div id="page-content">
            <a href="https://external.com/page">External</a>
            <a href="/fireball">Fireball</a>
            <a href="#anchor">Anchor</a>
            <a href="/spells:wizard">Index</a>
        </div>
        HTML;

    $action = new SpellsScrapeAction;
    $slugs = $action->parseIndexSlugs($html);

    expect($slugs)
        ->toContain('fireball')
        ->not->toContain('spells:wizard')
        ->not->toContain('#anchor');
});
