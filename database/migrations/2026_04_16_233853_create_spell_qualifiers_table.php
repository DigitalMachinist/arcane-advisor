<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spell_qualifiers', function (Blueprint $table): void {
            $table->foreignId('spell_id')->constrained('spells')->cascadeOnDelete();
            $table->string('qualifier', 50);
            $table->primary(['spell_id', 'qualifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spell_qualifiers');
    }
};
