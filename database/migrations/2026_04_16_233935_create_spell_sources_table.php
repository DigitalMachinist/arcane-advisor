<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spell_sources', function (Blueprint $table): void {
            $table->foreignId('spell_id')->constrained('spells')->cascadeOnDelete();
            $table->string('code', 50);
            $table->unsignedInteger('page');
            $table->primary(['spell_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spell_sources');
    }
};
