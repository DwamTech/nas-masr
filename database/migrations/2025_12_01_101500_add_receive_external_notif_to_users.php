<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'receive_external_notif')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('receive_external_notif')->default(true)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'receive_external_notif')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('receive_external_notif');
            });
        }
    }
};

