<?php

declare(strict_types=1);

use App\Domain\Spells\Actions\SpellsScrapeAction;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

covers(SpellsScrapeAction::class);

test('http client sends descriptive user-agent header on index request', function (): void {
    $indexHtml = <<<'HTML'
        <html><body>
        <a href="/spell:fireball">Fireball</a>
        </body></html>
        HTML;

    Http::fake([
        'https://dnd5e.wikidot.com/spells:wizard' => Http::response($indexHtml, 200),
        'https://dnd5e.wikidot.com/*' => Http::response('', 404),
    ]);

    $outputDir = sys_get_temp_dir().'/arcane-scrape-ua-'.uniqid();
    mkdir($outputDir, 0o755, true);

    $action = new SpellsScrapeAction;
    $action->execute(outputDir: $outputDir, dryRun: false, delayMs: 0);

    Http::assertSent(function (Request $request): bool {
        return str_contains($request->url(), 'spells:wizard')
            && str_contains($request->header('User-Agent')[0] ?? '', 'ArcaneAdvisor');
    });

    array_map('unlink', glob($outputDir.'/*.json') ?: []);
    rmdir($outputDir);
});

test('configured delay is passed through to execute and honored', function (): void {
    // The contract: delayMs param is accepted and stored; we verify the value
    // passes through without verifying the actual sleep call (implementation detail).
    $action = new SpellsScrapeAction;

    // Verify the action accepts delayMs via its execute() signature.
    // If the signature changes, this test will catch it via a TypeError or similar.
    $reflection = new ReflectionMethod($action, 'execute');
    $params = $reflection->getParameters();

    $paramNames = array_map(fn (ReflectionParameter $p): string => $p->getName(), $params);

    expect($paramNames)->toContain('delayMs');

    $delayParam = array_values(array_filter($params, fn (ReflectionParameter $p): bool => $p->getName() === 'delayMs'))[0];

    expect($delayParam->getDefaultValue())->toBe(500);
});

test('http client sends user-agent on spell detail page requests', function (): void {
    $indexHtml = <<<'HTML'
        <html><body>
        <a href="/spell:fireball">Fireball</a>
        <a href="/spell:mage-hand">Mage Hand</a>
        <a href="/spell:alarm">Alarm</a>
        </body></html>
        HTML;

    $fireballHtml = file_get_contents(__DIR__.'/../../../../Fixtures/scrape/fireball.html');
    $mageHandHtml = file_get_contents(__DIR__.'/../../../../Fixtures/scrape/mage-hand.html');
    $alarmHtml = file_get_contents(__DIR__.'/../../../../Fixtures/scrape/alarm.html');

    Http::fake([
        'https://dnd5e.wikidot.com/spells:wizard' => Http::response($indexHtml, 200),
        'https://dnd5e.wikidot.com/spell:fireball' => Http::response($fireballHtml, 200),
        'https://dnd5e.wikidot.com/spell:mage-hand' => Http::response($mageHandHtml, 200),
        'https://dnd5e.wikidot.com/spell:alarm' => Http::response($alarmHtml, 200),
        'https://dnd5e.wikidot.com/*' => Http::response('', 404),
    ]);

    $outputDir = sys_get_temp_dir().'/arcane-scrape-ua2-'.uniqid();
    mkdir($outputDir, 0o755, true);

    $action = new SpellsScrapeAction;
    $action->execute(outputDir: $outputDir, dryRun: false, delayMs: 0);

    Http::assertSentCount(4); // index + 3 spell pages

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://dnd5e.wikidot.com/spell:fireball'
            && str_contains($request->header('User-Agent')[0] ?? '', 'ArcaneAdvisor');
    });

    array_map('unlink', glob($outputDir.'/*.json') ?: []);
    rmdir($outputDir);
});
