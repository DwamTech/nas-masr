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
        Schema::table('category_plan_prices', function (Blueprint $table) {
            $table->decimal('free_ad_max_price', 12, 2)->default(0)->after('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('category_plan_prices', function (Blueprint $table) {
            $table->dropColumn('free_ad_max_price');
        });
    }
};
