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
     * يعالج options من Database ويضمن "غير ذلك" في الآخر
     * 
     * @param array|null $options القائمة من Database
     * @param bool $shouldSort هل نرتب القائمة أبجدياً؟
     * @return array القائمة معالجة
     */
    public static function processOptions(?array $options, bool $shouldSort = false): array
    {
        if (empty($options)) {
            return [self::OTHER_OPTION];
        }

        // تنظيف القائمة من القيم الفارغة
        $cleaned = array_filter(
            array_map('trim', $options),
            function ($opt) {
                return !empty($opt);
            }
        );

        if (empty($cleaned)) {
            return [self::OTHER_OPTION];
        }

        // ترتيب أو مجرد وضع "غير ذلك" في الآخر
        return $shouldSort 
            ? self::sortOptionsWithOtherAtEnd($cleaned)
            : self::ensureOtherAtEnd($cleaned);
    }

    /**
     * يعالج Collection من CategoryField ويضمن "غير ذلك" في آخر كل options
     * 
     * @param \Illuminate\Support\Collection $fields
     * @param bool $shouldSort
     * @return \Illuminate\Support\Collection
     */
    public static function processFieldsCollection($fields, bool $shouldSort = false)
    {
        return $fields->transform(function ($field) use ($shouldSort) {
            if (!empty($field->options) && is_array($field->options)) {
                $field->options = self::processOptions($field->options, $shouldSort);
            }
            return $field;
        });
    }

    /**
     * يعالج array من options (مثل الماركات والموديلات)
     * 
     * @param array $optionsMap
     * @param bool $shouldSort
     * @return array
     */
    public static function processOptionsMap(array $optionsMap, bool $shouldSort = false): array
    {
        $processed = [];
        
        foreach ($optionsMap as $key => $values) {
            $processed[$key] = self::processOptions($values, $shouldSort);
        }
        
        return $processed;
    }
}
