<?php

declare(strict_types=1);

namespace App\Domain\Spells\Models;

use App\Domain\Spells\Enums\CombatRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpellCombatRole extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    /** @return BelongsTo<Spell, $this> */
    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }

    protected function casts(): array
    {
        return [
            'role' => CombatRole::class,
        ];
    }
}
