<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::ensureVectorExtensionExists();

        Schema::create('counties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // IEBC county number, 1 to 47.
            $table->unsignedSmallInteger('code')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('constituencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('county_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('wards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('constituency_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            // Only active wards accept reports and get weekly briefs.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['ward_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas');
        Schema::dropIfExists('wards');
        Schema::dropIfExists('constituencies');
        Schema::dropIfExists('counties');
    }
};
