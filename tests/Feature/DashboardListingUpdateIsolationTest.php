<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardListingUpdateIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_listing_via_dashboard_patch_and_form_endpoints(): void
    {
        $this->seedJobsLocationContext();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $listing = $this->createListing([
            'status' => 'Pending',
            'publish_via' => 'free',
            'plan_type' => 'free',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/listings/{$listing->id}", [
                'title' => 'تحديث من patch',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'تحديث من patch');

        $this->actingAs($admin)
            ->post("/api/admin/listings/{$listing->id}/update", [
                'title' => 'تحديث من الفورم',
                'admin_comment' => 'مراجعة داخلية',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'تحديث من الفورم');

        $this->assertSame('تحديث من الفورم', $listing->fresh()->title);
    }

    public function test_employee_with_ads_moderation_permission_can_update_regular_pending_listing(): void
    {
        $this->seedJobsLocationContext();

        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['ads.moderation'],
        ]);

        $listing = $this->createListing([
            'status' => 'Pending',
            'publish_via' => 'free',
            'plan_type' => 'free',
        ]);

        $this->actingAs($employee)
            ->post("/api/admin/listings/{$listing->id}/update", [
                'title' => 'تحديث موظف مراجعة',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'تحديث موظف مراجعة');
    }

    public function test_employee_with_ads_unpaid_permission_can_update_unpaid_pending_listing(): void
    {
        $this->seedJobsLocationContext();

        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['ads.unpaid'],
        ]);

        $listing = $this->createListing([
            'status' => 'Pending',
            'publish_via' => null,
            'isPayment' => false,
            'plan_type' => 'standard',
        ]);

        $this->actingAs($employee)
            ->post("/api/admin/listings/{$listing->id}/update", [
                'title' => 'تحديث إعلان غير مدفوع',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'تحديث إعلان غير مدفوع');
    }

    public function test_employee_with_wrong_permission_is_blocked_from_pending_listing_updates(): void
    {
        $this->seedJobsLocationContext();

        $moderationEmployee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['ads.moderation'],
        ]);

        $unpaidEmployee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['ads.unpaid'],
        ]);

        $regularPending = $this->createListing([
            'status' => 'Pending',
            'publish_via' => 'free',
            'plan_type' => 'free',
        ]);

        $unpaidPending = $this->createListing([
            'status' => 'Pending',
            'publish_via' => null,
            'isPayment' => false,
            'plan_type' => 'standard',
        ]);

        $this->actingAs($unpaidEmployee)
            ->post("/api/admin/listings/{$regularPending->id}/update", [
                'title' => 'محاولة غير مصرح بها',
            ])
            ->assertStatus(403)
            ->assertJsonPath('required_page_key', 'ads.moderation');

        $this->actingAs($moderationEmployee)
            ->post("/api/admin/listings/{$unpaidPending->id}/update", [
                'title' => 'محاولة أخرى غير مصرح بها',
            ])
            ->assertStatus(403)
            ->assertJsonPath('required_page_key', 'ads.unpaid');
    }

    public function test_mobile_v1_listing_update_flow_remains_unchanged(): void
    {
        $this->seedJobsLocationContext();

        $owner = User::factory()->create([
            'role' => 'user',
        ]);

        $listing = $this->createListing([
            'user_id' => $owner->id,
            'status' => 'Pending',
            'publish_via' => 'free',
            'plan_type' => 'free',
        ]);

        $this->actingAs($owner)
            ->post("/api/v1/jobs/listings/{$listing->id}", [
                '_method' => 'PUT',
                'title' => 'تحديث من التطبيق',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'تحديث من التطبيق');

        $this->assertSame('تحديث من التطبيق', $listing->fresh()->title);
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
        $ownerId = $overrides['user_id'] ?? User::factory()->create([
            'role' => 'user',
        ])->id;

        unset($overrides['user_id']);

        return Listing::create(array_merge([
            'category_id' => 1,
            'user_id' => $ownerId,
            'title' => 'إعلان قابل للتعديل',
            'description' => 'وصف تجريبي',
            'governorate_id' => 1,
            'city_id' => 1,
            'address' => 'القاهرة',
            'status' => 'Pending',
            'published_at' => now(),
            'plan_type' => 'free',
            'publish_via' => 'free',
            'admin_approved' => false,
            'views' => 0,
            'isPayment' => false,
        ], $overrides));
    }
}
