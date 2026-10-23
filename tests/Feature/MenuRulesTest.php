<?php

use App\Models\Activity;
use App\Models\Category;
use App\Models\Item;
use App\Support\Money;

it('formats pesos from centavos', function () {
    expect(Money::peso(12000))->toBe('₱120')
        ->and(Money::peso(12050))->toBe('₱120.50')
        ->and(Money::peso(null))->toBe('')
        ->and(Money::parse('₱1,200'))->toBe(120000)
        ->and(Money::parse(''))->toBeNull();
});

it('appends new categories and items at the end', function () {
    $a = Category::create(['name' => 'A']);
    $b = Category::create(['name' => 'B']);
    $i1 = Item::create(['category_id' => $a->id, 'name' => 'One']);
    $i2 = Item::create(['category_id' => $a->id, 'name' => 'Two']);

    expect($b->sort)->toBeGreaterThan($a->sort)->and($i2->sort)->toBeGreaterThan($i1->sort);
});

it('keeps a blank price row when nothing is priced', function () {
    $item = Item::create(['category_id' => Category::create(['name' => 'A'])->id, 'name' => 'One', 'prices' => [['label' => '', 'cents' => null]]]);

    expect($item->prices)->toBe([['label' => '', 'cents' => null]])->and($item->priceText())->toBe('');
});

it('drops fully blank price rows', function () {
    $item = Item::create(['category_id' => Category::create(['name' => 'A'])->id, 'name' => 'One',
        'prices' => [['label' => '12oz', 'cents' => 10000], ['label' => '', 'cents' => null]]]);

    expect($item->prices)->toHaveCount(1)->and($item->priceText())->toBe('12oz ₱100');
});

it('shows the cheapest price as "from"', function () {
    $item = Item::create(['category_id' => Category::create(['name' => 'A'])->id, 'name' => 'One',
        'prices' => [['label' => '12oz', 'cents' => 10000], ['label' => '16oz', 'cents' => 13000]]]);

    expect($item->fromPrice())->toBe('from ₱100');
});

it('logs item changes to recent changes', function () {
    $item = Item::create(['category_id' => Category::create(['name' => 'Coffee'])->id, 'name' => 'Latte']);
    $item->update(['sold_out' => true]);
    $item->update(['prices' => [['label' => '', 'cents' => 12000]]]);

    $log = Activity::pluck('what')->all();
    expect($log)->toContain('Added Latte to Coffee')
        ->and($log)->toContain('Edited Latte: sold out')
        ->and($log)->toContain('Edited Latte: price none → ₱120');
});

it('does not log reordering as an edit', function () {
    $cat = Category::create(['name' => 'Coffee']);
    Activity::query()->delete();
    $cat->fresh()->update(['sort' => 9]);

    expect(Activity::count())->toBe(0);
});

it('deletes a category together with its items', function () {
    $cat = Category::create(['name' => 'Coffee']);
    Item::create(['category_id' => $cat->id, 'name' => 'Latte']);
    $cat->delete();

    expect(Item::count())->toBe(0)->and(Activity::pluck('what')->last())->toBe('Deleted category Coffee and its 1 items');
});

it('keeps only the latest 200 changes', function () {
    foreach (range(1, 205) as $i) {
        Activity::log("change $i");
    }

    expect(Activity::count())->toBe(200);
});
