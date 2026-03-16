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
        Schema::table('users', function (Blueprint $table) {
            // Add is_representative column (default false)
            $table->boolean('is_representative')->default(false)->after('role');
        });

        // Migrate existing data: users with role='representative' should have is_representative=true
        DB::statement("UPDATE users SET is_representative = 1 WHERE role = 'representative'");
        
        echo "✅ Migration completed. Updated " . DB::table('users')->where('role', 'representative')->count() . " representatives.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_representative');
        });
    }
};
