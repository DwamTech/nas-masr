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

    public function test_public_and_dashboard_reads_exclude_hidden_governorates_and_cities(): void
    {
        $visible = $this->seedGovernorates('القاهرة', 'مدينة نصر', true, true);
        $this->seedGovernorates('الجيزة', 'الدقي', false, true);
        $this->seedGovernorates('الإسكندرية', 'سموحة', true, false);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->getJson('/api/governorates')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'القاهرة',
            ])
            ->assertJsonMissing([
                'name' => 'الجيزة',
            ])
            ->assertJsonMissing([
                'name' => 'الدقي',
            ])
            ->assertJsonMissing([
                'name' => 'سموحة',
            ]);

        $this->getJson("/api/governorates/{$visible['governorate_id']}/cities")
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'مدينة نصر',
            ])
            ->assertJsonMissing([
                'name' => 'سموحة',
            ]);

        $hiddenGovernorate = DB::table('governorates')->where('name', 'الجيزة')->value('id');
        $this->getJson("/api/governorates/{$hiddenGovernorate}/cities")
            ->assertOk()
            ->assertExactJson([]);

        $this->actingAs($admin)
            ->getJson('/api/admin/filter-lists/governorates')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $visible['governorate_id'],
                'name' => 'القاهرة',
            ])
            ->assertJsonMissing([
                'name' => 'الجيزة',
            ])
            ->assertJsonMissing([
                'name' => 'سموحة',
            ]);
    }

    public function test_dashboard_management_can_request_inactive_items_explicitly(): void
    {
        $visible = $this->seedGovernorates('القاهرة', 'مدينة نصر', true, true);
        $hidden = $this->seedGovernorates('الجيزة', 'الدقي', false, false);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/filter-lists/governorates?include_inactive=1')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $visible['governorate_id'],
                'name' => 'القاهرة',
                'is_active' => true,
            ])
            ->assertJsonFragment([
                'id' => $hidden['governorate_id'],
                'name' => 'الجيزة',
                'is_active' => false,
            ])
            ->assertJsonFragment([
                'id' => $hidden['city_id'],
                'name' => 'الدقي',
                'governorate_id' => $hidden['governorate_id'],
                'is_active' => false,
            ]);
    }

    public function test_admin_can_toggle_governorate_and_city_visibility(): void
    {
        $ctx = $this->seedGovernorates();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/governorates/{$ctx['governorate_id']}/visibility", [
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $ctx['governorate_id'],
                'is_active' => false,
            ]);

        $this->getJson('/api/governorates')
            ->assertOk()
            ->assertJsonMissing([
                'name' => 'القاهرة',
            ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/governorates/{$ctx['governorate_id']}/visibility", [
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $ctx['governorate_id'],
                'is_active' => true,
            ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/cities/{$ctx['city_id']}/visibility", [
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $ctx['city_id'],
                'is_active' => false,
            ]);

        $this->getJson("/api/governorates/{$ctx['governorate_id']}/cities")
            ->assertOk()
            ->assertJsonMissing([
                'name' => 'مدينة نصر',
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

    protected function seedGovernorates(
        string $governorateName = 'القاهرة',
        string $cityName = 'مدينة نصر',
        bool $governorateActive = true,
        bool $cityActive = true
    ): array
    {
        $governorateId = DB::table('governorates')->insertGetId([
            'name' => $governorateName,
            'sort_order' => 1,
            'is_active' => $governorateActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cityId = DB::table('cities')->insertGetId([
            'governorate_id' => $governorateId,
            'name' => $cityName,
            'sort_order' => 1,
            'is_active' => $cityActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'governorate_id' => $governorateId,
            'city_id' => $cityId,
        ];
    }
}
