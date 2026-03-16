<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminTaxonomyReadIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_with_categories_filters_can_read_admin_taxonomy_endpoints(): void
    {
        $ctx = $this->seedTaxonomyContext();

        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['categories.filters'],
        ]);

        $this->actingAs($employee)
            ->getJson('/api/admin/makes')
            ->assertOk();

        $this->actingAs($employee)
            ->getJson("/api/admin/makes/{$ctx['make_id']}")
            ->assertOk()
            ->assertJsonPath('id', $ctx['make_id']);

        $this->actingAs($employee)
            ->getJson('/api/admin/governorates')
            ->assertOk();

        $this->actingAs($employee)
            ->getJson("/api/admin/governorates/{$ctx['governorate_id']}")
            ->assertOk()
            ->assertJsonPath('id', $ctx['governorate_id']);

        $this->actingAs($employee)
            ->getJson('/api/admin/main-sections?category_slug=jobs')
            ->assertOk()
            ->assertJsonPath('category.slug', 'jobs');

        $this->actingAs($employee)
            ->getJson("/api/admin/sub-section/{$ctx['main_section_id']}")
            ->assertOk();
    }

    public function test_employee_with_ads_create_can_read_admin_taxonomy_needed_by_create_form(): void
    {
        $ctx = $this->seedTaxonomyContext();

        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['ads.create'],
        ]);

        $this->actingAs($employee)
            ->getJson('/api/admin/makes')
            ->assertOk();

        $this->actingAs($employee)
            ->getJson('/api/admin/governorates')
            ->assertOk();

        $this->actingAs($employee)
            ->getJson('/api/admin/main-sections?category_slug=jobs')
            ->assertOk();

        $this->actingAs($employee)
            ->getJson("/api/admin/sub-section/{$ctx['main_section_id']}")
            ->assertOk();
    }

    public function test_employee_without_allowed_page_is_blocked_from_admin_taxonomy_reads(): void
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['dashboard.home'],
        ]);

        $this->actingAs($employee)
            ->getJson('/api/admin/makes')
            ->assertStatus(403);
    }

    public function test_public_taxonomy_routes_remain_unchanged(): void
    {
        $ctx = $this->seedTaxonomyContext();

        $this->getJson('/api/makes')
            ->assertOk();

        $this->getJson('/api/governorates')
            ->assertOk();

        $this->getJson('/api/main-sections?category_slug=jobs')
            ->assertOk()
            ->assertJsonPath('category.slug', 'jobs');

        $this->getJson("/api/sub-sections/{$ctx['main_section_id']}")
            ->assertOk();
    }

    protected function seedTaxonomyContext(): array
    {
        DB::table('categories')->insert([
            [
                'id' => 1,
                'slug' => 'cars',
                'name' => 'سيارات',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'slug' => 'jobs',
                'name' => 'وظائف',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $makeId = DB::table('makes')->insertGetId([
            'name' => 'Toyota',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('models')->insert([
            'make_id' => $makeId,
            'name' => 'Corolla',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $governorateId = DB::table('governorates')->insertGetId([
            'name' => 'القاهرة',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cities')->insert([
            'governorate_id' => $governorateId,
            'name' => 'مدينة نصر',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mainSectionId = DB::table('category_main_sections')->insertGetId([
            'category_id' => 2,
            'name' => 'وظائف إدارية',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('category_sub_section')->insert([
            'category_id' => 2,
            'main_section_id' => $mainSectionId,
            'name' => 'سكرتارية',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'make_id' => $makeId,
            'governorate_id' => $governorateId,
            'main_section_id' => $mainSectionId,
        ];
    }
}
