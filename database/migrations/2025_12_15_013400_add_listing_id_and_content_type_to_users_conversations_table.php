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
            // content_type distinguishes the payload: text, listing_inquiry, image, video, etc.
            $table->string('content_type', 50)->default('text')->after('type')->index();

            // Link to a listing if the conversation is about a specific ad
            $table->foreignId('listing_id')->nullable()->after('conversation_id')->constrained('listings')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_conversations', function (Blueprint $table) {
            $table->dropForeign(['listing_id']);
            $table->dropColumn('listing_id');
            $table->dropColumn('content_type');
        });
    }
};
