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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 191)->unique();

            $table->text('value')->nullable();

            $table->string('type', 50)->default('string');
            $table->string('group', 191)->default('general');
            $table->string('label')->nullable();
            $table->json('meta')->nullable();

            $table->boolean('autoload')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
