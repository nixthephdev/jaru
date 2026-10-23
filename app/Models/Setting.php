<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** Photo settings: each holds an uploads/ file name. */
    public const PHOTOS = ['logo', 'hero', 'cover'];

    private static ?array $all = null;

    public static function values(): array
    {
        return self::$all ??= Schema::hasTable('settings') ? static::query()->pluck('value', 'key')->all() : [];
    }

    public static function get(string $key, string $default = ''): string
    {
        return (string) (self::values()[$key] ?? $default);
    }

    public static function put(string $key, string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        self::$all = null;
    }

    /** The menu is a preview until the café sends the final one. On by default; the owner turns it off in Settings. */
    public static function menuIsPreview(): bool
    {
        return self::get('menu_preview', '1') === '1';
    }

    /** Non-empty lines of a multi-line setting (hours, story). */
    public static function lines(string $key): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', self::get($key)))));
    }
}
