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
        Schema::table('listings', function (Blueprint $table) {
            // التحقق من وجود العمود أولاً
            if (!Schema::hasColumn('listings', 'title')) {
                throw new \Exception('Column title does not exist in listings table. Please run the add_title migration first.');
            }
            
            // إضافة index على title للبحث السريع
            // استخدام اسم واضح للـ index
            $table->index('title', 'listings_title_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex('listings_title_index');
        });
    }
};
