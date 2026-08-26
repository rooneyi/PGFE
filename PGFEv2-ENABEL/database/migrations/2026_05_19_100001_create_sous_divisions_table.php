<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sous_divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proved_id')->constrained('proveds')->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->timestamps();

            $table->unique(['proved_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sous_divisions');
    }
};
