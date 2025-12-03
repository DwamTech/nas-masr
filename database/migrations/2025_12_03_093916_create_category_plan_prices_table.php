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
        Schema::create('category_plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete()
                ->unique(); // كل قسم ليه صف واحد بس في الجدول

            $table->decimal('price_featured', 10, 2)->default(0);  // سعر الإعلان المميز
            $table->decimal('price_standard', 10, 2)->default(0); // سعر الإعلان الستاندرد
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_plan_prices');
    }
};
