<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collecte_rapides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proved_id')->constrained('proveds')->cascadeOnDelete();
            $table->foreignId('sous_division_id')->constrained('sous_divisions')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->string('status', 20)->default('draft'); // draft|submitted
            $table->unsignedTinyInteger('current_step')->default(0);
            $table->json('data')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['sous_division_id', 'school_year_id'], 'collecte_rapides_sd_year_unique');
            $table->index(['proved_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collecte_rapides');
    }
};
