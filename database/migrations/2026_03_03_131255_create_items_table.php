<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('type', 50);
            $table->string('rarity', 50);
            $table->unsignedInteger('required_level')->nullable();
            $table->unsignedSmallInteger('power');
            $table->unsignedSmallInteger('speed');
            $table->unsignedSmallInteger('durability');
            $table->text('magical_properties');
            $table->boolean('tradeable_default')->default(true);
            $table->timestamps();

            $table->index(['type', 'rarity']);
            $table->index('required_level');
            $table->index('tradeable_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
