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
        DB::table('system_settings')->updateOrInsert(
            ['id' => 1],
            [
                'support_number' => '+971 54 519 4553',
                'panner_image'   => 'storage/uploads/banner/53228567cbfa1e8ef884e31013cba35dffde42d3.png',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]
        );
    }
}
