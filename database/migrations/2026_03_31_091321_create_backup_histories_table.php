<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('backup_histories', function (Blueprint $table) {
            $table->id();

            // اسم الملف فقط: e.g. backup_2026_03_31_db.sql.gz
            $table->string('file_name');

            // المسار الكامل داخل الـ disk: e.g. backups/backup_2026_03_31_db.sql.gz
            $table->string('file_path');

            // نوع الـ backup: full / db / files
            $table->enum('type', ['full', 'db', 'files'])->default('db');

            // حالة العملية
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');

            // حجم الملف بالـ bytes (null لو فشل الـ backup قبل ما يتكتب)
            $table->unsignedBigInteger('size')->nullable();

            // الـ admin اللي نفّذ العملية
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_histories');
    }
};
