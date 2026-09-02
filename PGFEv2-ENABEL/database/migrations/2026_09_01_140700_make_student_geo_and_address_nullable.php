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
        if (! Schema::hasTable('students')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        $mysql = in_array($driver, ['mysql', 'mariadb'], true);

        if (Schema::hasColumn('students', 'address')) {
            if ($mysql) {
                DB::statement('ALTER TABLE `students` MODIFY `address` VARCHAR(255) NULL');
            } else {
                Schema::table('students', function (Blueprint $table) {
                    $table->string('address', 255)->nullable()->change();
                });
            }
        }

        foreach (['country_id', 'province_id', 'territory_id', 'commune_id'] as $column) {
            if (! Schema::hasColumn('students', $column)) {
                continue;
            }

            $info = collect(Schema::getColumns('students'))->firstWhere('name', $column);
            if ($info && filter_var($info['nullable'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            if ($mysql) {
                DB::statement("ALTER TABLE `students` MODIFY `{$column}` BIGINT UNSIGNED NULL");
            } else {
                Schema::table('students', function (Blueprint $table) use ($column) {
                    $table->unsignedBigInteger($column)->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        // Intentionally empty: these fields must remain optional.
    }
};
