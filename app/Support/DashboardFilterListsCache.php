<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class DashboardFilterListsCache
{
    private const PREFIX = 'dashboard_filter_lists:';
    private const TTL_SECONDS = 300;
    private const AUTOMOTIVE_FIELD_CATEGORIES = ['cars', 'cars_rent', 'spare-parts'];

    public static function governorates(bool $includeInactive = false): string
    {
        return self::PREFIX . 'governorates' . ($includeInactive ? ':with-inactive' : '');
    }

    public static function sections(string $categorySlug, bool $includeInactive = false): string
    {
        return self::PREFIX . 'sections:' . $categorySlug . ($includeInactive ? ':with-inactive' : '');
    }

    public static function subSections(int $mainSectionId, bool $includeInactive = false): string
    {
        return self::PREFIX . 'sub-sections:' . $mainSectionId . ($includeInactive ? ':with-inactive' : '');
    }

    public static function automotive(bool $includeInactive = false): string
    {
        return self::PREFIX . 'automotive' . ($includeInactive ? ':with-inactive' : '');
    }

    public static function fieldCategory(string $categorySlug, bool $includeHidden = false): string
    {
        return self::PREFIX . 'field-category:' . $categorySlug . ($includeHidden ? ':with-hidden' : '');
    }

    public static function remember(string $key, Closure $callback, ?int $ttlSeconds = null): mixed
    {
        try {
            return Cache::remember($key, now()->addSeconds($ttlSeconds ?? self::TTL_SECONDS), $callback);
        } catch (\Throwable $exception) {
            Log::warning('Dashboard filter-lists cache unavailable, using uncached fallback.', [
                'key' => $key,
                'message' => $exception->getMessage(),
            ]);

            return $callback();
        }
    }

    public static function forget(string ...$keys): void
    {
        foreach ($keys as $key) {
            if ($key === '') {
                continue;
            }

            try {
                Cache::forget($key);
            } catch (\Throwable $exception) {
                Log::warning('Failed to forget dashboard filter-lists cache key.', [
                    'key' => $key,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    public static function flushGovernorates(): void
    {
        self::forget(
            self::governorates(),
            self::governorates(true)
        );
    }

    public static function flushAutomotive(): void
    {
        $keys = [self::automotive(), self::automotive(true)];

        foreach (self::AUTOMOTIVE_FIELD_CATEGORIES as $slug) {
            $keys[] = self::fieldCategory($slug);
            $keys[] = self::fieldCategory($slug, true);
        }

        self::forget(...$keys);
    }

    public static function flushFieldCategory(string $categorySlug): void
    {
        self::forget(
            self::fieldCategory($categorySlug),
            self::fieldCategory($categorySlug, true)
        );
    }

    public static function flushSections(string $categorySlug, ?int $mainSectionId = null): void
    {
        $keys = [
            self::sections($categorySlug),
            self::sections($categorySlug, true),
            self::fieldCategory($categorySlug),
            self::fieldCategory($categorySlug, true),
        ];

        if ($mainSectionId !== null) {
            $keys[] = self::subSections($mainSectionId);
            $keys[] = self::subSections($mainSectionId, true);
        }

        self::forget(...$keys);
    }
}
