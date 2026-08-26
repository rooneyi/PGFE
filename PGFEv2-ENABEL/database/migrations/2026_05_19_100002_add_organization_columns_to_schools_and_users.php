<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (! Schema::hasColumn('schools', 'sous_division_id')) {
                $table->foreignId('sous_division_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('sous_divisions')
                    ->nullOnDelete();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'proved_id')) {
                $table->foreignId('proved_id')
                    ->nullable()
                    ->after('school_id')
                    ->constrained('proveds')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'sous_division_id')) {
                $table->foreignId('sous_division_id')
                    ->nullable()
                    ->after('proved_id')
                    ->constrained('sous_divisions')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (Schema::hasColumn('schools', 'sous_division_id')) {
                $table->dropConstrainedForeignId('sous_division_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'sous_division_id')) {
                $table->dropConstrainedForeignId('sous_division_id');
            }
            if (Schema::hasColumn('users', 'proved_id')) {
                $table->dropConstrainedForeignId('proved_id');
            }
        });
    }
};
