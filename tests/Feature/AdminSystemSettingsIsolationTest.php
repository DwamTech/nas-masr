<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminSystemSettingsIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_read_system_settings_via_dashboard_endpoint(): void
    {
        DB::table('system_settings')->insert([
            'key' => 'free_ads_count',
            'value' => '7',
            'type' => 'integer',
            'group' => 'ads',
            'autoload' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/system-settings')
            ->assertOk()
            ->assertJsonPath('free_ads_count', 7);
    }

    public function test_employee_with_allowed_dashboard_page_can_read_admin_system_settings(): void
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['settings.index'],
        ]);

        $this->actingAs($employee)
            ->getJson('/api/admin/system-settings')
            ->assertOk();
    }

    public function test_employee_without_allowed_page_is_blocked_from_admin_system_settings(): void
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['dashboard.home'],
        ]);

        $this->actingAs($employee)
            ->getJson('/api/admin/system-settings')
            ->assertStatus(403);
    }

    public function test_public_system_settings_route_remains_unchanged(): void
    {
        DB::table('system_settings')->insert([
            'key' => 'show_phone',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'general',
            'autoload' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/system-settings')
            ->assertOk()
            ->assertJsonPath('show_phone', true);
    }
}
