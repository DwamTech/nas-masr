<?php

namespace Tests\Feature\Guest;

use App\Models\Category;
use App\Models\CategoryBanner;
use App\Models\City;
use App\Models\Governorate;
use App\Models\Listing;
use App\Models\ListingAttribute;
use App\Models\User;

/**
 * @group guest
 * @group listing-detail
 */
class ListingDetailTest extends GuestTestCase
{
    use CreatesTestData;

    /**
     * Test that guest can view listing details
     * 
     * **Validates: Requirements 6.1**
     */
    public function test_guest_can_view_listing_details(): void
    {
        $category = $this->createActiveCategory(['slug' => 'test-category']);
        $listing = $this->createValidListing($category);

        $response = $this->guestGet("/api/v1/{$category->slug}/listings/{$listing->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'price',
            ]
        ]);
    }

    /**
     * Test that listing details include all required fields
     * 
     * **Validates: Requirements 6.2**
     */
    public function test_listing_details_include_all_fields(): void
    {
        $governorate = Governorate::create(['name' => 'Test Gov', 'is_visible' => true]);
        $city = City::create(['name' => 'Test City', 'governorate_id' => $governorate->id, 'is_visible' => true]);
        
        $category = $this->createActiveCategory(['slug' => 'test-category-fields']);
        $listing = $this->createValidListing($category, [
            'title' => 'Test Listing',
            'price' => 5000,
            'description' => 'Test description',
            'governorate_id' => $governorate->id,
            'city_id' => $city->id,
        ]);

        $response = $this->guestGet("/api/v1/{$category->slug}/listings/{$listing->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'price',
                'description',
                'attributes',
            ]
        ]);

        $data = $response->json('data');
        $this->assertEquals('Test Listing', $data['title']);
        $this->assertEquals(5000, $data['price']);
        $this->assertEquals('Test description', $data['description']);
    }

    /**
     * Test that owner information is included in listing details
     * 
     * **Validates: Requirements 6.3**
     */
    public function test_owner_info_included(): void
    {
        $user = User::factory()->create([
            'name' => 'Test Owner',
            'status' => 'active',
        ]);
        
        $category = $this->createActiveCategory(['slug' => 'test-owner']);
        $listing = $this->createValidListing($category, [
            'user_id' => $user->id,
        ]);

        $response = $this->guestGet("/api/v1/{$category->slug}/listings/{$listing->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => [
                'id',
                'name',
                'joined_at',
                'joined_at_human',
                'listings_count',
            ]
        ]);

        $userData = $response->json('user');
        $this->assertEquals($user->id, $userData['id']);
        $this->assertEquals('Test Owner', $userData['name']);
        $this->assertArrayHasKey('joined_at', $userData);
        $this->assertArrayHasKey('listings_count', $userData);
    }

    /**
     * Test that views counter is incremented when viewing listing
     * 
     * **Validates: Requirements 6.4**
     */
    public function test_views_counter_incremented(): void
    {
        $category = $this->createActiveCategory(['slug' => 'test-views']);
        $listing = $this->createValidListing($category, ['views' => 0]);

        $initialViews = $listing->views;

        $response = $this->guestGet("/api/v1/{$category->slug}/listings/{$listing->id}");

        $response->assertStatus(200);

        // Refresh the listing from database
        $listing->refresh();
        
        $this->assertEquals($initialViews + 1, $listing->views);
    }

    /**
     * Test that category banner is included in response
     * 
     * **Validates: Requirements 6.5**
     */
    public function test_category_banner_included(): void
    {
        $category = $this->createActiveCategory(['slug' => 'test-banner']);
        
        // Create a category banner
        CategoryBanner::create([
            'slug' => $category->slug,
            'banner_path' => 'banners/test-banner.jpg',
            'is_active' => true,
        ]);

        $listing = $this->createValidListing($category);

        $response = $this->guestGet("/api/v1/{$category->slug}/listings/{$listing->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => [
                'banner',
            ]
        ]);

        $userData = $response->json('user');
        $this->assertNotNull($userData['banner']);
    }

    /**
     * Test that related data (governorate, city, etc.) is loaded
     * 
     * **Validates: Requirements 6.6**
     */
    public function test_related_data_loaded(): void
    {
        $governorate = Governorate::create(['name' => 'Test Gov', 'is_visible' => true]);
        $city = City::create([
            'name' => 'Test City',
            'governorate_id' => $governorate->id,
            'is_visible' => true
        ]);
        
        $category = $this->createActiveCategory(['slug' => 'test-relations']);
        $listing = $this->createValidListing($category, [
            'governorate_id' => $governorate->id,
            'city_id' => $city->id,
        ]);

        // Create some attributes using the correct field structure
        $listing->attributes()->create([
            'key' => 'color',
            'type' => 'string',
            'value_string' => 'red',
        ]);

        $response = $this->guestGet("/api/v1/{$category->slug}/listings/{$listing->id}");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        
        // Verify attributes are loaded
        $this->assertArrayHasKey('attributes', $data);
        
        // Verify governorate and city are loaded
        $this->assertArrayHasKey('governorate', $data);
        $this->assertArrayHasKey('city', $data);
    }

    /**
     * Test that mismatched category returns 404
     * 
     * **Validates: Requirements 6.7**
     */
    public function test_mismatched_category_returns_404(): void
    {
        $category1 = $this->createActiveCategory(['slug' => 'category-1']);
        $category2 = $this->createActiveCategory(['slug' => 'category-2']);
        
        $listing = $this->createValidListing($category1);

        // Try to access listing with wrong category slug
        $response = $this->guestGet("/api/v1/{$category2->slug}/listings/{$listing->id}");

        $response->assertStatus(404);
    }

    /**
     * Test that invalid listing ID returns 404
     * 
     * **Validates: Requirements 6.8**
     */
    public function test_invalid_listing_id_returns_404(): void
    {
        $category = $this->createActiveCategory(['slug' => 'test-invalid']);

        // Try to access non-existent listing
        $response = $this->guestGet("/api/v1/{$category->slug}/listings/999999");

        $response->assertStatus(404);
    }
}
