<?php

declare(strict_types=1);

namespace App\Domain\Spells\Models;

use App\Casts\UnixMillisecondsCast;
use App\Domain\Spells\Enums\ActionEconomy;
use App\Domain\Spells\Enums\AreaShape;
use App\Domain\Spells\Enums\AttackRoll;
use App\Domain\Spells\Enums\DurationCategory;
use App\Domain\Spells\Enums\School;
use App\Domain\Spells\Enums\Targeting;
use Database\Factories\SpellFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[UseFactory(SpellFactory::class)]
#[Fillable([
    'uuid',
    'slug',
    'name',
    'level',
    'school',
    'casting_time',
    'range',
    'component_verbal',
    'component_somatic',
    'component_material',
    'duration',
    'targeting',
    'area_shape',
    'area_size',
    'attack_roll',
    'action_economy',
    'duration_category',
    'personality_blurb',
    'embedding',
    'created_at_ms',
    'updated_at_ms',
])]
class Spell extends Model
{
    /** @use HasFactory<SpellFactory> */
    use HasFactory;

    public $timestamps = false;

    protected static function booted(): void
    {
        static::creating(function (self $spell): void {
            $spell->uuid ??= (string) Str::uuid();
            $now = now()->getTimestampMs();
            $spell->created_at_ms ??= $now;
            $spell->updated_at_ms ??= $now;
        });

        static::updating(function (self $spell): void {
            $spell->updated_at_ms = now()->getTimestampMs();
        });
    }

    /** @return HasMany<SpellQualifier, $this> */
    public function qualifiers(): HasMany
    {
        return $this->hasMany(SpellQualifier::class);
    }

    /** @return HasMany<SpellClass, $this> */
    public function classes(): HasMany
    {
        return $this->hasMany(SpellClass::class);
    }

    /** @return HasMany<SpellDamage, $this> */
    public function damage(): HasMany
    {
        return $this->hasMany(SpellDamage::class);
    }

    /** @return HasMany<SpellCondition, $this> */
    public function conditions(): HasMany
    {
        return $this->hasMany(SpellCondition::class);
    }

    /** @return HasMany<SpellCombatRole, $this> */
    public function combatRoles(): HasMany
    {
        return $this->hasMany(SpellCombatRole::class);
    }

    /** @return HasMany<SpellUtility, $this> */
    public function utilities(): HasMany
    {
        return $this->hasMany(SpellUtility::class);
    }

    /** @return HasMany<SpellSource, $this> */
    public function sources(): HasMany
    {
        return $this->hasMany(SpellSource::class);
    }

    /** @return HasOne<SpellSavingThrow, $this> */
    public function savingThrow(): HasOne
    {
        return $this->hasOne(SpellSavingThrow::class);
    }

    protected function casts(): array
    {
        return [
            'school' => School::class,
            'targeting' => Targeting::class,
            'area_shape' => AreaShape::class,
            'attack_roll' => AttackRoll::class,
            'action_economy' => ActionEconomy::class,
            'duration_category' => DurationCategory::class,
            'component_verbal' => 'boolean',
            'component_somatic' => 'boolean',
            'level' => 'integer',
            'created_at_ms' => UnixMillisecondsCast::class,
            'updated_at_ms' => UnixMillisecondsCast::class,
        ];
    }
}
