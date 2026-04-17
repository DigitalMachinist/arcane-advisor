<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spells', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug', 100)->unique();
            $table->string('name');
            $table->string('school', 50);
            $table->string('casting_time');
            $table->string('range');
            $table->string('duration');
            $table->string('targeting', 50);
            $table->string('action_economy', 50);
            $table->string('duration_category', 50);
            $table->string('area_shape', 50)->nullable();
            $table->string('area_size', 100)->nullable();
            $table->string('attack_roll', 50)->nullable();
            $table->text('component_material')->nullable();
            $table->text('personality_blurb')->default('');
            $table->tinyInteger('level')->unsigned();
            $table->boolean('component_verbal');
            $table->boolean('component_somatic');
            $table->binary('embedding')->nullable();
            $table->unsignedBigInteger('created_at_ms');
            $table->unsignedBigInteger('updated_at_ms')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spells');
    }
};
