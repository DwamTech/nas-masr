<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Integration Tests for Unified Category Images Management
 * 
 * These tests validate the complete end-to-end workflow of the unified category
 * images management feature, including:
 * - Complete workflow: toggle → upload → display
 * - Backward compatibility with existing listings
 * - Toggle reversion (disable → revert to original images)
 * 
 * Validates: Requirements 12.1, 12.3
 */
class UnifiedImagesIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        $this->admin = User::factory()->admin()->create();
        $this->category = Category::factory()->create([
            'name' => 'السيارات',
            'is_global_image_active' => false,
            'global_image_url' => null,
        ]);
    }

    /**
     * Test complete workflow: toggle enable → upload image → verify display
     * 
     * This test validates the entire user journey from enabling unified images
     * to uploading an image and verifying it's properly stored and accessible.
     * 
     * @test
     */
    public function complete_workflow_toggle_upload_display()
    {
        // Step 1: Enable unified image toggle
        $toggleResponse = $this->actingAs($this->admin)
            ->putJson("/api/admin/categories/{$this->category->id}/toggle-global-image", [
                'is_global_image_active' => true,
            ]);

        $toggleResponse->assertOk();
        $toggleResponse->assertJson([
            'id' => $this->category->id,
            'is_global_image_active' => true,
        ]);

        // Verify database state after toggle
        $this->category->refresh();
        $this->assertTrue($this->category->is_global_image_active);

        // Step 2: Upload unified image
        $imageFile = UploadedFile::fake()->image('category-image.jpg', 1000, 1000);
        
        $uploadResponse = $this->actingAs($this->admin)
            ->postJson("/api/admin/categories/{$this->category->id}/upload-global-image", [
                'image' => $imageFile,
            ]);

        $uploadResponse->assertOk();
        $uploadResponse->assertJsonStructure([
            'id',
            'global_image_url',
            'global_image_full_url',
            'is_global_image_active',
            'message',
        ]);

        // Verify database state after upload
        $this->category->refresh();
        $this->assertNotNull($this->category->global_image_url);
        $this->assertTrue($this->category->is_global_image_active);
        $this->assertStringEndsWith('.webp', $this->category->global_image_url);

        // Step 3: Verify image is stored in filesystem
        Storage::disk('public')->assertExists($this->category->global_image_url);

        // Step 4: Verify category data is accessible via database
        $categoryFromDb = Category::find($this->category->id);
        $this->assertNotNull($categoryFromDb);
        $this->assertTrue($categoryFromDb->is_global_image_active);
        $this->assertNotNull($categoryFromDb->global_image_url);
        $this->assertNotNull($categoryFromDb->global_image_full_url);
    }

    /**
     * Test backward compatibility with existing listings
     * 
     * This test ensures that existing listings continue to work correctly
     * when unified images are enabled, and that their original image data
     * is preserved and not modified.
     * 
     * Validates: Requirements 12.1, 12.4
     * 
     * @test
     */
    public function backward_compatibility_with_existing_listings()
    {
        // Create listings with original images before enabling unified images
        $user = User::factory()->create();
        
        $listing1 = Listing::create([
            'category_id' => $this->category->id,
            'user_id' => $user->id,
            'title' => 'إعلان 1',
            'price' => 1000,
            'currency' => 'EGP',
            'main_image' => 'listings/original-image-1.jpg',
            'status' => 'Valid',
        ]);

        $listing2 = Listing::create([
            'category_id' => $this->category->id,
            'user_id' => $user->id,
            'title' => 'إعلان 2',
            'price' => 2000,
            'currency' => 'EGP',
            'main_image' => 'listings/original-image-2.jpg',
            'status' => 'Valid',
        ]);

        $listing3 = Listing::create([
            'category_id' => $this->category->id,
            'user_id' => $user->id,
            'title' => 'إعلان 3',
            'price' => 3000,
            'currency' => 'EGP',
            'main_image' => null, // Listing without image
            'status' => 'Valid',
        ]);

        // Store original image values
        $originalImage1 = $listing1->main_image;
        $originalImage2 = $listing2->main_image;
        $originalImage3 = $listing3->main_image;

        // Enable unified image
        $this->actingAs($this->admin)
            ->putJson("/api/admin/categories/{$this->category->id}/toggle-global-image", [
                'is_global_image_active' => true,
            ])
            ->assertOk();

        // Upload unified image
        $imageFile = UploadedFile::fake()->image('unified.jpg', 800, 800);
        
        $this->actingAs($this->admin)
            ->postJson("/api/admin/categories/{$this->category->id}/upload-global-image", [
                'image' => $imageFile,
            ])
            ->assertOk();

        // Verify original listing images are preserved
        $listing1->refresh();
        $listing2->refresh();
        $listing3->refresh();

        $this->assertEquals($originalImage1, $listing1->main_image);
        $this->assertEquals($originalImage2, $listing2->main_image);
        $this->assertEquals($originalImage3, $listing3->main_image);

        // Verify category data includes unified image information
        $this->category->refresh();
        $this->assertTrue($this->category->is_global_image_active);
        $this->assertNotNull($this->category->global_image_url);
    }

    /**
     * Test toggle reversion: disable → revert to original images
     * 
     * This test validates that when unified images are disabled, the system
     * properly reverts to displaying original listing images, and the unified
     * image data is preserved for potential re-enabling.
     * 
     * Validates: Requirements 12.3
     * 
     * @test
     */
    public function toggle_reversion_disabling_reverts_to_original_images()
    {
        // Setup: Enable unified image and upload
        $this->actingAs($this->admin)
            ->putJson("/api/admin/categories/{$this->category->id}/toggle-global-image", [
                'is_global_image_active' => true,
            ])
            ->assertOk();

        $imageFile = UploadedFile::fake()->image('unified.jpg', 800, 800);
        
        $this->actingAs($this->admin)
            ->postJson("/api/admin/categories/{$this->category->id}/upload-global-image", [
                'image' => $imageFile,
            ])
            ->assertOk();

        $this->category->refresh();
        $unifiedImageUrl = $this->category->global_image_url;
        
        $this->assertTrue($this->category->is_global_image_active);
        $this->assertNotNull($unifiedImageUrl);

        // Create listings with original images
        $user = User::factory()->create();
        
        $listing1 = Listing::create([
            'category_id' => $this->category->id,
            'user_id' => $user->id,
            'title' => 'سيارة 1',
            'price' => 50000,
            'currency' => 'EGP',
            'main_image' => 'listings/car-1.jpg',
            'status' => 'Valid',
        ]);

        $listing2 = Listing::create([
            'category_id' => $this->category->id,
            'user_id' => $user->id,
            'title' => 'سيارة 2',
            'price' => 60000,
            'currency' => 'EGP',
            'main_image' => 'listings/car-2.jpg',
            'status' => 'Valid',
        ]);

        // Disable unified image
        $disableResponse = $this->actingAs($this->admin)
            ->putJson("/api/admin/categories/{$this->category->id}/toggle-global-image", [
                'is_global_image_active' => false,
            ]);

        $disableResponse->assertOk();
        $disableResponse->assertJson([
            'id' => $this->category->id,
            'is_global_image_active' => false,
        ]);

        // Verify database state after disabling
        $this->category->refresh();
        $this->assertFalse($this->category->is_global_image_active);
        
        // Unified image URL should still be preserved (not deleted)
        $this->assertEquals($unifiedImageUrl, $this->category->global_image_url);

        // Verify listings still have their original images
        $listing1->refresh();
        $listing2->refresh();
        
        $this->assertEquals('listings/car-1.jpg', $listing1->main_image);
        $this->assertEquals('listings/car-2.jpg', $listing2->main_image);

        // Test re-enabling: unified image should still be available
        $reEnableResponse = $this->actingAs($this->admin)
            ->putJson("/api/admin/categories/{$this->category->id}/toggle-global-image", [
                'is_global_image_active' => true,
            ]);

        $reEnableResponse->assertOk();
        
        $this->category->refresh();
        $this->assertTrue($this->category->is_global_image_active);
        $this->assertEquals($unifiedImageUrl, $this->category->global_image_url);
    }

    /**
     * Test complete workflow with multiple categories
     * 
     * This test validates that the unified image feature works correctly
     * when multiple categories are involved, ensuring proper isolation
     * between categories.
     * 
     * @test
     */
    public function multiple_categories_work_independently()
    {
        // Create additional categories
        $category2 = Category::factory()->create([
            'name' => 'عقارات',
            'is_global_image_active' => false,
        ]);

        $category3 = Category::factory()->create([
            'name' => 'إلكترونيات',
            'is_global_image_active' => false,
        ]);

        // Enable unified image for category 1 and upload
        $this->actingAs($this->admin)
            ->putJson("/api/admin/categories/{$this->category->id}/toggle-global-image", [
                'is_global_image_active' => true,
            ])
            ->assertOk();

        $image1 = UploadedFile::fake()->image('cars.jpg', 800, 800);
        $this->actingAs($this->admin)
            ->postJson("/api/admin/categories/{$this->category->id}/upload-global-image", [
                'image' => $image1,
            ])
            ->assertOk();

        // Enable unified image for category 2 and upload
        $this->actingAs($this->admin)
            ->putJson("/api/admin/categories/{$category2->id}/toggle-global-image", [
                'is_global_image_active' => true,
            ])
            ->assertOk();

        $image2 = UploadedFile::fake()->image('real-estate.jpg', 800, 800);
        $this->actingAs($this->admin)
            ->postJson("/api/admin/categories/{$category2->id}/upload-global-image", [
                'image' => $image2,
            ])
            ->assertOk();

        // Leave category 3 without unified image

        // Verify each category has correct state
        $this->category->refresh();
        $category2->refresh();
        $category3->refresh();

        $this->assertTrue($this->category->is_global_image_active);
        $this->assertNotNull($this->category->global_image_url);

        $this->assertTrue($category2->is_global_image_active);
        $this->assertNotNull($category2->global_image_url);

        $this->assertFalse($category3->is_global_image_active);
        $this->assertNull($category3->global_image_url);

        // Verify images are different
        $this->assertNotEquals(
            $this->category->global_image_url,
            $category2->global_image_url
        );

        // Verify public API returns correct data for all categories
        $publicApiResponse = $this->getJson('/api/categories');
        $publicApiResponse->assertOk();

        $categories = $publicApiResponse->json();
        
        $cat1 = collect($categories)->firstWhere('id', $this->category->id);
        $cat2 = collect($categories)->firstWhere('id', $category2->id);
        $cat3 = collect($categories)->firstWhere('id', $category3->id);

        $this->assertTrue($cat1['is_global_image_active']);
        $this->assertTrue($cat2['is_global_image_active']);
        $this->assertFalse($cat3['is_global_image_active']);
    }

    /**
     * Test error handling in integration workflow
     * 
     * This test validates that errors at any step of the workflow
     * are handled gracefully without leaving the system in an inconsistent state.
     * 
     * @test
     */
    public function workflow_handles_errors_gracefully()
    {
        // Enable unified image
        $this->actingAs($this->admin)
            ->putJson("/api/admin/categories/{$this->category->id}/toggle-global-image", [
                'is_global_image_active' => true,
            ])
            ->assertOk();

        // Attempt to upload invalid file
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);
        
        $uploadResponse = $this->actingAs($this->admin)
            ->postJson("/api/admin/categories/{$this->category->id}/upload-global-image", [
                'image' => $invalidFile,
            ]);

        $uploadResponse->assertStatus(422);

        // Verify category state is still consistent
        $this->category->refresh();
        $this->assertTrue($this->category->is_global_image_active);
        $this->assertNull($this->category->global_image_url);

        // Now upload valid file
        $validFile = UploadedFile::fake()->image('valid.jpg', 800, 800);
        
        $validUploadResponse = $this->actingAs($this->admin)
            ->postJson("/api/admin/categories/{$this->category->id}/upload-global-image", [
                'image' => $validFile,
            ]);

        $validUploadResponse->assertOk();

        // Verify successful upload after error
        $this->category->refresh();
        $this->assertTrue($this->category->is_global_image_active);
        $this->assertNotNull($this->category->global_image_url);
    }
}
