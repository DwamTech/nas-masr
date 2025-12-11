<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_plan_prices', function (Blueprint $table) {
            $table->unsignedInteger('featured_ads_count')->default(0)->after('featured_days');
            $table->unsignedInteger('standard_ads_count')->default(0)->after('standard_days');
        });
    }

    public function down(): void
    {
        Schema::table('category_plan_prices', function (Blueprint $table) {
            $table->dropColumn(['featured_ads_count', 'standard_ads_count']);
        });
    }
};
