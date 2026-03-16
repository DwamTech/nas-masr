<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use App\Models\Governorate;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Listing>
 */
class ListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // جلب محافظة ومدينة عشوائية
        $governorate = Governorate::inRandomOrder()->first();
        $city = $governorate ? $governorate->cities()->inRandomOrder()->first() : null;

        // جلب قسم عشوائي
        $category = Category::where('is_active', true)->inRandomOrder()->first();

        return [
            'category_id' => $category?->id ?? 1,
            'user_id' => User::factory(),
            
            // ✅ Title مع بيانات واقعية بالعربية
            'title' => $this->generateArabicTitle(),
            
            'price' => fake()->randomFloat(2, 1000, 500000),
            'currency' => 'EGP',
            'description' => $this->generateArabicDescription(),
            
            'governorate_id' => $governorate?->id,
            'city_id' => $city?->id,
            'lat' => fake()->latitude(22, 32), // مصر
            'lng' => fake()->longitude(25, 37), // مصر
            'address' => fake()->address(),
            
            'main_image' => 'listings/default.jpg',
            'images' => [],
            
            'status' => fake()->randomElement(['Valid', 'Pending']),
            'published_at' => now(),
            'plan_type' => fake()->randomElement(['free', 'standard', 'premium', 'featured']),
            'publish_via' => 'free_plan',
            
            'contact_phone' => fake()->numerify('01#########'),
            'whatsapp_phone' => fake()->numerify('01#########'),
            'country_code' => '+20',
            
            'views' => fake()->numberBetween(0, 1000),
            'rank' => 0,
            'admin_approved' => true,
            'expire_at' => now()->addDays(30),
            'isPayment' => false,
        ];
    }

    /**
     * توليد عنوان عربي واقعي
     */
    protected function generateArabicTitle(): string
    {
        $templates = [
            'شقة للبيع في {city} - {rooms} غرف',
            'سيارة {brand} موديل {year} للبيع',
            'وظيفة {job} - {city}',
            'محل تجاري للإيجار في {city}',
            'أرض للبيع {area} متر - {city}',
            'فيلا للبيع في {city} - {rooms} غرف',
            'مطلوب {job} للعمل في {city}',
            'معدات {equipment} للبيع',
        ];

        $template = fake()->randomElement($templates);

        return strtr($template, [
            '{city}' => fake()->randomElement(['القاهرة', 'الإسكندرية', 'الجيزة', 'المنصورة', 'طنطا', 'أسيوط']),
            '{rooms}' => fake()->numberBetween(2, 5),
            '{brand}' => fake()->randomElement(['تويوتا', 'هيونداي', 'نيسان', 'شيفروليه', 'كيا']),
            '{year}' => fake()->year(),
            '{job}' => fake()->randomElement(['محاسب', 'مهندس', 'مدرس', 'طبيب', 'مبرمج', 'مندوب مبيعات']),
            '{area}' => fake()->numberBetween(100, 1000),
            '{equipment}' => fake()->randomElement(['كهربائية', 'صناعية', 'زراعية', 'طبية']),
        ]);
    }

    /**
     * توليد وصف عربي واقعي
     */
    protected function generateArabicDescription(): string
    {
        $descriptions = [
            'إعلان مميز بمواصفات ممتازة وسعر مناسب. للتواصل والاستفسار يرجى الاتصال على الرقم المذكور.',
            'فرصة رائعة للشراء بسعر مناسب. الموقع متميز والمواصفات ممتازة. للجادين فقط.',
            'عرض خاص لفترة محدودة. جميع الأوراق سليمة. للمعاينة والاستفسار يرجى التواصل.',
            'مطلوب بشكل عاجل. شروط ممتازة ومرتب مجزي. يرجى إرسال السيرة الذاتية.',
        ];

        return fake()->randomElement($descriptions);
    }

    /**
     * State: إعلان معلق (Pending)
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Pending',
            'admin_approved' => false,
        ]);
    }

    /**
     * State: إعلان مميز (Featured)
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_type' => 'featured',
            'rank' => fake()->numberBetween(1, 100),
            'isPayment' => true,
        ]);
    }

    /**
     * State: إعلان منتهي (Expired)
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Expired',
            'expire_at' => now()->subDays(10),
        ]);
    }

    /**
     * State: إعلان بدون عنوان (للاختبار)
     */
    public function withoutTitle(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => null,
        ]);
    }

    /**
     * State: إعلان بعنوان مخصص
     */
    public function withTitle(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
        ]);
    }
}
