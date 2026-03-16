<?php

namespace Tests\Feature;

use App\Models\AdminNotifications;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_send_dashboard_notification_via_admin_endpoint(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $recipient = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($admin)->postJson('/api/admin/admin-notifications', [
            'user_id' => $recipient->id,
            'title' => 'تنبيه إداري',
            'body' => 'رسالة من الداشبورد',
            'type' => 'promotion',
            'data' => ['source' => 'test'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'created')
            ->assertJsonPath('data.user_id', $recipient->id)
            ->assertJsonPath('admin_notification.source', 'dashboard');

        $this->assertDatabaseHas(Notification::class, [
            'user_id' => $recipient->id,
            'title' => 'تنبيه إداري',
            'body' => 'رسالة من الداشبورد',
            'type' => 'promotion',
        ]);

        $this->assertDatabaseHas(AdminNotifications::class, [
            'title' => 'تنبيه إداري',
            'body' => 'رسالة من الداشبورد',
            'type' => 'promotion',
            'source' => 'dashboard',
        ]);
    }

    public function test_employee_with_notifications_permission_can_send_dashboard_notification(): void
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['notifications.index'],
        ]);

        $recipient = User::factory()->create([
            'role' => 'user',
        ]);

        $this->actingAs($employee)->postJson('/api/admin/admin-notifications', [
            'user_id' => $recipient->id,
            'title' => 'رسالة موظف',
            'body' => 'محتوى من الموظف',
        ])->assertCreated();

        $this->assertDatabaseHas(Notification::class, [
            'user_id' => $recipient->id,
            'title' => 'رسالة موظف',
            'body' => 'محتوى من الموظف',
        ]);
    }

    public function test_employee_without_notifications_permission_is_blocked_from_admin_notification_send(): void
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['dashboard.home'],
        ]);

        $recipient = User::factory()->create([
            'role' => 'user',
        ]);

        $this->actingAs($employee)->postJson('/api/admin/admin-notifications', [
            'user_id' => $recipient->id,
            'title' => 'محاولة غير مصرح بها',
            'body' => 'يجب أن تفشل',
        ])->assertStatus(403);

        $this->assertDatabaseMissing(Notification::class, [
            'user_id' => $recipient->id,
            'title' => 'محاولة غير مصرح بها',
        ]);
    }

    public function test_legacy_mobile_notification_endpoint_still_rejects_employee(): void
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['notifications.index'],
        ]);

        $recipient = User::factory()->create([
            'role' => 'user',
        ]);

        $this->actingAs($employee)->postJson('/api/notifications', [
            'user_id' => $recipient->id,
            'title' => 'legacy',
            'body' => 'should fail',
        ])->assertStatus(403);
    }
}
