<?php

declare(strict_types=1);

namespace App\Domain\Spells\Models;

use App\Domain\Spells\Enums\OutOfCombatUtility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpellUtility extends Model
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
            'utility' => OutOfCombatUtility::class,
        ];
    }
}
