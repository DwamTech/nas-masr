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
        Schema::create('category_field_option_ranks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('field_name');
            $table->string('option_value');
            $table->unsignedInteger('rank');
            $table->timestamps();

            // Unique constraint: each option can only appear once per category/field
            $table->unique(['category_id', 'field_name', 'option_value'], 'unique_option');
            
            // Index for performance: querying by category and field
            $table->index(['category_id', 'field_name'], 'idx_category_field');
            
            // Index for performance: ordering by rank
            $table->index(['category_id', 'field_name', 'rank'], 'idx_rank');

            // Foreign key constraint
            $table->foreign('category_id')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_field_option_ranks');
    }
};
