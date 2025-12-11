<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class JobsDefaultImageSeeder extends Seeder
{
    public function run(): void
    {
        SystemSetting::updateOrCreate(
            ['key' => 'jobs_default_image'],
            [
                'value' => 'defaults/jobs_default.png', // Placeholder, user will upload real one
                'type' => 'string',
                'group' => 'appearance',
                'autoload' => true,
            ]
        );
    }
}
