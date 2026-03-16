<?php

namespace App\Support;

/**
 * Options Helper
 * 
 * يوفر دوال مساعدة لإدارة خيارات الأقسام
 * يضمن أن "غير ذلك" دائماً في آخر القائمة
 */
class OptionsHelper
{
    const OTHER_OPTION = 'غير ذلك';

    /**
     * يضمن أن "غير ذلك" موجود في آخر القائمة
     * 
     * @param array $options القائمة الأصلية
     * @return array القائمة مع "غير ذلك" في الآخر
     */
    public static function ensureOtherAtEnd(array $options): array
    {
        if (empty($options)) {
            return [self::OTHER_OPTION];
        }

        // إزالة كل نسخ "غير ذلك" من القائمة
        $filtered = array_filter($options, function ($opt) {
            return $opt !== self::OTHER_OPTION;
        });

        // إعادة ترتيب المفاتيح
        $filtered = array_values($filtered);

        // إضافة "غير ذلك" في الآخر
        $filtered[] = self::OTHER_OPTION;

        return $filtered;
    }

    /**
     * يرتب القائمة أبجدياً مع إبقاء "غير ذلك" في الآخر
     * 
     * @param array $options القائمة الأصلية
     * @return array القائمة مرتبة مع "غير ذلك" في الآخر
     */
    public static function sortOptionsWithOtherAtEnd(array $options): array
    {
        if (empty($options)) {
            return [self::OTHER_OPTION];
        }

        // إزالة "غير ذلك" وترتيب الباقي
        $filtered = array_filter($options, function ($opt) {
            return $opt !== self::OTHER_OPTION;
        });

        // ترتيب أبجدي (يدعم العربية)
        usort($filtered, function ($a, $b) {
            return strcmp($a, $b);
        });

        // إضافة "غير ذلك" في الآخر
        $filtered[] = self::OTHER_OPTION;

        return $filtered;
    }

    /**
     * يرتب القائمة عكسياً (Z to A) مع إبقاء "غير ذلك" في الآخر
     * 
     * @param array $options القائمة الأصلية
     * @return array القائمة مرتبة عكسياً مع "غير ذلك" في الآخر
     */
    public static function reverseSortWithOtherAtEnd(array $options): array
    {
        if (empty($options)) {
            return [self::OTHER_OPTION];
        }

        // إزالة "غير ذلك" من القائمة
        $filtered = array_filter($options, function ($opt) {
            return $opt !== self::OTHER_OPTION;
        });

        if (empty($filtered)) {
            return [self::OTHER_OPTION];
        }

        $filtered = array_values($filtered);

        try {
            // ترتيب عكسي باستخدام Arabic locale
            $collator = new \Collator('ar');
            
            if ($collator === null) {
                // Fallback to basic sorting
                \Log::warning('Collator initialization failed, using fallback sorting');
                sort($filtered);
                $filtered = array_reverse($filtered);
            } else {
                $collator->sort($filtered);
                // عكس الترتيب (من Z إلى A)
                $filtered = array_reverse($filtered);
            }
        } catch (\Exception $e) {
            // Log error and use fallback
            \Log::error('Error in reverse sorting: ' . $e->getMessage());
            sort($filtered);
            $filtered = array_reverse($filtered);
        }

        // إضافة "غير ذلك" في الآخر
        $filtered[] = self::OTHER_OPTION;

        return $filtered;
    }

    /**
     * يعالج options من Database ويضمن "غير ذلك" في الآخر
     * 
     * @param array|null $options القائمة من Database
     * @param bool $shouldSort هل نرتب القائمة أبجدياً؟
     * @param bool $reverseSort هل نرتب عكسياً؟ (Z to A) - الافتراضي true
     * @return array القائمة معالجة
     */
    public static function processOptions(?array $options, bool $shouldSort = false, bool $reverseSort = true): array
    {
        if (empty($options)) {
            return [self::OTHER_OPTION];
        }

        // تنظيف القائمة من القيم الفارغة وتحويل إلى strings
        $cleaned = array_filter(
            array_map(function($opt) {
                return trim((string) $opt);
            }, $options),
            function ($opt) {
                return !empty($opt);
            }
        );

        if (empty($cleaned)) {
            return [self::OTHER_OPTION];
        }

        // اختيار نوع الترتيب
        if ($shouldSort) {
            return $reverseSort 
                ? self::reverseSortWithOtherAtEnd($cleaned)
                : self::sortOptionsWithOtherAtEnd($cleaned);
        }

        return self::ensureOtherAtEnd($cleaned);
    }

    /**
     * يعالج Collection من CategoryField ويضمن "غير ذلك" في آخر كل options
     * 
     * @param \Illuminate\Support\Collection $fields
     * @param bool $shouldSort
     * @param bool $reverseSort
     * @return \Illuminate\Support\Collection
     */
    public static function processFieldsCollection($fields, bool $shouldSort = false, bool $reverseSort = true)
    {
        return $fields->transform(function ($field) use ($shouldSort, $reverseSort) {
            if (!empty($field->options) && is_array($field->options)) {
                $field->options = self::processOptions($field->options, $shouldSort, $reverseSort);
            }
            return $field;
        });
    }

    /**
     * يعالج array من options (مثل الماركات والموديلات)
     * 
     * @param array $optionsMap
     * @param bool $shouldSort
     * @param bool $reverseSort
     * @return array
     */
    public static function processOptionsMap(array $optionsMap, bool $shouldSort = false, bool $reverseSort = true): array
    {
        $processed = [];
        
        foreach ($optionsMap as $key => $values) {
            $processed[$key] = self::processOptions($values, $shouldSort, $reverseSort);
        }
        
        return $processed;
    }
}
