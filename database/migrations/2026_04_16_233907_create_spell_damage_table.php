<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spell_damage', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('spell_id')->constrained('spells')->cascadeOnDelete();
            $table->string('dice');
            $table->string('type', 50);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spell_damage');
    }
};
