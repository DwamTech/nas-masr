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

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'privacy_policy'],
            [
                'value'       => '',
                'type'        => 'text',
                'group'       => 'general',
                'label'       => 'سياسة الخصوصية',
                'meta'        => json_encode([
                    'input' => 'rich_text',
                    'icon' => 'shield'
                ]),
                'autoload'    => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]
        );

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'terms_conditions-main_'],
            [
                'value'       => '',
                'type'        => 'text',
                'group'       => 'general',
                'label'       => 'الشروط والأحكام',
                'meta'        => json_encode([
                    'input' => 'rich_text',
                    'icon' => 'document'
                ]),
                'autoload'    => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]
        );

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'sub_support_number'],
            [
                'value'       => '',
                'type'        => 'string',
                'group'       => 'general',
                'label'       => 'رقم الدعم الفرعي',
                'meta'        => json_encode([
                    'placeholder' => '+20 ...',
                    'icon' => 'phone'
                ]),
                'autoload'    => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]
        );

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'emergency_number'],
            [
                'value'       => '',
                'type'        => 'string',
                'group'       => 'general',
                'label'       => 'رقم الطوارئ',
                'meta'        => json_encode([
                    'placeholder' => '+20 ...',
                    'icon' => 'alert'
                ]),
                'autoload'    => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]
        );

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'facebook'],
            [
                'value'       => '',
                'type'        => 'string',
                'group'       => 'general',
                'label'       => 'فيسبوك',
                'meta'        => json_encode([
                    'input' => 'url',
                    'icon' => 'facebook'
                ]),
                'autoload'    => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]
        );

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'twitter'],
            [
                'value'       => '',
                'type'        => 'string',
                'group'       => 'general',
                'label'       => 'تويتر',
                'meta'        => json_encode([
                    'input' => 'url',
                    'icon' => 'twitter'
                ]),
                'autoload'    => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]
        );

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'instagram'],
            [
                'value'       => '',
                'type'        => 'string',
                'group'       => 'general',
                'label'       => 'إنستغرام',
                'meta'        => json_encode([
                    'input' => 'url',
                    'icon' => 'instagram'
                ]),
                'autoload'    => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]
        );

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'email'],
            [
                'value'       => '',
                'type'        => 'string',
                'group'       => 'general',
                'label'       => 'البريد الإلكتروني',
                'meta'        => json_encode([
                    'input' => 'email',
                    'icon' => 'mail'
                ]),
                'autoload'    => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]
        );
    }
}
