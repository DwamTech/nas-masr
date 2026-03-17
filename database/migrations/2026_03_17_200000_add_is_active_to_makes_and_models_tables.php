<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('makes', function (Blueprint $table) {
            if (! Schema::hasColumn('makes', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('name');
            }
        });

        Schema::table('models', function (Blueprint $table) {
            if (! Schema::hasColumn('models', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('models', function (Blueprint $table) {
            if (Schema::hasColumn('models', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });

        Schema::table('makes', function (Blueprint $table) {
            if (Schema::hasColumn('makes', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
