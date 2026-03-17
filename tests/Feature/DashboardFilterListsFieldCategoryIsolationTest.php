<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardFilterListsFieldCategoryIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_employee_with_categories_filters_can_read_dashboard_field_category(): void
    {
        $ctx = $this->seedFieldCategoryData();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['categories.filters'],
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/filter-lists/field-category?category_slug=spare-parts')
            ->assertOk()
            ->assertJsonPath('supports_make_model', true)
            ->assertJsonPath('supports_sections', true)
            ->assertJsonPath('data.0.field_name', 'condition')
            ->assertJsonPath('data.0.options.0', 'مستعمل')
            ->assertJsonPath('data.0.options.1', 'جديد')
            ->assertJsonPath('makes.0.name', 'تويوتا')
            ->assertJsonPath('makes.0.models.0.name', 'كورولا')
            ->assertJsonPath('main_sections.0.name', 'محركات')
            ->assertJsonFragment([
                'id' => $ctx['sub_section_id'],
                'name' => 'ديزل',
            ]);

        $this->actingAs($employee)
            ->getJson('/api/admin/filter-lists/field-category?category_slug=spare-parts')
            ->assertOk()
            ->assertJsonPath('supports_make_model', true)
            ->assertJsonPath('supports_sections', true);
    }

    public function test_employee_without_categories_filters_permission_is_blocked_from_dashboard_field_category(): void
    {
        $this->seedFieldCategoryData();

        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['dashboard.home'],
        ]);

        $this->actingAs($employee)
            ->getJson('/api/admin/filter-lists/field-category?category_slug=spare-parts')
            ->assertStatus(403);
    }

    public function test_public_category_fields_route_remains_unchanged(): void
    {
        $this->seedFieldCategoryData();

        $this->getJson('/api/category-fields?category_slug=spare-parts')
            ->assertOk()
            ->assertJsonPath('supports_make_model', true)
            ->assertJsonPath('supports_sections', true)
            ->assertJsonPath('data.0.field_name', 'condition');
    }

    public function test_dashboard_field_category_route_uses_reasonable_query_count(): void
    {
        $this->seedFieldCategoryData();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $queries = 0;

        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->actingAs($admin)
            ->getJson('/api/admin/filter-lists/field-category?category_slug=spare-parts')
            ->assertOk();

        $this->assertLessThan(
            30,
            $queries,
            "Expected optimized dashboard field-category endpoint to stay under 30 queries, got {$queries}."
        );
    }

    public function test_hidden_field_options_are_excluded_from_public_reads_but_available_for_management(): void
    {
        $this->seedFieldCategoryData();

        DB::table('category_fields')
            ->where('category_slug', 'spare-parts')
            ->where('field_name', 'condition')
            ->update([
                'rules_json' => json_encode([
                    'hidden_options' => ['جديد'],
                ], JSON_UNESCAPED_UNICODE),
            ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->getJson('/api/category-fields?category_slug=spare-parts')
            ->assertOk()
            ->assertJsonPath('data.0.options.0', 'مستعمل')
            ->assertJsonPath('data.0.options.1', 'غير ذلك');

        $this->actingAs($admin)
            ->getJson('/api/admin/filter-lists/field-category?category_slug=spare-parts&include_hidden=1')
            ->assertOk()
            ->assertJsonPath('data.0.options.0', 'مستعمل')
            ->assertJsonPath('data.0.options.1', 'جديد');
    }

    public function test_hidden_automotive_options_are_excluded_from_public_category_fields_reads(): void
    {
        $ctx = $this->seedFieldCategoryData();

        DB::table('makes')
            ->where('id', $ctx['nissan_id'])
            ->update(['is_active' => false]);

        DB::table('models')
            ->where('make_id', $ctx['toyota_id'])
            ->where('name', 'كورولا')
            ->update(['is_active' => false]);

        $response = $this->getJson('/api/category-fields?category_slug=spare-parts')
            ->assertOk();

        $makes = $response->json('makes');

        $this->assertIsArray($makes);
        $this->assertSame(['تويوتا', 'غير ذلك'], array_column($makes, 'name'));
        $this->assertSame(['غير ذلك'], array_column($makes[0]['models'], 'name'));
    }

    public function test_hidden_governorates_and_sections_are_excluded_from_public_category_fields_reads(): void
    {
        $this->seedFieldCategoryData();

        DB::table('governorates')->insert([
            [
                'name' => 'القاهرة',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'الجيزة',
                'sort_order' => 2,
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $cairoId = DB::table('governorates')->where('name', 'القاهرة')->value('id');
        $gizaId = DB::table('governorates')->where('name', 'الجيزة')->value('id');

        DB::table('cities')->insert([
            [
                'name' => 'مدينة نصر',
                'governorate_id' => $cairoId,
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'الدقي',
                'governorate_id' => $cairoId,
                'sort_order' => 2,
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'الهرم',
                'governorate_id' => $gizaId,
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('category_sub_section')->insert([
            'category_id' => 2,
            'main_section_id' => 1,
            'name' => 'بنزين',
            'title' => 'بنزين',
            'sort_order' => 2,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('category_main_sections')->where('category_id', 2)->where('name', 'إكسسوارات')->update([
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/category-fields?category_slug=spare-parts')
            ->assertOk();

        $governorates = $response->json('governorates');
        $mainSections = $response->json('main_sections');

        $this->assertSame(['القاهرة'], array_column($governorates, 'name'));
        $this->assertSame(['مدينة نصر'], array_column($governorates[0]['cities'], 'name'));

        $this->assertSame(['محركات'], array_column($mainSections, 'name'));
        $this->assertSame(['ديزل'], array_column($mainSections[0]['sub_sections'], 'name'));
    }

    protected function seedFieldCategoryData(): array
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
                'slug' => 'spare-parts',
                'name' => 'قطع غيار سيارات',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('category_fields')->insert([
            [
                'category_slug' => 'spare-parts',
                'field_name' => 'condition',
                'display_name' => 'الحالة',
                'type' => 'select',
                'required' => false,
                'filterable' => true,
                'options' => json_encode(['جديد', 'مستعمل', 'غير ذلك'], JSON_UNESCAPED_UNICODE),
                'rules_json' => json_encode([]),
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_slug' => 'spare-parts',
                'field_name' => 'brand',
                'display_name' => 'الماركة',
                'type' => 'select',
                'required' => false,
                'filterable' => true,
                'options' => json_encode(['تويوتا', 'نيسان', 'غير ذلك'], JSON_UNESCAPED_UNICODE),
                'rules_json' => json_encode([]),
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_slug' => 'spare-parts',
                'field_name' => 'model',
                'display_name' => 'الموديل',
                'type' => 'select',
                'required' => false,
                'filterable' => true,
                'options' => json_encode(['كورولا', 'صني', 'غير ذلك'], JSON_UNESCAPED_UNICODE),
                'rules_json' => json_encode([]),
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $toyotaId = DB::table('makes')->insertGetId([
            'name' => 'تويوتا',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $nissanId = DB::table('makes')->insertGetId([
            'name' => 'نيسان',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('models')->insert([
            [
                'make_id' => $toyotaId,
                'name' => 'كورولا',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'make_id' => $nissanId,
                'name' => 'صني',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $mainSectionId = DB::table('category_main_sections')->insertGetId([
            'category_id' => 2,
            'name' => 'محركات',
            'title' => 'محركات',
            'sort_order' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('category_main_sections')->insert([
            'category_id' => 2,
            'name' => 'إكسسوارات',
            'title' => 'إكسسوارات',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subSectionId = DB::table('category_sub_section')->insertGetId([
            'category_id' => 2,
            'main_section_id' => $mainSectionId,
            'name' => 'ديزل',
            'title' => 'ديزل',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('category_field_option_ranks')->insert([
            [
                'category_id' => 2,
                'field_name' => 'condition',
                'option_value' => 'مستعمل',
                'rank' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 2,
                'field_name' => 'condition',
                'option_value' => 'جديد',
                'rank' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 1,
                'field_name' => 'brand',
                'option_value' => 'تويوتا',
                'rank' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 1,
                'field_name' => 'brand',
                'option_value' => 'نيسان',
                'rank' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 1,
                'field_name' => "model_make_id_{$toyotaId}",
                'option_value' => 'كورولا',
                'rank' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 1,
                'field_name' => "model_make_id_{$nissanId}",
                'option_value' => 'صني',
                'rank' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 2,
                'field_name' => 'MainSection',
                'option_value' => 'محركات',
                'rank' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 2,
                'field_name' => 'MainSection',
                'option_value' => 'إكسسوارات',
                'rank' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 2,
                'field_name' => 'SubSection_محركات',
                'option_value' => 'ديزل',
                'rank' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return [
            'toyota_id' => $toyotaId,
            'nissan_id' => $nissanId,
            'main_section_id' => $mainSectionId,
            'sub_section_id' => $subSectionId,
        ];
    }
}
