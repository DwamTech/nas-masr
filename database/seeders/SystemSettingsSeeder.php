<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // 1) رقم الدعم
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'support_number'],
            [
                'value'       => '+971 54 519 4553',
                'type'        => 'string',
                'group'       => 'general',
                'label'       => 'رقم الدعم الفني',
                'meta'        => json_encode([
                    'placeholder' => '+971 ...',
                    'icon' => 'phone'
                ]),
                'autoload'    => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]
        );

        // 2) صورة البانر
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'panner_image'],
            [
                'value'       => 'storage/uploads/banner/53228567cbfa1e8ef884e31013cba35dffde42d3.png',
                'type'        => 'string',
                'group'       => 'appearance',
                'label'       => 'صورة البانر الرئيسية',
                'meta'        => json_encode([
                    'input' => 'file',
                    'preview' => true,
                ]),
                'autoload'    => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]
        );
    }
}
