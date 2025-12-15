<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users_conversations', function (Blueprint $table) {
            // attachment stores the file path for media messages (image, video, audio)
            $table->string('attachment')->nullable()->after('message');
            
            // Allow message to be nullable if an attachment exists (e.g. sending just an image)
            $table->text('message')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_conversations', function (Blueprint $table) {
            $table->dropColumn('attachment');
            // Revert message to not null if needed, but risky if rows exist with nulls.
            // keeping it nullable on rollback is safer or we'd need to fill data.
        });
    }
};
