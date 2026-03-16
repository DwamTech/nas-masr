<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementStaffRestrictionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function employee_does_not_see_dashboard_staff_in_users_summary()
    {
        $employeeViewer = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['users.index'],
        ]);

        User::factory()->create(['role' => 'employee', 'name' => 'Hidden Employee']);
        User::factory()->create(['role' => 'admin', 'name' => 'Hidden Admin']);
        $normalUser = User::factory()->create(['role' => 'user', 'name' => 'Visible User']);

        $response = $this->actingAs($employeeViewer)->getJson('/api/admin/users-summary');

        $response->assertOk()
            ->assertJsonMissing(['name' => 'Hidden Employee'])
            ->assertJsonMissing(['name' => 'Hidden Admin'])
            ->assertJsonFragment(['name' => 'Visible User']);
    }

    /** @test */
    public function employee_cannot_create_dashboard_staff_users_or_assign_dashboard_pages()
    {
        $employeeViewer = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['users.index'],
        ]);

        $this->actingAs($employeeViewer)
            ->postJson('/api/admin/users', [
                'name' => 'New Employee',
                'phone' => '01000000991',
                'role' => 'employee',
                'allowed_dashboard_pages' => ['dashboard.home'],
            ])
            ->assertStatus(403);

        $this->actingAs($employeeViewer)
            ->postJson('/api/admin/users', [
                'name' => 'Normal User',
                'phone' => '01000000992',
                'role' => 'user',
                'allowed_dashboard_pages' => ['dashboard.home'],
            ])
            ->assertStatus(403);
    }

    /** @test */
    public function employee_cannot_manage_existing_dashboard_staff_accounts()
    {
        $employeeViewer = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['users.index'],
        ]);

        $managedEmployee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['dashboard.home'],
        ]);

        $this->actingAs($employeeViewer)
            ->putJson("/api/admin/users/{$managedEmployee->id}", [
                'allowed_dashboard_pages' => ['messages.index'],
            ])
            ->assertStatus(403);

        $this->actingAs($employeeViewer)
            ->getJson("/api/admin/users/{$managedEmployee->id}")
            ->assertStatus(403);

        $this->actingAs($employeeViewer)
            ->patchJson("/api/admin/users/{$managedEmployee->id}/block")
            ->assertStatus(403);
    }

    /** @test */
    public function employee_cannot_reset_password_for_dashboard_staff_accounts()
    {
        $employeeViewer = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['users.index'],
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($employeeViewer)
            ->putJson("/api/admin/change-password/{$admin->id}")
            ->assertStatus(403);
    }
}
