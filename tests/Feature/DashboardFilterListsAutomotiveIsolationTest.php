<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardFilterListsAutomotiveIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_employee_with_categories_filters_and_ads_create_can_read_dashboard_automotive_list(): void
    {
        $ctx = $this->seedAutomotiveContext();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $filtersEmployee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['categories.filters'],
        ]);

        $adsCreateEmployee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['ads.create'],
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/filter-lists/automotive')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $ctx['make_id'],
                'name' => 'Toyota',
            ])
            ->assertJsonFragment([
                'id' => $ctx['model_id'],
                'name' => 'Corolla',
                'make_id' => $ctx['make_id'],
            ]);

        $this->actingAs($filtersEmployee)
            ->getJson('/api/admin/filter-lists/automotive')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $ctx['make_id'],
                'name' => 'Toyota',
            ]);

        $this->actingAs($adsCreateEmployee)
            ->getJson('/api/admin/filter-lists/automotive')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $ctx['make_id'],
                'name' => 'Toyota',
            ]);
    }

    public function test_employee_without_allowed_page_is_blocked_from_dashboard_automotive_list(): void
    {
        $this->seedAutomotiveContext();

        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['dashboard.home'],
        ]);

        $this->actingAs($employee)
            ->getJson('/api/admin/filter-lists/automotive')
            ->assertStatus(403);
    }

    public function test_public_makes_route_remains_unchanged(): void
    {
        $this->seedAutomotiveContext();

        $this->getJson('/api/makes')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Toyota',
            ])
            ->assertJsonFragment([
                'name' => 'غير ذلك',
            ]);
    }

    protected function seedAutomotiveContext(): array
    {
        DB::table('categories')->insert([
            'id' => 1,
            'slug' => 'cars',
            'name' => 'سيارات',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $makeId = DB::table('makes')->insertGetId([
            'name' => 'Toyota',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $modelId = DB::table('models')->insertGetId([
            'make_id' => $makeId,
            'name' => 'Corolla',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('category_field_option_ranks')->insert([
            [
                'category_id' => 1,
                'field_name' => 'brand',
                'option_value' => 'Toyota',
                'rank' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 1,
                'field_name' => "model_make_id_{$makeId}",
                'option_value' => 'Corolla',
                'rank' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return [
            'make_id' => $makeId,
            'model_id' => $modelId,
        ];
    }
}
