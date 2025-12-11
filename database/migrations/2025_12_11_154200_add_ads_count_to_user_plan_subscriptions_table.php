<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_plan_subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('ads_total')->default(0)->after('ad_price');
            $table->unsignedInteger('ads_used')->default(0)->after('ads_total');
        });

        // منح رصيد افتراضي للاشتراكات القديمة لضمان عدم توقفها
        \Illuminate\Support\Facades\DB::table('user_plan_subscriptions')->update(['ads_total' => 100]);
    }

    public function down(): void
    {
        Schema::table('user_plan_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['ads_total', 'ads_used']);
        });
    }
};
