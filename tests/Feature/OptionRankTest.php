<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryFieldOptionRank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptionRankTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'phone' => '01234567890', // Add required phone field
        ]);

        // Create test category
        $this->category = Category::factory()->create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_update_option_ranks()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/categories/{$this->category->slug}/options/ranks", [
                'field' => 'brand',
                'ranks' => [
                    ['option' => 'Toyota', 'rank' => 1],
                    ['option' => 'Honda', 'rank' => 2],
                    ['option' => 'BMW', 'rank' => 3],
                    ['option' => 'غير ذلك', 'rank' => 4],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تم تحديث الترتيب بنجاح',
            ]);

        // Verify database
        $this->assertDatabaseHas('category_field_option_ranks', [
            'category_id' => $this->category->id,
            'field_name' => 'brand',
            'option_value' => 'Toyota',
            'rank' => 1,
        ]);

        $this->assertDatabaseHas('category_field_option_ranks', [
            'category_id' => $this->category->id,
            'field_name' => 'brand',
            'option_value' => 'غير ذلك',
            'rank' => 4,
        ]);
    }

    /** @test */
    public function it_validates_other_option_must_be_last()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/categories/{$this->category->slug}/options/ranks", [
                'field' => 'brand',
                'ranks' => [
                    ['option' => 'Toyota', 'rank' => 1],
                    ['option' => 'غير ذلك', 'rank' => 2], // "غير ذلك" should be last
                    ['option' => 'BMW', 'rank' => 3],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'خيار "غير ذلك" يجب أن يكون في الأسفل دائماً',
            ]);
    }

    /** @test */
    public function it_validates_ranks_must_be_unique()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/categories/{$this->category->slug}/options/ranks", [
                'field' => 'brand',
                'ranks' => [
                    ['option' => 'Toyota', 'rank' => 1],
                    ['option' => 'Honda', 'rank' => 1], // Duplicate rank
                    ['option' => 'BMW', 'rank' => 3],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'قيم الترتيب يجب أن تكون فريدة',
            ]);
    }

    /** @test */
    public function it_validates_ranks_must_be_sequential()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/categories/{$this->category->slug}/options/ranks", [
                'field' => 'brand',
                'ranks' => [
                    ['option' => 'Toyota', 'rank' => 1],
                    ['option' => 'Honda', 'rank' => 3], // Skipped rank 2
                    ['option' => 'BMW', 'rank' => 4],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'قيم الترتيب يجب أن تكون متسلسلة بدءاً من 1',
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson("/api/admin/categories/{$this->category->slug}/options/ranks", [
            'field' => 'brand',
            'ranks' => [
                ['option' => 'Toyota', 'rank' => 1],
            ],
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_admin_role()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'phone' => '01234567891', // Add required phone field
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/admin/categories/{$this->category->slug}/options/ranks", [
                'field' => 'brand',
                'ranks' => [
                    ['option' => 'Toyota', 'rank' => 1],
                ],
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_returns_404_for_non_existent_category()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/categories/non-existent-slug/options/ranks", [
                'field' => 'brand',
                'ranks' => [
                    ['option' => 'Toyota', 'rank' => 1],
                ],
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'القسم غير موجود',
            ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/categories/{$this->category->slug}/options/ranks", [
                // Missing 'field' and 'ranks'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['field', 'ranks']);
    }

    /** @test */
    public function it_can_update_existing_ranks()
    {
        // Create initial ranks
        CategoryFieldOptionRank::create([
            'category_id' => $this->category->id,
            'field_name' => 'brand',
            'option_value' => 'Toyota',
            'rank' => 1,
        ]);

        CategoryFieldOptionRank::create([
            'category_id' => $this->category->id,
            'field_name' => 'brand',
            'option_value' => 'Honda',
            'rank' => 2,
        ]);

        // Update ranks (swap order)
        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/categories/{$this->category->slug}/options/ranks", [
                'field' => 'brand',
                'ranks' => [
                    ['option' => 'Honda', 'rank' => 1],
                    ['option' => 'Toyota', 'rank' => 2],
                ],
            ]);

        $response->assertStatus(200);

        // Verify updated ranks
        $this->assertDatabaseHas('category_field_option_ranks', [
            'category_id' => $this->category->id,
            'field_name' => 'brand',
            'option_value' => 'Honda',
            'rank' => 1,
        ]);

        $this->assertDatabaseHas('category_field_option_ranks', [
            'category_id' => $this->category->id,
            'field_name' => 'brand',
            'option_value' => 'Toyota',
            'rank' => 2,
        ]);
    }
}
