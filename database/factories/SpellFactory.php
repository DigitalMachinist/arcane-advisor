<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Spells\Enums\ActionEconomy;
use App\Domain\Spells\Enums\DurationCategory;
use App\Domain\Spells\Enums\Qualifier;
use App\Domain\Spells\Enums\School;
use App\Domain\Spells\Enums\SourceClass;
use App\Domain\Spells\Enums\SourceCode;
use App\Domain\Spells\Enums\Targeting;
use App\Domain\Spells\Models\Spell;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @extends Factory<Spell>
 */
class SpellFactory extends Factory
{
    protected $model = Spell::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(nb: $this->faker->numberBetween(1, 3), asText: true);
        $name = ucwords($name);

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'level' => $this->faker->numberBetween(0, 9),
            'school' => $this->faker->randomElement(School::cases()),
            'casting_time' => '1 action',
            'range' => $this->faker->randomElement(['Self', 'Touch', '30 feet', '60 feet', '150 feet']),
            'component_verbal' => $this->faker->boolean(),
            'component_somatic' => $this->faker->boolean(),
            'component_material' => null,
            'duration' => 'Instantaneous',
            'targeting' => $this->faker->randomElement(Targeting::cases()),
            'area_shape' => null,
            'area_size' => null,
            'attack_roll' => null,
            'action_economy' => ActionEconomy::Action,
            'duration_category' => DurationCategory::Instantaneous,
            'personality_blurb' => '',
            'embedding' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Spell $spell): void {
            DB::table('spell_classes')->insert([
                'spell_id' => $spell->id,
                'class' => SourceClass::Wizard->value,
            ]);

            DB::table('spell_sources')->insert([
                'spell_id' => $spell->id,
                'code' => SourceCode::PlayersHandbook->value,
                'page' => $this->faker->numberBetween(200, 300),
            ]);

            if ($this->faker->boolean(30)) {
                DB::table('spell_qualifiers')->insert([
                    'spell_id' => $spell->id,
                    'qualifier' => Qualifier::Concentration->value,
                ]);
            }
        });
    }
}
