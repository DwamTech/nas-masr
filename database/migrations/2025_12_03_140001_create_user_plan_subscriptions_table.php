<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_plan_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('plan_type');
            $table->unsignedInteger('days')->default(0);
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('ad_price', 10, 2)->default(0);
            $table->string('payment_status')->default('paid');
            $table->string('payment_reference')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'category_id', 'plan_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_plan_subscriptions');
    }
};

