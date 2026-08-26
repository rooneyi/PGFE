<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internat_pavillons', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('gender', 20)->default('mixte'); // mixte | M | F
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'name']);
        });

        Schema::create('internat_chambres', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('pavillon_id')->constrained('internat_pavillons')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('capacity')->default(1);
            $table->string('gender', 20)->default('mixte');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'pavillon_id']);
        });

        Schema::create('internat_lits', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('chambre_id')->constrained('internat_chambres')->cascadeOnDelete();
            $table->string('code');
            $table->string('status', 30)->default('libre'); // libre | occupe | hors_service
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['chambre_id', 'code']);
            $table->index(['school_id', 'status']);
        });

        Schema::create('internat_affectations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('lit_id')->constrained('internat_lits')->cascadeOnDelete();
            $table->date('date_entree');
            $table->date('date_sortie')->nullable();
            $table->string('statut', 20)->default('active'); // active | terminee
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'school_year_id', 'statut']);
            $table->index(['student_id', 'statut']);
            $table->index(['lit_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internat_affectations');
        Schema::dropIfExists('internat_lits');
        Schema::dropIfExists('internat_chambres');
        Schema::dropIfExists('internat_pavillons');
    }
};
