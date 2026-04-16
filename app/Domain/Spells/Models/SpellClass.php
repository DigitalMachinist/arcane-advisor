<?php

declare(strict_types=1);

namespace App\Domain\Spells\Models;

use App\Domain\Spells\Enums\SourceClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpellClass extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $table = 'spell_classes';

    /** @return BelongsTo<Spell, $this> */
    public function spell(): BelongsTo
    {
        return $this->belongsTo(Spell::class);
    }

    protected function casts(): array
    {
        return [
            'class' => SourceClass::class,
        ];
    }
}
