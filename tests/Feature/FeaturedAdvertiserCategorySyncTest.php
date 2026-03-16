<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\BestAdvertiser;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

/**
 * اختبار استكشافي لشرط الخلل: قراءة الأقسام المفعلة من قاعدة البيانات
 * 
 * **Validates: Requirements 1.4, 1.6, 2.3**
 * 
 * هذا الاختبار يتحقق من Property 1: Fault Condition
 * - يجب أن يفشل على الكود غير المُصلح (يثبت وجود الخلل)
 * - عندما ينجح بعد الإصلاح، يؤكد تحقق السلوك المتوقع
 * 
 * الهدف: إظهار أن API endpoint لقراءة بيانات معلن مميز غير موجود حالياً
 * مما يجبر الداشبورد على القراءة من localStorage بدلاً من قاعدة البيانات
 */
class FeaturedAdvertiserCategorySyncTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;
    protected $categories;

    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء مستخدم admin للمصادقة
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'phone' => '01000000001',
            'status' => 'active',
        ]);

        // إنشاء مستخدم عادي ليكون معلن مميز
        $this->user = User::factory()->create([
            'role' => 'user',
            'phone' => '01000000002',
            'status' => 'active',
        ]);

        // إنشاء أقسام للاختبار
        $this->categories = collect([
            Category::create([
                'name' => 'سيارات',
                'slug' => 'cars',
                'title' => 'سيارات',
                'is_active' => true,
                'sort_order' => 1,
            ]),
            Category::create([
                'name' => 'عقارات',
                'slug' => 'real-estate',
                'title' => 'عقارات',
                'is_active' => true,
                'sort_order' => 2,
            ]),
            Category::create([
                'name' => 'وظائف',
                'slug' => 'jobs',
                'title' => 'وظائف',
                'is_active' => true,
                'sort_order' => 3,
            ]),
        ]);
    }

    /**
     * Property 1: Fault Condition - قراءة الأقسام المفعلة من قاعدة البيانات
     * 
     * **النتيجة المتوقعة**: فشل الاختبار على الكود غير المُصلح
     * (لأن endpoint غير موجود حالياً)
     * 
     * هذا الاختبار يحاكي السيناريو التالي:
     * 1. تعيين معلن كمميز في أقسام معينة من الداشبورد
     * 2. حفظ البيانات في قاعدة البيانات بنجاح
     * 3. محاولة قراءة الأقسام المفعلة من قاعدة البيانات عبر API
     * 4. التحقق من أن البيانات المُرجعة تطابق البيانات المحفوظة
     * 
     * الخلل: لا يوجد endpoint لقراءة البيانات، مما يجبر الداشبورد
     * على القراءة من localStorage بدلاً من قاعدة البيانات
     */
    public function test_featured_advertiser_categories_should_be_readable_from_database()
    {
        Sanctum::actingAs($this->admin);

        // السيناريو: تعيين معلن كمميز في أقسام معينة
        $categoryIds = $this->categories->pluck('id')->take(2)->toArray();
        
        // حفظ البيانات في قاعدة البيانات
        $response = $this->postJson('/api/admin/featured', [
            'user_id' => $this->user->id,
            'category_ids' => $categoryIds,
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        
        // التحقق من حفظ البيانات في قاعدة البيانات
        $this->assertDatabaseHas('best_advertiser', [
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $bestAdvertiser = BestAdvertiser::where('user_id', $this->user->id)->first();
        $this->assertNotNull($bestAdvertiser);
        $this->assertEquals($categoryIds, $bestAdvertiser->category_ids);

        // **الاختبار الأساسي**: محاولة قراءة البيانات من API
        // هذا يجب أن يفشل على الكود غير المُصلح لأن endpoint غير موجود
        $readResponse = $this->getJson("/api/admin/featured/{$this->user->id}");
        
        // التحقق من أن endpoint موجود ويعمل
        $readResponse->assertStatus(200);
        
        // التحقق من أن البيانات المُرجعة تطابق البيانات المحفوظة في قاعدة البيانات
        $readResponse->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'category_ids',
                'is_active',
            ],
        ]);

        $readResponse->assertJson([
            'data' => [
                'user_id' => $this->user->id,
                'category_ids' => $categoryIds,
                'is_active' => true,
            ],
        ]);

        echo "\n✅ Property 1 Passed: الأقسام المفعلة يمكن قراءتها من قاعدة البيانات عبر API\n";
    }

    /**
     * Property 1 - حالة حدية: قراءة بيانات معلن غير موجود
     * 
     * يجب أن يرجع API خطأ 404 عند محاولة قراءة بيانات معلن غير مميز
     */
    public function test_reading_non_existent_featured_advertiser_returns_404()
    {
        Sanctum::actingAs($this->admin);

        // محاولة قراءة بيانات معلن غير مميز
        $nonExistentUserId = 99999;
        $response = $this->getJson("/api/admin/featured/{$nonExistentUserId}");
        
        // يجب أن يرجع 404
        $response->assertStatus(404);

        echo "\n✅ Edge Case Passed: قراءة معلن غير موجود ترجع 404\n";
    }

    /**
     * Property 1 - حالة حدية: قراءة بيانات معلن مميز معطل
     * 
     * يجب أن يرجع API البيانات حتى لو كان المعلن معطل (is_active = false)
     * لأن الداشبورد يحتاج لعرض البيانات لتعديلها
     */
    public function test_reading_disabled_featured_advertiser_returns_data()
    {
        Sanctum::actingAs($this->admin);

        // إنشاء معلن مميز معطل
        $categoryIds = $this->categories->pluck('id')->take(1)->toArray();
        
        $bestAdvertiser = BestAdvertiser::create([
            'user_id' => $this->user->id,
            'category_ids' => $categoryIds,
            'is_active' => false,
        ]);

        // قراءة البيانات من API
        $response = $this->getJson("/api/admin/featured/{$this->user->id}");
        
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'user_id' => $this->user->id,
                'category_ids' => $categoryIds,
                'is_active' => false,
            ],
        ]);

        echo "\n✅ Edge Case Passed: قراءة معلن معطل ترجع البيانات\n";
    }

    /**
     * Property 1 - اختبار قائم على الخصائص: توليد حالات عشوائية
     * 
     * يختبر قراءة البيانات لمعلنين مميزين مختلفين بأقسام مختلفة
     * للتحقق من أن API يعمل بشكل صحيح عبر مجموعة واسعة من المدخلات
     */
    public function test_reading_featured_advertisers_with_various_category_combinations()
    {
        Sanctum::actingAs($this->admin);

        // توليد 5 حالات عشوائية
        for ($i = 0; $i < 5; $i++) {
            // إنشاء مستخدم جديد
            $user = User::factory()->create([
                'role' => 'user',
                'phone' => '0100000' . str_pad($i + 100, 4, '0', STR_PAD_LEFT),
                'status' => 'active',
            ]);

            // اختيار عدد عشوائي من الأقسام (1-3)
            $numCategories = rand(1, 3);
            $categoryIds = $this->categories->random($numCategories)->pluck('id')->toArray();
            
            // حفظ البيانات
            $this->postJson('/api/admin/featured', [
                'user_id' => $user->id,
                'category_ids' => $categoryIds,
                'is_active' => true,
            ])->assertStatus(201);

            // قراءة البيانات والتحقق من التطابق
            $response = $this->getJson("/api/admin/featured/{$user->id}");
            
            $response->assertStatus(200);
            $response->assertJson([
                'data' => [
                    'user_id' => $user->id,
                    'category_ids' => $categoryIds,
                    'is_active' => true,
                ],
            ]);
        }

        echo "\n✅ Property-Based Test Passed: قراءة البيانات تعمل بشكل صحيح لـ 5 حالات عشوائية\n";
    }

    /**
     * Property 1 - اختبار التزامن: تحديث البيانات ثم قراءتها
     * 
     * يتحقق من أن قراءة البيانات من API تعكس آخر تحديث في قاعدة البيانات
     */
    public function test_reading_reflects_latest_database_updates()
    {
        Sanctum::actingAs($this->admin);

        // حفظ البيانات الأولية
        $initialCategoryIds = [$this->categories[0]->id];
        
        $this->postJson('/api/admin/featured', [
            'user_id' => $this->user->id,
            'category_ids' => $initialCategoryIds,
            'is_active' => true,
        ])->assertStatus(201);

        // قراءة البيانات الأولية
        $response1 = $this->getJson("/api/admin/featured/{$this->user->id}");
        $response1->assertJson([
            'data' => [
                'category_ids' => $initialCategoryIds,
            ],
        ]);

        // تحديث البيانات
        $updatedCategoryIds = $this->categories->pluck('id')->toArray();
        
        $this->postJson('/api/admin/featured', [
            'user_id' => $this->user->id,
            'category_ids' => $updatedCategoryIds,
            'is_active' => true,
        ])->assertStatus(200);

        // قراءة البيانات المحدثة - يجب أن تعكس التحديث
        $response2 = $this->getJson("/api/admin/featured/{$this->user->id}");
        $response2->assertJson([
            'data' => [
                'category_ids' => $updatedCategoryIds,
            ],
        ]);

        echo "\n✅ Sync Test Passed: قراءة البيانات تعكس آخر تحديث في قاعدة البيانات\n";
    }
}
