<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardEmployeeAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function employee_can_login_via_dashboard_auth_endpoint()
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'phone' => '01000000001',
            'status' => 'active',
            'allowed_dashboard_pages' => ['dashboard.home'],
        ]);

        $response = $this->postJson('/api/admin/auth/login', [
            'identifier' => $employee->phone,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.role', 'employee')
            ->assertJsonPath('user.allowed_dashboard_pages.0', 'dashboard.home');
    }

    /** @test */
    public function regular_user_cannot_login_via_dashboard_auth_endpoint()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'phone' => '01000000002',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/admin/auth/login', [
            'identifier' => $user->phone,
            'password' => 'password',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function employee_can_access_authorized_dashboard_page_and_is_blocked_from_other_pages()
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['dashboard.home'],
        ]);

        $this->actingAs($employee)
            ->getJson('/api/admin/stats')
            ->assertOk();

        $this->actingAs($employee)
            ->getJson('/api/admin/users-summary')
            ->assertStatus(403)
            ->assertJsonPath('required_page_keys.0', 'users.index');
    }

    /** @test */
    public function employee_can_read_dashboard_me_endpoint()
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['account.self'],
        ]);

        $this->actingAs($employee)
            ->getJson('/api/admin/me')
            ->assertOk()
            ->assertJsonPath('user.role', 'employee')
            ->assertJsonPath('user.allowed_dashboard_pages.0', 'account.self');
    }

    /** @test */
    public function employee_still_cannot_use_routes_protected_by_legacy_admin_middleware()
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['notifications.index'],
        ]);
        $recipient = User::factory()->create([
            'role' => 'user',
            'phone' => '01000000003',
        ]);

        $this->actingAs($employee)
            ->postJson('/api/notifications', [
                'user_id' => $recipient->id,
                'title' => 'اختبار',
                'body' => 'رسالة تجريبية',
            ])
            ->assertStatus(403);
    }

    /** @test */
    public function admin_bypasses_dashboard_page_checks()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/users-summary')
            ->assertOk();
    }
}
