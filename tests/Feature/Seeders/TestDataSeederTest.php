<?php

namespace Tests\Feature\Seeders;

use App\Models\Category;
use App\Models\CategoryMainSection;
use App\Models\CategorySubSection;
use App\Models\City;
use App\Models\Governorate;
use App\Models\Make;
use App\Models\CarModel;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestDataSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that TestDataSeeder creates basic categories
     */
    public function test_seeder_creates_basic_categories(): void
    {
        $this->seed(TestDataSeeder::class);

        $this->assertDatabaseHas('categories', ['slug' => 'cars', 'name' => 'السيارات']);
        $this->assertDatabaseHas('categories', ['slug' => 'jobs', 'name' => 'الوظائف']);
        $this->assertDatabaseHas('categories', ['slug' => 'real_estate', 'name' => 'العقارات']);

        $categories = Category::whereIn('slug', ['cars', 'jobs', 'real_estate'])->get();
        $this->assertCount(3, $categories);
        
        foreach ($categories as $category) {
            $this->assertTrue($category->is_active);
        }
    }

    /**
     * Test that TestDataSeeder creates governorates and cities
     */
    public function test_seeder_creates_governorates_and_cities(): void
    {
        $this->seed(TestDataSeeder::class);

        // Check governorates
        $this->assertDatabaseHas('governorates', ['name' => 'القاهرة']);
        $this->assertDatabaseHas('governorates', ['name' => 'الجيزة']);
        $this->assertDatabaseHas('governorates', ['name' => 'الإسكندرية']);

        // Check cities
        $cairo = Governorate::where('name', 'القاهرة')->first();
        $this->assertNotNull($cairo);
        $this->assertDatabaseHas('cities', ['governorate_id' => $cairo->id, 'name' => 'مدينة نصر']);
        $this->assertDatabaseHas('cities', ['governorate_id' => $cairo->id, 'name' => 'مصر الجديدة']);

        $giza = Governorate::where('name', 'الجيزة')->first();
        $this->assertNotNull($giza);
        $this->assertDatabaseHas('cities', ['governorate_id' => $giza->id, 'name' => 'الدقي']);
        $this->assertDatabaseHas('cities', ['governorate_id' => $giza->id, 'name' => '6 أكتوبر']);
    }

    /**
     * Test that TestDataSeeder creates makes and models
     */
    public function test_seeder_creates_makes_and_models(): void
    {
        $this->seed(TestDataSeeder::class);

        // Check makes
        $this->assertDatabaseHas('makes', ['name' => 'تويوتا']);
        $this->assertDatabaseHas('makes', ['name' => 'هيونداي']);
        $this->assertDatabaseHas('makes', ['name' => 'كيا']);

        // Check models
        $toyota = Make::where('name', 'تويوتا')->first();
        $this->assertNotNull($toyota);
        $this->assertDatabaseHas('models', ['make_id' => $toyota->id, 'name' => 'كورولا']);
        $this->assertDatabaseHas('models', ['make_id' => $toyota->id, 'name' => 'كامري']);

        $hyundai = Make::where('name', 'هيونداي')->first();
        $this->assertNotNull($hyundai);
        $this->assertDatabaseHas('models', ['make_id' => $hyundai->id, 'name' => 'إلنترا']);
        $this->assertDatabaseHas('models', ['make_id' => $hyundai->id, 'name' => 'أكسنت']);
    }

    /**
     * Test that TestDataSeeder creates main sections and sub sections
     */
    public function test_seeder_creates_sections(): void
    {
        $this->seed(TestDataSeeder::class);

        $cars = Category::where('slug', 'cars')->first();
        $this->assertNotNull($cars);

        // Check main sections
        $this->assertDatabaseHas('category_main_sections', [
            'category_id' => $cars->id,
            'name' => 'سيارات ركوب'
        ]);
        $this->assertDatabaseHas('category_main_sections', [
            'category_id' => $cars->id,
            'name' => 'سيارات تجارية'
        ]);

        // Check sub sections
        $mainSection = CategoryMainSection::where('category_id', $cars->id)
            ->where('name', 'سيارات ركوب')
            ->first();
        $this->assertNotNull($mainSection);

        $this->assertDatabaseHas('category_sub_section', [
            'category_id' => $cars->id,
            'main_section_id' => $mainSection->id,
            'name' => 'سيدان'
        ]);
        $this->assertDatabaseHas('category_sub_section', [
            'category_id' => $cars->id,
            'main_section_id' => $mainSection->id,
            'name' => 'SUV'
        ]);
    }

    /**
     * Test that seeder can be run multiple times without errors
     */
    public function test_seeder_is_idempotent(): void
    {
        $this->seed(TestDataSeeder::class);
        $firstCount = Category::whereIn('slug', ['cars', 'jobs', 'real_estate'])->count();

        // Run seeder again
        $this->seed(TestDataSeeder::class);
        $secondCount = Category::whereIn('slug', ['cars', 'jobs', 'real_estate'])->count();

        // Should have same count (no duplicates)
        $this->assertEquals($firstCount, $secondCount);
    }
}
