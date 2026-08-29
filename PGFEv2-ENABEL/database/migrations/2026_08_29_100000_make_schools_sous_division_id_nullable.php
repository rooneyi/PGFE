<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('schools', 'sous_division_id')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->foreignId('sous_division_id')
                    ->nullable()
                    ->constrained('sous_divisions')
                    ->nullOnDelete();
            });

            return;
        }

        if ($this->isColumnNullable('schools', 'sous_division_id')) {
            return;
        }

        $this->dropForeignKeyIfExists('schools', 'sous_division_id');

        DB::statement('ALTER TABLE schools MODIFY sous_division_id BIGINT UNSIGNED NULL');

        Schema::table('schools', function (Blueprint $table) {
            $table->foreign('sous_division_id')
                ->references('id')
                ->on('sous_divisions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('schools', 'sous_division_id')) {
            return;
        }

        $this->dropForeignKeyIfExists('schools', 'sous_division_id');

        DB::statement('ALTER TABLE schools MODIFY sous_division_id BIGINT UNSIGNED NOT NULL');

        Schema::table('schools', function (Blueprint $table) {
            $table->foreign('sous_division_id')
                ->references('id')
                ->on('sous_divisions')
                ->nullOnDelete();
        });
    }

    private function isColumnNullable(string $table, string $column): bool
    {
        $row = DB::selectOne(
            'SELECT IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );

        return $row !== null && strtoupper((string) $row->IS_NULLABLE) === 'YES';
    }

    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        $fk = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [$table, $column]
        );

        if ($fk === null) {
            return;
        }

        $name = (string) $fk->CONSTRAINT_NAME;
        DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$name}`");
    }
};
