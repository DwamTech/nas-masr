<?php

namespace Tests\Feature\Guest;

use App\Models\BestAdvertiser;
use App\Models\Category;
use App\Models\CategoryField;
use App\Models\Listing;
use App\Models\User;

/**
 * Trait CreatesTestData
 * 
 * Provides helper methods for creating test data that all guest tests can use.
 * This trait simplifies the creation of common test data patterns.
 */
trait CreatesTestData
{
    /**
     * Create an active category
     * 
     * @param array $attributes Additional attributes to override defaults
     * @return Category
     */
    protected function createActiveCategory(array $attributes = []): Category
    {
        return Category::factory()->create(array_merge([
            'is_active' => true,
            'sort_order' => 1,
        ], $attributes));
    }

    /**
     * Create a valid listing (status='Valid', non-expired)
     * 
     * @param Category|null $category The category for the listing
     * @param array $attributes Additional attributes to override defaults
     * @return Listing
     */
    protected function createValidListing(?Category $category = null, array $attributes = []): Listing
    {
        if (!$category) {
            $category = $this->createActiveCategory();
        }

        return Listing::factory()->create(array_merge([
            'category_id' => $category->id,
            'status' => 'Valid',
            'expire_at' => now()->addDays(30),
            'admin_approved' => true,
            'published_at' => now(),
        ], $attributes));
    }

    /**
     * Create a best advertiser with active user
     * 
     * @param User|null $user The user to be marked as best advertiser
     * @param array $categoryIds Array of category IDs
     * @param array $attributes Additional attributes to override defaults
     * @return BestAdvertiser
     */
    protected function createBestAdvertiser(?User $user = null, array $categoryIds = [], array $attributes = []): BestAdvertiser
    {
        if (!$user) {
            $user = User::factory()->create(['status' => 'active']);
        }

        if (empty($categoryIds)) {
            $category = $this->createActiveCategory();
            $categoryIds = [$category->id];
        }

        return BestAdvertiser::create(array_merge([
            'user_id' => $user->id,
            'category_ids' => $categoryIds,
            'is_active' => true,
            'max_listings' => 10,
        ], $attributes));
    }

    /**
     * Create a category field with options
     * 
     * @param string $categorySlug The category slug
     * @param array $attributes Additional attributes to override defaults
     * @return CategoryField
     */
    protected function createCategoryField(string $categorySlug, array $attributes = []): CategoryField
    {
        return CategoryField::create(array_merge([
            'category_slug' => $categorySlug,
            'field_name' => $attributes['field_name'] ?? 'test_field',
            'display_name' => $attributes['display_name'] ?? 'Test Field',
            'type' => 'string',
            'required' => false,
            'filterable' => true,
            'options' => [],
            'is_active' => true,
            'sort_order' => 1,
        ], $attributes));
    }
}
