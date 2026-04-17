<?php

declare(strict_types=1);

namespace App\Domain\Spells\Actions;

use App\Domain\Spells\Data\SpellData;
use App\Domain\Spells\Models\Spell;
use App\Domain\Spells\Models\SpellClass;
use App\Domain\Spells\Models\SpellCombatRole;
use App\Domain\Spells\Models\SpellCondition;
use App\Domain\Spells\Models\SpellDamage;
use App\Domain\Spells\Models\SpellQualifier;
use App\Domain\Spells\Models\SpellSavingThrow;
use App\Domain\Spells\Models\SpellSource;
use App\Domain\Spells\Models\SpellUtility;
use App\Domain\Spells\Parsers\CastingTimeParser;
use App\Domain\Spells\Parsers\DurationParser;
use App\Domain\Spells\YamlLoader;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SpellsImportAction
{
    public function __construct(
        private readonly YamlLoader $loader = new YamlLoader,
        private readonly CastingTimeParser $castingTimeParser = new CastingTimeParser,
        private readonly DurationParser $durationParser = new DurationParser,
    ) {}

    /**
     * Import all YAML spell files from the given directory.
     *
     * @throws RuntimeException if any YAML file is invalid or import fails
     */
    public function execute(string $directory): int
    {
        $files = glob($directory.'/*.yaml');

        if ($files === false) {
            throw new RuntimeException("Could not read spell directory: '{$directory}'");
        }

        // Load and validate all files before touching the database so we can
        // abort cleanly without partial state.
        $spells = [];

        foreach ($files as $file) {
            $spells[] = $this->loader->load($file);
        }

        DB::transaction(function () use ($spells): void {
            foreach ($spells as $spell) {
                $this->upsert($spell);
            }
        });

        return count($spells);
    }

    private function upsert(SpellData $data): void
    {
        $actionEconomy = $this->castingTimeParser->parse($data->castingTime);
        $durationCategory = $this->durationParser->parse($data->duration);

        /** @var Spell $spell */
        $spell = Spell::updateOrCreate(
            ['slug' => $data->slug],
            [
                'name' => $data->name,
                'level' => $data->level,
                'school' => $data->school,
                'casting_time' => $data->castingTime,
                'range' => $data->range,
                'component_verbal' => $data->componentVerbal,
                'component_somatic' => $data->componentSomatic,
                'component_material' => $data->componentMaterial,
                'duration' => $data->duration,
                'targeting' => $data->targeting,
                'area_shape' => $data->areaShape,
                'area_size' => $data->areaSize,
                'attack_roll' => $data->attackRoll,
                'action_economy' => $actionEconomy,
                'duration_category' => $durationCategory,
                'personality_blurb' => $data->personalityBlurb,
            ],
        );

        // Replace child rows on each upsert to keep idempotent
        $spell->qualifiers()->delete();

        foreach ($data->qualifiers as $qualifier) {
            SpellQualifier::create([
                'spell_id' => $spell->id,
                'qualifier' => $qualifier,
            ]);
        }

        $spell->classes()->delete();

        foreach ($data->classes as $class) {
            SpellClass::create([
                'spell_id' => $spell->id,
                'class' => $class,
            ]);
        }

        $spell->damage()->delete();

        foreach ($data->damage as $entry) {
            SpellDamage::create([
                'spell_id' => $spell->id,
                'dice' => $entry['dice'],
                'type' => $entry['type'],
            ]);
        }

        $spell->conditions()->delete();

        foreach ($data->conditions as $condition) {
            SpellCondition::create([
                'spell_id' => $spell->id,
                'condition' => $condition,
            ]);
        }

        $spell->combatRoles()->delete();

        foreach ($data->combatRoles as $role) {
            SpellCombatRole::create([
                'spell_id' => $spell->id,
                'role' => $role,
            ]);
        }

        $spell->utilities()->delete();

        foreach ($data->utilities as $utility) {
            SpellUtility::create([
                'spell_id' => $spell->id,
                'utility' => $utility,
            ]);
        }

        $spell->sources()->delete();

        foreach ($data->sources as $source) {
            SpellSource::create([
                'spell_id' => $spell->id,
                'code' => $source['code'],
                'page' => $source['page'],
            ]);
        }

        // Saving throw: one-or-none per spell
        $spell->savingThrow()->delete();

        if ($data->savingThrow !== null) {
            SpellSavingThrow::create([
                'spell_id' => $spell->id,
                'ability' => $data->savingThrow['ability'],
            ]);
        }
    }
}
