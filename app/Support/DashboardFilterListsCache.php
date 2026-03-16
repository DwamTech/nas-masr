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

    public static function governorates(): string
    {
        return self::PREFIX . 'governorates';
    }

    public static function sections(string $categorySlug): string
    {
        return self::PREFIX . 'sections:' . $categorySlug;
    }

    public static function subSections(int $mainSectionId): string
    {
        return self::PREFIX . 'sub-sections:' . $mainSectionId;
    }

    public static function automotive(): string
    {
        return self::PREFIX . 'automotive';
    }

    public static function fieldCategory(string $categorySlug): string
    {
        return self::PREFIX . 'field-category:' . $categorySlug;
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
        self::forget(self::governorates());
    }

    public static function flushAutomotive(): void
    {
        $keys = [self::automotive()];

        foreach (self::AUTOMOTIVE_FIELD_CATEGORIES as $slug) {
            $keys[] = self::fieldCategory($slug);
        }

        self::forget(...$keys);
    }

    public static function flushFieldCategory(string $categorySlug): void
    {
        self::forget(self::fieldCategory($categorySlug));
    }

    public static function flushSections(string $categorySlug, ?int $mainSectionId = null): void
    {
        $keys = [self::sections($categorySlug), self::fieldCategory($categorySlug)];

        if ($mainSectionId !== null) {
            $keys[] = self::subSections($mainSectionId);
        }

        self::forget(...$keys);
    }
}
