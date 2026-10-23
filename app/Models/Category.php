<?php

namespace App\Models;

use App\Support\Photos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public const STATUSES = [
        'active' => 'Show on menu',
        'soon' => 'Coming soon (shown, not orderable yet)',
        'hidden' => 'Hidden',
    ];

    protected $fillable = ['name', 'note', 'status', 'sort'];

    /** Photos of a category's items, kept until the cascade delete is done. */
    private static array $orphans = [];

    protected static function booted(): void
    {
        static::saving(function (self $c) {
            $c->note ??= '';
            $c->status ??= 'active';
            if (! $c->exists) {
                $c->sort = $c->sort ?: (int) static::max('sort') + 1;
            }
        });
        static::saved(function (self $c) {
            if ($c->wasRecentlyCreated) {
                Activity::log("Added category {$c->name}");
            } elseif ($c->wasChanged(['name', 'note', 'status'])) {
                Activity::log("Edited category {$c->name}".($c->wasChanged('name') ? " (was {$c->getOriginal('name')})" : '').", status: {$c->status}");
            }
        });
        static::deleting(function (self $c) {
            self::$orphans[$c->id] = $c->items()->pluck('photo')->all();
            Activity::log("Deleted category {$c->name}".($c->items()->count() ? " and its {$c->items()->count()} items" : ''));
        });
        static::deleted(function (self $c) {
            foreach (self::$orphans[$c->id] ?? [] as $photo) {
                Photos::delete($photo);
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class)->orderBy('sort')->orderBy('id');
    }
}
