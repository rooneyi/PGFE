<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('registrations') || ! Schema::hasColumn('registrations', 'academic_personal_id')) {
            return;
        }

        try {
            Schema::table('registrations', function ($table): void {
                $table->dropForeign(['academic_personal_id']);
            });
        } catch (\Throwable) {
        }

        DB::statement('ALTER TABLE `registrations` MODIFY `academic_personal_id` BIGINT UNSIGNED NULL');

        try {
            Schema::table('registrations', function ($table): void {
                $table->foreign('academic_personal_id')
                    ->references('id')
                    ->on('academic_personals')
                    ->nullOnDelete();
            });
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('registrations') || ! Schema::hasColumn('registrations', 'academic_personal_id')) {
            return;
        }

        try {
            Schema::table('registrations', function ($table): void {
                $table->dropForeign(['academic_personal_id']);
            });
        } catch (\Throwable) {
        }

        DB::statement('ALTER TABLE `registrations` MODIFY `academic_personal_id` BIGINT UNSIGNED NOT NULL');

        try {
            Schema::table('registrations', function ($table): void {
                $table->foreign('academic_personal_id')
                    ->references('id')
                    ->on('academic_personals');
            });
        } catch (\Throwable) {
        }
    }
};
