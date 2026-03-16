<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardListingCreationIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_listing_via_dashboard_admin_endpoint(): void
    {
        $this->seedJobsLocationContext();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->postJson('/api/admin/listings/create/jobs', $this->jobsPayload());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.category', 'jobs');

        $listingId = $response->json('id');
        $listing = Listing::findOrFail($listingId);

        $this->assertSame($admin->id, $listing->user_id);
        $this->assertSame('admin', $listing->publish_via);
        $this->assertSame('Valid', $listing->status);
        $this->assertTrue((bool) $listing->admin_approved);
    }

    public function test_employee_with_ads_create_permission_can_create_listing_via_dashboard_endpoint(): void
    {
        $this->seedJobsLocationContext();

        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['ads.create'],
        ]);

        $response = $this->actingAs($employee)->postJson('/api/admin/listings/create/jobs', $this->jobsPayload([
            'title' => 'موظف أنشأ الإعلان',
        ]));

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $listing = Listing::findOrFail($response->json('id'));

        $this->assertSame($employee->id, $listing->user_id);
        $this->assertSame('admin', $listing->publish_via);
        $this->assertSame('employee', $employee->fresh()->role);
    }

    public function test_employee_without_ads_create_permission_is_blocked(): void
    {
        $this->seedJobsLocationContext();

        $employee = User::factory()->create([
            'role' => 'employee',
            'allowed_dashboard_pages' => ['dashboard.home'],
        ]);

        $this->actingAs($employee)->postJson('/api/admin/listings/create/jobs', $this->jobsPayload([
            'title' => 'محاولة مرفوضة',
        ]))->assertStatus(403);

        $this->assertDatabaseMissing('listings', [
            'title' => 'محاولة مرفوضة',
        ]);
    }

    public function test_mobile_v1_listing_creation_flow_remains_unchanged(): void
    {
        $this->seedJobsLocationContext();

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/jobs/listings', $this->jobsPayload([
            'title' => 'إعلان من التطبيق',
        ]));

        $response->assertOk()
            ->assertJsonPath('data.title', 'إعلان من التطبيق');

        $listing = Listing::where('title', 'إعلان من التطبيق')->firstOrFail();

        $this->assertSame($user->id, $listing->user_id);
        $this->assertSame('free', $listing->publish_via);
        $this->assertSame('advertiser', $user->fresh()->role);
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

    protected function jobsPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'فرصة عمل',
            'description' => 'إعلان تجريبي',
            'governorate' => 'القاهرة',
            'city' => 'مدينة نصر',
            'lat' => '30.044420',
            'lng' => '31.235712',
            'address' => 'القاهرة - مدينة نصر',
            'plan_type' => 'free',
            'price' => null,
        ], $overrides);
    }
}
