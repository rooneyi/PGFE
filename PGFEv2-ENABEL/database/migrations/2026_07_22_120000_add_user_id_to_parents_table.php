<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            if (! Schema::hasColumn('parents', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('school_id')
                    ->constrained('users')
                    ->nullOnDelete();
                $table->unique('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            if (Schema::hasColumn('parents', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
