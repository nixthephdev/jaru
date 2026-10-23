<?php

namespace App\Models;

use App\Support\Money;
use App\Support\Photos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    protected $fillable = ['category_id', 'name', 'description', 'prices', 'photo', 'sold_out', 'hidden', 'featured', 'sort'];

    protected $casts = ['prices' => 'array', 'sold_out' => 'boolean', 'hidden' => 'boolean', 'featured' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (self $i) {
            $i->description ??= '';
            $i->photo ??= '';
            // Drop rows left completely blank in the form.
            $rows = array_values(array_filter($i->prices ?? [], fn ($p) => ($p['label'] ?? '') !== '' || ($p['cents'] ?? null) !== null));
            $i->prices = $rows ?: [['label' => '', 'cents' => null]];
            if (! $i->exists && ! $i->sort) {
                $i->sort = (int) static::where('category_id', $i->category_id)->max('sort') + 1;
            }
        });

        static::created(fn (self $i) => Activity::log("Added {$i->name} to {$i->category->name}".($i->priceText() ? " ({$i->priceText()})" : '')));

        static::updated(function (self $i) {
            if ($i->wasChanged('photo')) {
                Photos::delete($i->getOriginal('photo'));
            }
            $changes = [];
            if ($i->wasChanged('name')) {
                $changes[] = "renamed from \"{$i->getOriginal('name')}\"";
            }
            if ($i->wasChanged('prices')) {
                $old = Money::priceText(json_decode($i->getRawOriginal('prices') ?: '[]', true)) ?: 'none';
                $changes[] = "price $old → ".($i->priceText() ?: 'none');
            }
            if ($i->wasChanged('category_id')) {
                $changes[] = "moved to {$i->category->name}";
            }
            if ($i->wasChanged('description')) {
                $changes[] = 'description';
            }
            if ($i->wasChanged('photo')) {
                $changes[] = $i->photo ? 'new photo' : 'photo removed';
            }
            if ($i->wasChanged('sold_out')) {
                $changes[] = $i->sold_out ? 'sold out' : 'available';
            }
            if ($i->wasChanged('hidden')) {
                $changes[] = $i->hidden ? 'hidden' : 'shown';
            }
            if ($i->wasChanged('featured')) {
                $changes[] = $i->featured ? 'added to Popular' : 'removed from Popular';
            }
            if ($changes) {
                Activity::log("Edited {$i->name}: ".implode(', ', $changes));
            }
        });

        static::deleted(function (self $i) {
            Photos::delete($i->photo);
            Activity::log("Deleted {$i->name}");
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Prices that have an amount, e.g. [['label' => '16oz', 'cents' => 12000]]. */
    public function pricedRows(): array
    {
        return array_values(array_filter($this->prices ?? [], fn ($p) => $p['cents'] !== null));
    }

    public function priceText(): string
    {
        return Money::priceText($this->prices ?? []);
    }

    /** "from ₱120" for the cheapest price, '' when nothing is priced. */
    public function fromPrice(): string
    {
        $rows = $this->pricedRows();

        return $rows ? (count($rows) > 1 ? 'from ' : '').Money::peso(min(array_column($rows, 'cents'))) : '';
    }

    public function photoUrl(bool $small = false): string
    {
        return Photos::url($this->photo, $small);
    }
}
