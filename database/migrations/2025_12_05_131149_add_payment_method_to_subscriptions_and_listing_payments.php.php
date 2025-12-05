<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('user_plan_subscriptions', 'payment_method')) {
            Schema::table('user_plan_subscriptions', function (Blueprint $table) {
                $table->string('payment_method', 50)
                    ->nullable()
                    ->after('payment_status');
            });
        }

        if (!Schema::hasColumn('listing_payments', 'payment_method')) {
            Schema::table('listing_payments', function (Blueprint $table) {
                $table->string('payment_method', 50)
                    ->nullable()
                    ->after('amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_plan_subscriptions', 'payment_method')) {
            Schema::table('user_plan_subscriptions', function (Blueprint $table) {
                $table->dropColumn('payment_method');
            });
        }

        if (Schema::hasColumn('listing_payments', 'payment_method')) {
            Schema::table('listing_payments', function (Blueprint $table) {
                $table->dropColumn('payment_method');
            });
        }
    }
};
