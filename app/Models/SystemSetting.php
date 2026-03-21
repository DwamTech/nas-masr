<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $table = 'system_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'meta',
        'autoload',
    ];

    protected $casts = [
        'meta' => 'array',
        'autoload' => 'boolean',
    ];

    public static function cachedPositiveInt(string $key, int $default): int
    {
        $value = Cache::remember("settings:{$key}", now()->addHours(6), function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });

        $value = is_numeric($value) ? (int) $value : $default;

        return $value > 0 ? $value : $default;
    }
}
