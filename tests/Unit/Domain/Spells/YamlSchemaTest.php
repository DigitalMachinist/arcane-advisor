<?php

declare(strict_types=1);

use App\Domain\Spells\Validation\SpellYamlSchema;

// ─── Fixture helpers ──────────────────────────────────────────────────────────

function validFireballData(): array
{
    return [
        'slug' => 'fireball',
        'name' => 'Fireball',
        'level' => 3,
        'school' => 'evocation',
        'castingTime' => '1 action',
        'range' => '150 feet',
        'components' => [
            'verbal' => true,
            'somatic' => true,
            'material' => 'a tiny ball of bat guano and sulfur',
        ],
        'duration' => 'Instantaneous',
        'qualifiers' => [],
        'classes' => ['wizard', 'sorcerer'],
        'damage' => [
            ['dice' => '8d6', 'type' => 'fire'],
        ],
        'conditions' => [],
        'targeting' => 'point',
        'areaShape' => 'sphere',
        'areaSize' => '20 feet',
        'savingThrow' => ['ability' => 'dexterity'],
        'attackRoll' => null,
        'combatRoles' => ['areaDamage'],
        'utilities' => [],
        'sources' => [
            ['code' => 'phb', 'page' => 241],
        ],
        'personalityBlurb' => '',
    ];
}

function validMageHandData(): array
{
    return [
        'slug' => 'mage-hand',
        'name' => 'Mage Hand',
        'level' => 0,
        'school' => 'conjuration',
        'castingTime' => '1 action',
        'range' => '30 feet',
        'components' => [
            'verbal' => true,
            'somatic' => true,
            'material' => null,
        ],
        'duration' => '1 minute',
        'qualifiers' => [],
        'classes' => ['wizard', 'sorcerer', 'bard', 'warlock', 'artificer'],
        'damage' => [],
        'conditions' => [],
        'targeting' => 'point',
        'areaShape' => null,
        'areaSize' => null,
        'savingThrow' => null,
        'attackRoll' => null,
        'combatRoles' => [],
        'utilities' => ['create', 'explore'],
        'sources' => [
            ['code' => 'phb', 'page' => 256],
        ],
        'personalityBlurb' => '',
    ];
}

function validAlarmData(): array
{
    return [
        'slug' => 'alarm',
        'name' => 'Alarm',
        'level' => 1,
        'school' => 'abjuration',
        'castingTime' => '1 minute',
        'range' => '30 feet',
        'components' => [
            'verbal' => true,
            'somatic' => true,
            'material' => 'a tiny bell and a piece of fine silver wire',
        ],
        'duration' => '8 hours',
        'qualifiers' => ['ritual'],
        'classes' => ['wizard', 'ranger', 'artificer'],
        'damage' => [],
        'conditions' => [],
        'targeting' => 'point',
        'areaShape' => null,
        'areaSize' => null,
        'savingThrow' => null,
        'attackRoll' => null,
        'combatRoles' => [],
        'utilities' => ['ward'],
        'sources' => [
            ['code' => 'phb', 'page' => 211],
        ],
        'personalityBlurb' => '',
    ];
}

// ─── Fixture YAML file loading ────────────────────────────────────────────────

test('fireball.yaml validates successfully', function (): void {
    $path = database_path('spells/fireball.yaml');
    $schema = new SpellYamlSchema;

    $result = $schema->validateFile($path);

    expect($result->isValid())->toBeTrue();
});

test('mage-hand.yaml validates successfully', function (): void {
    $path = database_path('spells/mage-hand.yaml');
    $schema = new SpellYamlSchema;

    $result = $schema->validateFile($path);

    expect($result->isValid())->toBeTrue();
});

test('alarm.yaml validates successfully', function (): void {
    $path = database_path('spells/alarm.yaml');
    $schema = new SpellYamlSchema;

    $result = $schema->validateFile($path);

    expect($result->isValid())->toBeTrue();
});

// ─── Failure mode: missing required key ───────────────────────────────────────

test('rejects data missing the slug key', function (): void {
    $data = validFireballData();
    unset($data['slug']);

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Missing required field: 'slug'");
});

test('rejects data missing the name key', function (): void {
    $data = validFireballData();
    unset($data['name']);

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Missing required field: 'name'");
});

test('rejects data missing the level key', function (): void {
    $data = validFireballData();
    unset($data['level']);

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Missing required field: 'level'");
});

test('rejects data missing the components key', function (): void {
    $data = validFireballData();
    unset($data['components']);

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Missing required field: 'components'");
});

test('rejects data missing the sources key', function (): void {
    $data = validFireballData();
    unset($data['sources']);

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Missing required field: 'sources'");
});

// ─── Failure mode: bad enum value ────────────────────────────────────────────

test('rejects an unknown school enum value', function (): void {
    $data = validFireballData();
    $data['school'] = 'pyromancy';

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Invalid value 'pyromancy' for field 'school'");
});

test('rejects an unknown targeting enum value', function (): void {
    $data = validFireballData();
    $data['targeting'] = 'everything';

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Invalid value 'everything' for field 'targeting'");
});

test('rejects an unknown qualifier in the qualifiers array', function (): void {
    $data = validFireballData();
    $data['qualifiers'] = ['concentration', 'cursed'];

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Invalid value 'cursed' in array field 'qualifiers'");
});

test('rejects an unknown source class in the classes array', function (): void {
    $data = validFireballData();
    $data['classes'] = ['wizard', 'necromancer'];

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Invalid value 'necromancer' in array field 'classes'");
});

test('rejects an unknown damage type in the damage array', function (): void {
    $data = validFireballData();
    $data['damage'] = [['dice' => '8d6', 'type' => 'arcane']];

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Invalid value 'arcane' for field 'damage[0].type'");
});

test('rejects an unknown source code', function (): void {
    $data = validFireballData();
    $data['sources'] = [['code' => 'dmg', 'page' => 200]];

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Invalid value 'dmg' for field 'sources[0].code'");
});

test('rejects an unknown area shape', function (): void {
    $data = validFireballData();
    $data['areaShape'] = 'triangle';

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Invalid value 'triangle' for field 'areaShape'");
});

test('rejects an unknown attack roll value', function (): void {
    $data = validFireballData();
    $data['attackRoll'] = 'thrown';

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Invalid value 'thrown' for field 'attackRoll'");
});

// ─── Failure mode: wrong type ────────────────────────────────────────────────

test('rejects a non-integer level', function (): void {
    $data = validFireballData();
    $data['level'] = 'three';

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Field 'level' must be an integer");
});

test('rejects a non-string name', function (): void {
    $data = validFireballData();
    $data['name'] = 42;

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Field 'name' must be a string");
});

test('rejects a non-boolean components.verbal', function (): void {
    $data = validFireballData();
    $data['components']['verbal'] = 'yes';

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Field 'components.verbal' must be a boolean");
});

test('rejects a non-array damage field', function (): void {
    $data = validFireballData();
    $data['damage'] = '8d6 fire';

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Field 'damage' must be an array");
});

test('rejects a non-integer source page', function (): void {
    $data = validFireballData();
    $data['sources'] = [['code' => 'phb', 'page' => 'two-forty-one']];

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Field 'sources[0].page' must be an integer");
});

test('rejects a non-string saving throw ability', function (): void {
    $data = validFireballData();
    $data['savingThrow'] = ['ability' => 99];

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toContain("Field 'savingThrow.ability' must be a string");
});

// ─── Accumulates multiple errors ─────────────────────────────────────────────

test('accumulates all errors when multiple fields are invalid', function (): void {
    $data = validFireballData();
    $data['level'] = 'three';
    $data['school'] = 'pyromancy';

    $result = (new SpellYamlSchema)->validate($data);

    expect($result->isValid())->toBeFalse()
        ->and($result->errors())->toHaveCount(2);
});
