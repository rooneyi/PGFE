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
        if (! Schema::hasTable('classrooms') || ! Schema::hasColumn('classrooms', 'titulaire_id')) {
            return;
        }

        $column = collect(Schema::getColumns('classrooms'))->firstWhere('name', 'titulaire_id');
        if ($column && filter_var($column['nullable'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `classrooms` MODIFY `titulaire_id` BIGINT UNSIGNED NULL');

            return;
        }

        Schema::table('classrooms', function (Blueprint $table) {
            $table->unsignedBigInteger('titulaire_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Intentionally empty: titulaire must remain optional.
    }
};
