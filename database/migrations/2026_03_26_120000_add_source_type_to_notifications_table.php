<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('notifications', 'source_type')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->string('source_type', 50)->nullable()->after('type');
                $table->index(['user_id', 'source_type'], 'notifications_user_source_type_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notifications', 'source_type')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropIndex('notifications_user_source_type_idx');
                $table->dropColumn('source_type');
            });
        }
    }
};
