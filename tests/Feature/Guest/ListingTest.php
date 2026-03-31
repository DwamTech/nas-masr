<?php

namespace Tests\Feature\Guest;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;

/**
 * @group guest
 * @group listings
 */
class ListingTest extends GuestTestCase
{
    use CreatesTestData;

    /**
     * Test that guest can view listings for section
     * 
     * **Validates: Requirements 5.1**
     */
    public function test_guest_can_view_listings_for_section(): void
    {
        // Create a category
        $category = $this->createActiveCategory(['slug' => 'test-listings', 'name' => 'اختبار الإعلانات']);
        
        // Create valid listings
        $this->createValidListing($category, ['title' => 'إعلان 1', 'price' => 1000]);
        $this->createValidListing($category, ['title' => 'إعلان 2', 'price' => 2000]);

        $response = $this->guestGet('/api/v1/test-listings/listings');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'title',
                'price',
                'category',
                'category_name',
                'governorate',
                'city',
                'main_image_url',
                'attributes',
                'rank',
                'views',
            ]
        ]);
        
        $data = $response->json();
        $this->assertGreaterThanOrEqual(2, count($data));
    }

    /**
     * Test that only valid non-expired listings are returned
     * 
     * **Validates: Requirements 5.2**
     */
    public function test_only_valid_non_expired_listings_returned(): void
    {
        // Create a category
        $category = $this->createActiveCategory(['slug' => 'valid-listings-test', 'name' => 'اختبار الإعلانات الصحيحة']);
        
        // Create a valid, non-expired listing
        $validListing = $this->createValidListing($category, [
            'title' => 'إعلان صحيح',
            'status' => 'Valid',
            'expire_at' => now()->addDays(30),
            'price' => 1000,
        ]);
        
        // Create a pending listing (should not appear)
        Listing::factory()->create([
            'category_id' => $category->id,
            'title' => 'إعلان معلق',
            'status' => 'Pending',
            'price' => 2000,
        ]);
        
        // Create a rejected listing (should not appear)
        Listing::factory()->create([
            'category_id' => $category->id,
            'title' => 'إعلان مرفوض',
            'status' => 'Rejected',
            'price' => 3000,
        ]);
        
        // Create an expired listing (should not appear)
        Listing::factory()->create([
            'category_id' => $category->id,
            'title' => 'إعلان منتهي',
            'status' => 'Valid',
            'expire_at' => now()->subDays(1),
            'price' => 4000,
        ]);

        $response = $this->guestGet('/api/v1/valid-listings-test/listings');

        $response->assertStatus(200);
        
        $data = $response->json();
        
        // Only 1 valid listing should be returned
        $this->assertCount(1, $data);
        $this->assertEquals('إعلان صحيح', $data[0]['title']);
        $this->assertEquals(1000, $data[0]['price']);
        $this->assertEquals($validListing->id, $data[0]['id']);
    }

    /**
     * Test that listings are ordered by rank
     * 
     * **Validates: Requirements 5.11**
     */
    public function test_listings_ordered_by_rank(): void
    {
        // Create a category
        $category = $this->createActiveCategory(['slug' => 'rank-order-test', 'name' => 'اختبار ترتيب الرتبة']);
        
        // Create listings with different ranks
        $this->createValidListing($category, [
            'title' => 'إعلان رتبة 3',
            'rank' => 3,
            'price' => 3000,
        ]);
        
        $this->createValidListing($category, [
            'title' => 'إعلان رتبة 1',
            'rank' => 1,
            'price' => 1000,
        ]);
        
        $this->createValidListing($category, [
            'title' => 'إعلان رتبة 2',
            'rank' => 2,
            'price' => 2000,
        ]);

        $response = $this->guestGet('/api/v1/rank-order-test/listings');

        $response->assertStatus(200);
        
        $data = $response->json();
        
        // Verify we have 3 listings
        $this->assertCount(3, $data);
        
        // Verify ordering by rank (ascending)
        $this->assertEquals(1, $data[0]['rank']);
        $this->assertEquals('إعلان رتبة 1', $data[0]['title']);
        $this->assertEquals(1000, $data[0]['price']);
        
        $this->assertEquals(2, $data[1]['rank']);
        $this->assertEquals('إعلان رتبة 2', $data[1]['title']);
        $this->assertEquals(2000, $data[1]['price']);
        
        $this->assertEquals(3, $data[2]['rank']);
        $this->assertEquals('إعلان رتبة 3', $data[2]['title']);
        $this->assertEquals(3000, $data[2]['price']);
    }

    /**
     * Test that invalid section returns error
     * 
     * **Validates: Requirements 5.12**
     */
    public function test_invalid_section_returns_error(): void
    {
        $response = $this->guestGet('/api/v1/invalid-section-slug-99999/listings');

        // Should return 404 because Section::fromSlug() will throw ModelNotFoundException
        $response->assertStatus(404);
    }

    /**
     * Test that keyword search filters listings
     * 
     * **Validates: Requirements 5.3**
     */
    public function test_keyword_search_filters_listings(): void
    {
        // Create a category
        $category = $this->createActiveCategory(['slug' => 'search-test', 'name' => 'اختبار البحث']);
        
        // Create listings with different titles
        $this->createValidListing($category, [
            'title' => 'سيارة تويوتا كامري',
            'price' => 50000,
        ]);
        
        $this->createValidListing($category, [
            'title' => 'سيارة هوندا أكورد',
            'price' => 45000,
        ]);
        
        $this->createValidListing($category, [
            'title' => 'شقة للبيع',
            'price' => 100000,
        ]);

        // Search for "تويوتا"
        $response = $this->guestGet('/api/v1/search-test/listings?q=تويوتا');

        $response->assertStatus(200);
        
        $data = $response->json();
        
        // Only 1 listing should match
        $this->assertCount(1, $data);
        $this->assertStringContainsString('تويوتا', $data[0]['title']);
        $this->assertEquals(50000, $data[0]['price']);
    }

    /**
     * Test that governorate filter works
     * 
     * **Validates: Requirements 5.4**
     */
    public function test_governorate_filter_works(): void
    {
        // Create a category
        $category = $this->createActiveCategory(['slug' => 'gov-filter-test', 'name' => 'اختبار فلتر المحافظة']);
        
        // Create governorates
        $cairo = \App\Models\Governorate::create(['name' => 'القاهرة', 'is_visible' => true]);
        $giza = \App\Models\Governorate::create(['name' => 'الجيزة', 'is_visible' => true]);
        
        // Create listings in different governorates
        $this->createValidListing($category, [
            'title' => 'إعلان القاهرة',
            'governorate_id' => $cairo->id,
            'price' => 1000,
        ]);
        
        $this->createValidListing($category, [
            'title' => 'إعلان الجيزة',
            'governorate_id' => $giza->id,
            'price' => 2000,
        ]);

        // Filter by Cairo governorate ID
        $response = $this->guestGet('/api/v1/gov-filter-test/listings?governorate_id=' . $cairo->id);

        $response->assertStatus(200);
        
        $data = $response->json();
        
        // Only 1 listing from Cairo should be returned
        $this->assertCount(1, $data);
        $this->assertEquals('إعلان القاهرة', $data[0]['title']);
        $this->assertEquals('القاهرة', $data[0]['governorate']);
    }

    /**
     * Test that city filter works
     * 
     * **Validates: Requirements 5.5**
     */
    public function test_city_filter_works(): void
    {
        // Create a category
        $category = $this->createActiveCategory(['slug' => 'city-filter-test', 'name' => 'اختبار فلتر المدينة']);
        
        // Create governorate and cities
        $cairo = \App\Models\Governorate::create(['name' => 'القاهرة', 'is_visible' => true]);
        $nasr = \App\Models\City::create(['name' => 'مدينة نصر', 'governorate_id' => $cairo->id, 'is_visible' => true]);
        $heliopolis = \App\Models\City::create(['name' => 'مصر الجديدة', 'governorate_id' => $cairo->id, 'is_visible' => true]);
        
        // Create listings in different cities
        $this->createValidListing($category, [
            'title' => 'إعلان مدينة نصر',
            'governorate_id' => $cairo->id,
            'city_id' => $nasr->id,
            'price' => 1000,
        ]);
        
        $this->createValidListing($category, [
            'title' => 'إعلان مصر الجديدة',
            'governorate_id' => $cairo->id,
            'city_id' => $heliopolis->id,
            'price' => 2000,
        ]);

        // Filter by Nasr City ID
        $response = $this->guestGet('/api/v1/city-filter-test/listings?city_id=' . $nasr->id);

        $response->assertStatus(200);
        
        $data = $response->json();
        
        // Only 1 listing from Nasr City should be returned
        $this->assertCount(1, $data);
        $this->assertEquals('إعلان مدينة نصر', $data[0]['title']);
        $this->assertEquals('مدينة نصر', $data[0]['city']);
    }

    /**
     * Test that price range filter works
     * 
     * **Validates: Requirements 5.6**
     */
    public function test_price_range_filter_works(): void
    {
        // Create a category
        $category = $this->createActiveCategory(['slug' => 'price-filter-test', 'name' => 'اختبار فلتر السعر']);
        
        // Create listings with different prices
        $this->createValidListing($category, [
            'title' => 'إعلان رخيص',
            'price' => 1000,
        ]);
        
        $this->createValidListing($category, [
            'title' => 'إعلان متوسط',
            'price' => 5000,
        ]);
        
        $this->createValidListing($category, [
            'title' => 'إعلان غالي',
            'price' => 10000,
        ]);

        // Filter by price range 2000-6000
        $response = $this->guestGet('/api/v1/price-filter-test/listings?price_min=2000&price_max=6000');

        $response->assertStatus(200);
        
        $data = $response->json();
        
        // Only 1 listing in the price range should be returned
        $this->assertCount(1, $data);
        $this->assertEquals('إعلان متوسط', $data[0]['title']);
        $this->assertEquals(5000, $data[0]['price']);
        
        // Verify price is within range
        $this->assertGreaterThanOrEqual(2000, $data[0]['price']);
        $this->assertLessThanOrEqual(6000, $data[0]['price']);
    }
}
