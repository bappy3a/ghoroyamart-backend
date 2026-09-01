<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'label',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        
        Cache::forget("setting.{$key}");
    }

    /**
     * Get all settings grouped by group (cached)
     */
    public static function getGrouped(): array
    {
        return Cache::remember('settings.grouped', 3600, function () {
            return static::orderBy('group')
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get()
                ->groupBy('group')
                ->toArray();
        });
    }

    /**
     * Get all settings as key => value (cached, for API)
     */
    public static function getAllKeyValue(): array
    {
        return Cache::remember('settings.key_value', 3600, function () {
            return static::orderBy('group')
                ->orderBy('sort_order')
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    /**
     * Clear settings cache (call on any setting create/update/delete)
     */
    public static function clearCache(): void
    {
        Cache::forget('settings.grouped');
        Cache::forget('settings.key_value');
        static::pluck('key')->each(function ($key) {
            Cache::forget("setting.{$key}");
        });
    }

    /**
     * Boot method to clear cache on save/delete
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }
}
