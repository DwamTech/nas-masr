<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardFilterListsGovernoratesIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_employee_with_categories_filters_can_read_dashboard_governorates(): void
    {
        $ctx = $this->seedGovernorates();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['categories.filters'],
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/filter-lists/governorates')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $ctx['governorate_id'],
                'name' => 'القاهرة',
            ])
            ->assertJsonFragment([
                'id' => $ctx['city_id'],
                'name' => 'مدينة نصر',
                'governorate_id' => $ctx['governorate_id'],
            ]);

        $this->actingAs($employee)
            ->getJson('/api/admin/filter-lists/governorates')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $ctx['governorate_id'],
                'name' => 'القاهرة',
            ]);
    }

    public function test_employee_without_categories_filters_permission_is_blocked(): void
    {
        $this->seedGovernorates();

        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['dashboard.home'],
        ]);

        $this->actingAs($employee)
            ->getJson('/api/admin/filter-lists/governorates')
            ->assertStatus(403);
    }

    public function test_public_governorates_route_remains_unchanged(): void
    {
        $this->seedGovernorates();

        $this->getJson('/api/governorates')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'القاهرة',
            ])
            ->assertJsonFragment([
                'name' => 'غير ذلك',
            ]);
    }

    protected function seedGovernorates(): array
    {
        $governorateId = DB::table('governorates')->insertGetId([
            'name' => 'القاهرة',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cityId = DB::table('cities')->insertGetId([
            'governorate_id' => $governorateId,
            'name' => 'مدينة نصر',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'governorate_id' => $governorateId,
            'city_id' => $cityId,
        ];
    }
}
