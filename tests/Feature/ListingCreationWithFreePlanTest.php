<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\CategoryPlanPrice;
use App\Models\UserPackages;
use App\Models\Governorate;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class ListingCreationWithFreePlanTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $category;
    protected $governorate;
    protected $city;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake storage for file uploads
        Storage::fake('uploads');

        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'user',
            'phone' => '01234567890',
        ]);

        // Create governorate and city (simple creation without name_ar)
        $this->governorate = Governorate::create([
            'name' => 'Cairo',
        ]);

        $this->city = City::create([
            'name' => 'Nasr City',
            'governorate_id' => $this->governorate->id,
        ]);

        // Create a test category
        $this->category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'title' => 'Test Category Title',
            'parent_id' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    /**
     * Helper method to create complete listing data
     */
    protected function getListingData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Test Ad',
            'description' => 'Test Description',
            'price' => 1000,
            'plan_type' => 'standard',
            'governorate_id' => $this->governorate->id,
            'governorate' => (string) $this->governorate->id,  // Must be string
            'city_id' => $this->city->id,
            'city' => (string) $this->city->id,                // Must be string
            'lat' => 30.0444,
            'lng' => 31.2357,
            'address' => 'Test Address, Cairo',
            'main_image' => UploadedFile::fake()->image('test-ad.jpg', 800, 600),
        ], $overrides);
    }

    /**
     * Test: When plan price is 0, ad should be accepted without package or balance
     */
    public function test_ad_accepted_when_plan_price_is_zero()
    {
        // Set plan price to 0
        CategoryPlanPrice::create([
            'category_id' => $this->category->id,
            'featured_ad_price' => 0,
            'featured_days' => 30,
            'standard_ad_price' => 0,
            'standard_days' => 15,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/{$this->category->slug}/listings", 
            $this->getListingData()
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'status',
            ],
            'payment' => [
                'type',
                'price',
            ],
        ]);

        $response->assertJson([
            'payment' => [
                'type' => 'free_plan',
                'price' => 0,
            ],
        ]);

        $this->assertDatabaseHas('listings', [
            'title' => 'Test Ad',
            'user_id' => $this->user->id,
            'publish_via' => 'free_plan',
        ]);

        echo "\n✅ Test 1 Passed: Ad accepted when plan price is 0\n";
    }

    /**
     * Test: When plan price is 0 for featured, ad should be accepted
     */
    public function test_featured_ad_accepted_when_price_is_zero()
    {
        CategoryPlanPrice::create([
            'category_id' => $this->category->id,
            'featured_ad_price' => 0,
            'featured_days' => 30,
            'standard_ad_price' => 100,
            'standard_days' => 15,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/{$this->category->slug}/listings", 
            $this->getListingData([
                'title' => 'Featured Test Ad',
                'plan_type' => 'featured',
                'price' => 5000,
            ])
        );

        $response->assertStatus(200);
        $response->assertJson([
            'payment' => [
                'type' => 'free_plan',
                'price' => 0,
            ],
        ]);

        echo "\n✅ Test 2 Passed: Featured ad accepted when price is 0\n";
    }

    /**
     * Test: When plan price > 0 and no package, payment should be required
     */
    public function test_payment_required_when_price_not_zero_and_no_package()
    {
        CategoryPlanPrice::create([
            'category_id' => $this->category->id,
            'featured_ad_price' => 100,
            'featured_days' => 30,
            'standard_ad_price' => 50,
            'standard_days' => 15,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/{$this->category->slug}/listings", 
            $this->getListingData([
                'title' => 'Paid Test Ad',
            ])
        );

        $response->assertStatus(402);
        $response->assertJson([
            'success' => false,
            'payment_required' => true,
        ]);

        echo "\n✅ Test 3 Passed: Payment required when price > 0 and no package\n";
    }

    /**
     * Test: When plan price > 0 but user has package balance, ad should be accepted
     */
    public function test_ad_accepted_when_user_has_package_balance()
    {
        CategoryPlanPrice::create([
            'category_id' => $this->category->id,
            'featured_ad_price' => 100,
            'featured_days' => 30,
            'standard_ad_price' => 50,
            'standard_days' => 15,
        ]);

        // Create package with balance
        UserPackages::create([
            'user_id' => $this->user->id,
            'standard_ads' => 5,
            'standard_ads_used' => 0,
            'standard_days' => 15,
            'categories' => [$this->category->id],
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/{$this->category->slug}/listings", 
            $this->getListingData([
                'title' => 'Package Test Ad',
            ])
        );

        $response->assertStatus(200);
        $response->assertJson([
            'payment' => [
                'type' => 'package',
            ],
        ]);

        // Verify package balance was consumed
        $this->assertDatabaseHas('user_packages', [
            'user_id' => $this->user->id,
            'standard_ads_used' => 1,
        ]);

        echo "\n✅ Test 4 Passed: Ad accepted when user has package balance\n";
    }

    /**
     * Test: When plan price > 0 and package balance is 0, payment should be required
     */
    public function test_payment_required_when_package_balance_is_zero()
    {
        CategoryPlanPrice::create([
            'category_id' => $this->category->id,
            'standard_ad_price' => 50,
            'standard_days' => 15,
        ]);

        // Create package with no balance
        UserPackages::create([
            'user_id' => $this->user->id,
            'standard_ads' => 5,
            'standard_ads_used' => 5, // All used
            'standard_days' => 15,
            'categories' => [$this->category->id],
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/{$this->category->slug}/listings", 
            $this->getListingData([
                'title' => 'No Balance Test Ad',
            ])
        );

        $response->assertStatus(402);
        $response->assertJson([
            'success' => false,
            'payment_required' => true,
        ]);

        echo "\n✅ Test 5 Passed: Payment required when package balance is 0\n";
    }

    /**
     * Test: Admin can create ad regardless of price or balance
     */
    public function test_admin_can_create_ad_without_restrictions()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'phone' => '01111111111',
        ]);

        CategoryPlanPrice::create([
            'category_id' => $this->category->id,
            'standard_ad_price' => 100,
            'standard_days' => 15,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/{$this->category->slug}/listings", 
            $this->getListingData([
                'title' => 'Admin Test Ad',
            ])
        );

        $response->assertStatus(200);
        $response->assertJson([
            'payment' => [
                'type' => 'admin_bypass',
                'price' => 0,
            ],
        ]);

        echo "\n✅ Test 6 Passed: Admin can create ad without restrictions\n";
    }
}
