<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spell_classes', function (Blueprint $table): void {
            $table->foreignId('spell_id')->constrained('spells')->cascadeOnDelete();
            $table->string('class', 50);
            $table->primary(['spell_id', 'class']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spell_classes');
    }
};
