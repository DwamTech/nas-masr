<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardFilterListsSectionsIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_employee_with_categories_filters_can_read_dashboard_sections(): void
    {
        $ctx = $this->seedSections();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['categories.filters'],
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/filter-lists/sections?category_slug=animals')
            ->assertOk()
            ->assertJsonPath('category.slug', 'animals')
            ->assertJsonFragment([
                'id' => $ctx['main_section_id'],
                'name' => 'حيوانات أليفة',
            ])
            ->assertJsonFragment([
                'id' => $ctx['sub_section_id'],
                'name' => 'قطط',
                'main_section_id' => $ctx['main_section_id'],
            ]);

        $this->actingAs($employee)
            ->getJson('/api/admin/filter-lists/sections?category_slug=animals')
            ->assertOk()
            ->assertJsonPath('category.slug', 'animals');

        $this->actingAs($employee)
            ->getJson("/api/admin/filter-lists/sections/{$ctx['main_section_id']}/sub-sections")
            ->assertOk()
            ->assertJsonFragment([
                'id' => $ctx['sub_section_id'],
                'name' => 'قطط',
                'main_section_id' => $ctx['main_section_id'],
            ]);
    }

    public function test_employee_without_categories_filters_permission_is_blocked_from_dashboard_sections(): void
    {
        $this->seedSections();

        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['dashboard.home'],
        ]);

        $this->actingAs($employee)
            ->getJson('/api/admin/filter-lists/sections?category_slug=animals')
            ->assertStatus(403);
    }

    public function test_public_sections_routes_remain_unchanged(): void
    {
        $ctx = $this->seedSections();

        $this->getJson('/api/main-sections?category_slug=animals')
            ->assertOk()
            ->assertJsonPath('category.slug', 'animals')
            ->assertJsonFragment([
                'name' => 'غير ذلك',
            ]);

        $this->getJson("/api/sub-sections/{$ctx['main_section_id']}")
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'غير ذلك',
            ]);
    }

    protected function seedSections(): array
    {
        DB::table('categories')->insert([
            'id' => 1,
            'slug' => 'animals',
            'name' => 'حيوانات',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mainSectionId = DB::table('category_main_sections')->insertGetId([
            'category_id' => 1,
            'name' => 'حيوانات أليفة',
            'title' => 'حيوانات أليفة',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subSectionId = DB::table('category_sub_section')->insertGetId([
            'category_id' => 1,
            'main_section_id' => $mainSectionId,
            'name' => 'قطط',
            'title' => 'قطط',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'main_section_id' => $mainSectionId,
            'sub_section_id' => $subSectionId,
        ];
    }
}
