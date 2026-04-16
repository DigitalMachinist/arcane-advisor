<?php

declare(strict_types=1);

namespace App\Domain\Spells\Models;

use App\Domain\Spells\Enums\DamageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpellDamage extends Model
{
    public $timestamps = false;

    protected $table = 'spell_damage';

    /** @return BelongsTo<Spell, $this> */
    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }

    protected function casts(): array
    {
        return [
            'type' => DamageType::class,
        ];
    }
}
