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
        Schema::table('category_banners', function (Blueprint $table) {
            $table->string('banner_type')->default('home_page')->after('category_id');
            // banner_type values: 'home_page', 'ad_creation'
            
            $table->index('banner_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('category_banners', function (Blueprint $table) {
            $table->dropColumn('banner_type');
        });
    }
};
