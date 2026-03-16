<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DashboardFilterListsCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardFilterListsCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_governorates_cache_is_invalidated_after_governorate_write(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        DB::table('governorates')->insert([
            'id' => 1,
            'name' => 'القاهرة',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/filter-lists/governorates')
            ->assertOk()
            ->assertJsonFragment(['name' => 'القاهرة']);

        $this->assertTrue(Cache::has(DashboardFilterListsCache::governorates()));

        $this->actingAs($admin)
            ->postJson('/api/admin/governorates', ['name' => 'الجيزة'])
            ->assertCreated();

        $this->assertFalse(Cache::has(DashboardFilterListsCache::governorates()));

        $this->actingAs($admin)
            ->getJson('/api/admin/filter-lists/governorates')
            ->assertOk()
            ->assertJsonFragment(['name' => 'الجيزة']);
    }

    public function test_sections_cache_is_invalidated_after_main_section_write(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        DB::table('categories')->insert([
            'id' => 1,
            'slug' => 'animals',
            'name' => 'حيوانات',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('category_main_sections')->insert([
            'category_id' => 1,
            'name' => 'حيوانات أليفة',
            'title' => 'حيوانات أليفة',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/filter-lists/sections?category_slug=animals')
            ->assertOk()
            ->assertJsonFragment(['name' => 'حيوانات أليفة']);

        $this->assertTrue(Cache::has(DashboardFilterListsCache::sections('animals')));

        $this->actingAs($admin)
            ->postJson('/api/admin/main-section/animals', ['name' => 'طيور'])
            ->assertCreated();

        $this->assertFalse(Cache::has(DashboardFilterListsCache::sections('animals')));

        $this->actingAs($admin)
            ->getJson('/api/admin/filter-lists/sections?category_slug=animals')
            ->assertOk()
            ->assertJsonFragment(['name' => 'طيور']);
    }

    public function test_automotive_cache_is_invalidated_after_make_write(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

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
            'category_slug' => 'spare-parts',
            'field_name' => 'condition',
            'display_name' => 'الحالة',
            'type' => 'select',
            'required' => false,
            'filterable' => true,
            'options' => json_encode(['جديد', 'غير ذلك'], JSON_UNESCAPED_UNICODE),
            'rules_json' => json_encode([]),
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/filter-lists/automotive')
            ->assertOk();

        $this->actingAs($admin)
            ->getJson('/api/admin/filter-lists/field-category?category_slug=spare-parts')
            ->assertOk();

        $this->assertTrue(Cache::has(DashboardFilterListsCache::automotive()));
        $this->assertTrue(Cache::has(DashboardFilterListsCache::fieldCategory('spare-parts')));

        $this->actingAs($admin)
            ->postJson('/api/admin/makes', ['name' => 'مازدا'])
            ->assertCreated();

        $this->assertFalse(Cache::has(DashboardFilterListsCache::automotive()));
        $this->assertFalse(Cache::has(DashboardFilterListsCache::fieldCategory('spare-parts')));

        $this->actingAs($admin)
            ->getJson('/api/admin/filter-lists/automotive')
            ->assertOk()
            ->assertJsonFragment(['name' => 'مازدا']);
    }

    public function test_field_category_cache_is_invalidated_after_rank_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        DB::table('categories')->insert([
            'id' => 1,
            'slug' => 'spare-parts',
            'name' => 'قطع غيار سيارات',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('category_fields')->insert([
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
        ]);

        DB::table('category_field_option_ranks')->insert([
            [
                'category_id' => 1,
                'field_name' => 'condition',
                'option_value' => 'جديد',
                'rank' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 1,
                'field_name' => 'condition',
                'option_value' => 'مستعمل',
                'rank' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/filter-lists/field-category?category_slug=spare-parts')
            ->assertOk()
            ->assertJsonPath('data.0.options.0', 'جديد');

        $this->assertTrue(Cache::has(DashboardFilterListsCache::fieldCategory('spare-parts')));

        $this->actingAs($admin)
            ->postJson('/api/admin/categories/spare-parts/options/ranks', [
                'field' => 'condition',
                'ranks' => [
                    ['option' => 'مستعمل', 'rank' => 1],
                    ['option' => 'جديد', 'rank' => 2],
                ],
            ])
            ->assertOk();

        $this->assertFalse(Cache::has(DashboardFilterListsCache::fieldCategory('spare-parts')));

        $this->actingAs($admin)
            ->getJson('/api/admin/filter-lists/field-category?category_slug=spare-parts')
            ->assertOk()
            ->assertJsonPath('data.0.field_name', 'condition');

        $this->assertTrue(Cache::has(DashboardFilterListsCache::fieldCategory('spare-parts')));
    }
}
