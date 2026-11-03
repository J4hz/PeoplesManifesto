<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            // mca, mp, governor, senator, women_rep
            $table->string('type', 16);
            $table->string('name');
            // Ward for an MCA, constituency for an MP, county for the rest.
            $table->morphs('jurisdiction');
            $table->timestamps();
            $table->unique(['type', 'jurisdiction_type', 'jurisdiction_id']);
        });

        Schema::create('leaders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->date('term_started_on')->nullable();
            $table->date('term_ends_on')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            // Staff work for one leader. Leaders point at their own record.
            $table->foreignId('leader_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('leader_id');
        });
        Schema::dropIfExists('leaders');
        Schema::dropIfExists('offices');
    }
};
