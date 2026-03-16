<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardListingReadDeleteIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_fetch_and_delete_published_listing_via_dashboard_endpoint_without_public_side_effects(): void
    {
        $this->seedJobsLocationContext();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $listing = $this->createListing([
            'status' => 'Valid',
            'views' => 0,
        ]);

        $this->actingAs($admin)
            ->getJson("/api/admin/listings/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $listing->id)
            ->assertJsonPath('data.category', 'jobs')
            ->assertJsonPath('user.id', $listing->user_id);

        $this->assertSame(0, (int) $listing->fresh()->views);

        $this->actingAs($admin)
            ->deleteJson("/api/admin/listings/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('listings', [
            'id' => $listing->id,
        ]);
    }

    public function test_employee_with_ads_list_permission_can_manage_published_listing(): void
    {
        $this->seedJobsLocationContext();

        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['ads.list'],
        ]);

        $listing = $this->createListing([
            'status' => 'Valid',
        ]);

        $this->actingAs($employee)
            ->getJson("/api/admin/listings/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $listing->id);

        $this->actingAs($employee)
            ->deleteJson("/api/admin/listings/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_employee_with_ads_moderation_permission_can_manage_rejected_listing(): void
    {
        $this->seedJobsLocationContext();

        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['ads.moderation'],
        ]);

        $listing = $this->createListing([
            'status' => 'Rejected',
            'admin_comment' => 'مرفوض للمراجعة',
        ]);

        $this->actingAs($employee)
            ->getJson("/api/admin/listings/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'Rejected');

        $this->actingAs($employee)
            ->deleteJson("/api/admin/listings/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_employee_with_wrong_page_permission_is_blocked_based_on_listing_status(): void
    {
        $this->seedJobsLocationContext();

        $publishedViewer = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['ads.list'],
        ]);

        $moderationViewer = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['ads.moderation'],
        ]);

        $publishedListing = $this->createListing([
            'status' => 'Valid',
            'title' => 'إعلان منشور',
        ]);

        $rejectedListing = $this->createListing([
            'status' => 'Rejected',
            'title' => 'إعلان مرفوض',
        ]);

        $this->actingAs($publishedViewer)
            ->getJson("/api/admin/listings/{$rejectedListing->id}")
            ->assertStatus(403)
            ->assertJsonPath('required_page_key', 'ads.moderation');

        $this->actingAs($publishedViewer)
            ->deleteJson("/api/admin/listings/{$rejectedListing->id}")
            ->assertStatus(403)
            ->assertJsonPath('required_page_key', 'ads.moderation');

        $this->actingAs($moderationViewer)
            ->getJson("/api/admin/listings/{$publishedListing->id}")
            ->assertStatus(403)
            ->assertJsonPath('required_page_key', 'ads.list');

        $this->actingAs($moderationViewer)
            ->deleteJson("/api/admin/listings/{$publishedListing->id}")
            ->assertStatus(403)
            ->assertJsonPath('required_page_key', 'ads.list');
    }

    public function test_mobile_v1_listing_show_and_delete_routes_remain_unchanged(): void
    {
        $this->seedJobsLocationContext();

        $owner = User::factory()->create([
            'role' => 'user',
        ]);

        $listing = $this->createListing([
            'user_id' => $owner->id,
            'status' => 'Valid',
            'views' => 0,
        ]);

        $this->getJson("/api/v1/jobs/listings/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $listing->id);

        $this->assertSame(1, (int) $listing->fresh()->views);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/jobs/listings/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    protected function seedJobsLocationContext(): void
    {
        DB::table('categories')->insert([
            'slug' => 'jobs',
            'name' => 'وظائف',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $governorateId = DB::table('governorates')->insertGetId([
            'name' => 'القاهرة',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cities')->insert([
            'governorate_id' => $governorateId,
            'name' => 'مدينة نصر',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createListing(array $overrides = []): Listing
    {
        $owner = User::factory()->create([
            'role' => 'user',
        ]);

        return Listing::create(array_merge([
            'category_id' => 1,
            'user_id' => $owner->id,
            'title' => 'إعلان تجريبي',
            'description' => 'وصف تجريبي',
            'governorate_id' => 1,
            'city_id' => 1,
            'address' => 'القاهرة',
            'status' => 'Valid',
            'published_at' => now(),
            'plan_type' => 'free',
            'publish_via' => 'free',
            'admin_approved' => true,
            'views' => 0,
            'isPayment' => false,
        ], $overrides));
    }
}
