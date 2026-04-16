<?php

declare(strict_types=1);

use App\Domain\Spells\Data\SpellData;
use App\Domain\Spells\YamlLoader;

test('loader returns a SpellData DTO, not a raw array', function (): void {
    $loader = new YamlLoader;
    $path = database_path('spells/fireball.yaml');

    $result = $loader->load($path);

    expect($result)->toBeInstanceOf(SpellData::class);
});

test('loader returns correct SpellData for fireball', function (): void {
    $loader = new YamlLoader;

    $spell = $loader->load(database_path('spells/fireball.yaml'));

    expect($spell->slug)->toBe('fireball')
        ->and($spell->name)->toBe('Fireball')
        ->and($spell->level)->toBe(3)
        ->and($spell->school)->toBe('evocation');
});

test('loader returns correct SpellData for mage-hand', function (): void {
    $loader = new YamlLoader;

    $spell = $loader->load(database_path('spells/mage-hand.yaml'));

    expect($spell->slug)->toBe('mage-hand')
        ->and($spell->level)->toBe(0)
        ->and($spell->damage)->toBe([]);
});

test('loader returns correct SpellData for alarm', function (): void {
    $loader = new YamlLoader;

    $spell = $loader->load(database_path('spells/alarm.yaml'));

    expect($spell->slug)->toBe('alarm')
        ->and($spell->qualifiers)->toContain('ritual')
        ->and($spell->savingThrow)->toBeNull();
});

test('loader throws when given a path to a non-existent file', function (): void {
    $loader = new YamlLoader;

    expect(fn (): SpellData => $loader->load('/tmp/no-such-file.yaml'))
        ->toThrow(RuntimeException::class, 'Spell YAML file not found');
});

test('loader throws when given invalid YAML', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'spell_test_');
    file_put_contents($path, "name: [\ninvalid yaml");

    try {
        expect(fn (): SpellData => (new YamlLoader)->load($path))
            ->toThrow(RuntimeException::class);
    } finally {
        unlink($path);
    }
});

test('loader throws when YAML does not pass schema validation', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'spell_test_');
    file_put_contents($path, "slug: bad-spell\nname: Bad Spell\n");

    try {
        expect(fn (): SpellData => (new YamlLoader)->load($path))
            ->toThrow(RuntimeException::class, 'Spell YAML validation failed');
    } finally {
        unlink($path);
    }
});

test('mage-hand SpellData has null savingThrow and null attackRoll', function (): void {
    $spell = (new YamlLoader)->load(database_path('spells/mage-hand.yaml'));

    expect($spell->savingThrow)->toBeNull()
        ->and($spell->attackRoll)->toBeNull()
        ->and($spell->areaShape)->toBeNull()
        ->and($spell->areaSize)->toBeNull();
});

test('fireball SpellData has a non-empty damage array', function (): void {
    $spell = (new YamlLoader)->load(database_path('spells/fireball.yaml'));

    expect($spell->damage)->not->toBeEmpty()
        ->and($spell->damage[0]['dice'])->toBe('8d6')
        ->and($spell->damage[0]['type'])->toBe('fire');
});
