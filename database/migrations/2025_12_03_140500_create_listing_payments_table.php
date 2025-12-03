<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('plan_type')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('status')->default('paid');
            $table->timestamps();
            $table->unique(['listing_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_payments');
    }
};

