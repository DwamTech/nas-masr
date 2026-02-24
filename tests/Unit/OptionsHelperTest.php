<?php

namespace Tests\Unit;

use App\Support\OptionsHelper;
use Tests\TestCase;

class OptionsHelperTest extends TestCase
{
    public function test_ensures_other_at_end()
    {
        $input = ['A', 'غير ذلك', 'B', 'C'];
        $result = OptionsHelper::ensureOtherAtEnd($input);
        
        $this->assertEquals(['A', 'B', 'C', 'غير ذلك'], $result);
        $this->assertEquals('غير ذلك', end($result));
    }

    public function test_removes_duplicate_other()
    {
        $input = ['A', 'غير ذلك', 'B', 'غير ذلك', 'C'];
        $result = OptionsHelper::ensureOtherAtEnd($input);
        
        $this->assertEquals(['A', 'B', 'C', 'غير ذلك'], $result);
        $this->assertCount(1, array_filter($result, fn($x) => $x === 'غير ذلك'));
    }

    public function test_adds_other_if_not_present()
    {
        $input = ['A', 'B', 'C'];
        $result = OptionsHelper::ensureOtherAtEnd($input);
        
        $this->assertEquals(['A', 'B', 'C', 'غير ذلك'], $result);
    }

    public function test_handles_empty_array()
    {
        $result = OptionsHelper::ensureOtherAtEnd([]);
        $this->assertEquals(['غير ذلك'], $result);
    }

    public function test_preserves_order_of_other_elements()
    {
        $input = ['Z', 'A', 'M', 'غير ذلك'];
        $result = OptionsHelper::ensureOtherAtEnd($input);
        
        $this->assertEquals(['Z', 'A', 'M', 'غير ذلك'], $result);
    }

    public function test_sorts_options_with_other_at_end()
    {
        $input = ['ج', 'غير ذلك', 'أ', 'ب'];
        $result = OptionsHelper::sortOptionsWithOtherAtEnd($input);
        
        $this->assertEquals('غير ذلك', end($result));
        // التحقق من الترتيب الأبجدي للعناصر الأخرى
        $withoutOther = array_slice($result, 0, -1);
        $this->assertEquals(['أ', 'ب', 'ج'], $withoutOther);
    }

    public function test_sorts_numbers_correctly()
    {
        $input = ['2024', '2022', 'غير ذلك', '2023'];
        $result = OptionsHelper::sortOptionsWithOtherAtEnd($input);
        
        $this->assertEquals(['2022', '2023', '2024', 'غير ذلك'], $result);
    }

    public function test_process_options_without_sorting()
    {
        $input = ['A', 'غير ذلك', 'B'];
        $result = OptionsHelper::processOptions($input);
        
        $this->assertEquals(['A', 'B', 'غير ذلك'], $result);
    }

    public function test_process_options_with_sorting()
    {
        $input = ['ج', 'غير ذلك', 'أ', 'ب'];
        $result = OptionsHelper::processOptions($input, true);
        
        $this->assertEquals(['أ', 'ب', 'ج', 'غير ذلك'], $result);
    }

    public function test_process_options_handles_null()
    {
        $result = OptionsHelper::processOptions(null);
        $this->assertEquals(['غير ذلك'], $result);
    }

    public function test_process_options_removes_empty_values()
    {
        $input = ['A', '', '  ', 'B', 'غير ذلك'];
        $result = OptionsHelper::processOptions($input);
        
        $this->assertEquals(['A', 'B', 'غير ذلك'], $result);
    }

    public function test_process_options_removes_duplicates()
    {
        $input = ['A', 'B', 'A', 'غير ذلك', 'B'];
        $result = OptionsHelper::processOptions($input);
        
        $this->assertEquals(['A', 'B', 'غير ذلك'], $result);
    }

    public function test_process_options_map()
    {
        $input = [
            'تويوتا' => ['كامري', 'غير ذلك', 'كورولا'],
            'هيونداي' => ['إلنترا', 'غير ذلك', 'توسان']
        ];
        
        $result = OptionsHelper::processOptionsMap($input);
        
        $this->assertEquals(['كامري', 'كورولا', 'غير ذلك'], $result['تويوتا']);
        $this->assertEquals(['إلنترا', 'توسان', 'غير ذلك'], $result['هيونداي']);
    }

    public function test_process_options_map_with_sorting()
    {
        $input = [
            'brand1' => ['C', 'غير ذلك', 'A', 'B']
        ];
        
        $result = OptionsHelper::processOptionsMap($input, true);
        
        $this->assertEquals(['A', 'B', 'C', 'غير ذلك'], $result['brand1']);
    }

    public function test_process_fields_collection()
    {
        // إنشاء mock collection
        $field1 = (object)[
            'field_name' => 'year',
            'options' => ['2024', 'غير ذلك', '2023']
        ];
        
        $field2 = (object)[
            'field_name' => 'fuel',
            'options' => ['بنزين', 'غير ذلك', 'ديزل']
        ];
        
        $collection = collect([$field1, $field2]);
        
        $result = OptionsHelper::processFieldsCollection($collection);
        
        $this->assertEquals(['2024', '2023', 'غير ذلك'], $result[0]->options);
        $this->assertEquals(['بنزين', 'ديزل', 'غير ذلك'], $result[1]->options);
    }

    // Real-world scenarios
    public function test_car_years_scenario()
    {
        $years = ['2024', '2023', 'غير ذلك', '2022', '2021'];
        $result = OptionsHelper::processOptions($years);
        
        $this->assertEquals('غير ذلك', end($result));
        $this->assertContains('2024', $result);
        $this->assertContains('2021', $result);
    }

    public function test_fuel_types_scenario()
    {
        $fuels = ['ديزل', 'غير ذلك', 'بنزين', 'كهرباء'];
        $result = OptionsHelper::processOptions($fuels, true);
        
        $this->assertEquals('غير ذلك', end($result));
    }

    public function test_brands_models_scenario()
    {
        $brandsModels = [
            'تويوتا' => ['كامري', 'غير ذلك', 'كورولا', 'راف فور'],
            'هيونداي' => ['إلنترا', 'توسان', 'غير ذلك', 'سوناتا'],
            'مرسيدس' => ['C-Class', 'E-Class', 'غير ذلك']
        ];
        
        $result = OptionsHelper::processOptionsMap($brandsModels);
        
        // التحقق من أن كل ماركة لها "غير ذلك" في الآخر
        foreach ($result as $models) {
            $this->assertEquals('غير ذلك', end($models));
        }
    }

    public function test_jobs_specialties_hierarchical_scenario()
    {
        $jobsMainSubs = [
            'تكنولوجيا المعلومات' => ['مطور ويب', 'غير ذلك', 'مطور موبايل'],
            'الطب' => ['طبيب عام', 'طبيب أسنان', 'غير ذلك'],
            'التعليم' => ['مدرس', 'غير ذلك', 'محاضر']
        ];
        
        $result = OptionsHelper::processOptionsMap($jobsMainSubs);
        
        $this->assertEquals('غير ذلك', end($result['تكنولوجيا المعلومات']));
        $this->assertEquals('غير ذلك', end($result['الطب']));
        $this->assertEquals('غير ذلك', end($result['التعليم']));
    }

    // Edge cases
    public function test_only_other_option()
    {
        $result = OptionsHelper::processOptions(['غير ذلك']);
        $this->assertEquals(['غير ذلك'], $result);
    }

    public function test_multiple_other_options_only()
    {
        $result = OptionsHelper::processOptions(['غير ذلك', 'غير ذلك', 'غير ذلك']);
        $this->assertEquals(['غير ذلك'], $result);
    }

    public function test_large_list()
    {
        $largeList = array_map(fn($i) => "Item $i", range(1, 1000));
        $largeList[] = 'غير ذلك';
        array_splice($largeList, 500, 0, ['غير ذلك']); // إضافة في المنتصف
        
        $result = OptionsHelper::processOptions($largeList);
        
        $this->assertCount(1001, $result); // 1000 items + 1 "غير ذلك"
        $this->assertEquals('غير ذلك', end($result));
    }

    public function test_special_characters()
    {
        $input = ['A@#$', 'غير ذلك', 'B%^&', 'C*()'];
        $result = OptionsHelper::processOptions($input);
        
        $this->assertEquals(['A@#$', 'B%^&', 'C*()', 'غير ذلك'], $result);
    }
}
