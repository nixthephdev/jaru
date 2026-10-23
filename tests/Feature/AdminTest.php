<?php

use App\Filament\Pages\Backup;
use App\Filament\Pages\Settings;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Items\Pages\CreateItem;
use App\Filament\Resources\Items\Pages\EditItem;
use App\Filament\Resources\Items\Pages\ListItems;
use App\Models\Activity;
use App\Models\Category;
use App\Models\Item;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->cat = Category::create(['name' => 'Coffee']);
});

it('protects the admin panel', function () {
    auth()->logout();

    $this->get('/admin')->assertRedirect('/admin/login');
});

it('renders every admin screen', function (string $path) {
    $this->get($path)->assertOk();
})->with(['/admin', '/admin/items', '/admin/categories', '/admin/settings', '/admin/qr-code', '/admin/backup', '/admin/profile']);

it('creates an item with prices in pesos', function () {
    Livewire::test(CreateItem::class)
        ->fillForm([
            'name' => 'Café Latte',
            'category_id' => $this->cat->id,
            'prices' => [['label' => '12oz', 'cents' => '120'], ['label' => '16oz', 'cents' => '140.50']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Item::first()->prices)->toBe([['label' => '12oz', 'cents' => 12000], ['label' => '16oz', 'cents' => 14050]]);
});

it('requires a name and category', function () {
    Livewire::test(CreateItem::class)->fillForm(['name' => '', 'category_id' => null])->call('create')
        ->assertHasFormErrors(['name' => 'required', 'category_id' => 'required']);
});

it('shows stored centavos as pesos when editing', function () {
    $item = Item::create(['category_id' => $this->cat->id, 'name' => 'Latte', 'prices' => [['label' => '', 'cents' => 12050]]]);

    $prices = Livewire::test(EditItem::class, ['record' => $item->id])->get('data.prices');

    expect(array_values($prices))->toBe([['label' => '', 'cents' => 120.5]]);
});

it('lists items and marks everything available again', function () {
    Item::create(['category_id' => $this->cat->id, 'name' => 'Latte', 'sold_out' => true]);
    Item::create(['category_id' => $this->cat->id, 'name' => 'Mocha', 'sold_out' => true]);

    Livewire::test(ListItems::class)->assertCanSeeTableRecords(Item::all())
        ->callAction('allAvailable');

    expect(Item::where('sold_out', true)->count())->toBe(0)
        ->and(Activity::pluck('what')->last())->toBe('Marked all available (2 items)');
});

it('toggles sold out from the table', function () {
    $item = Item::create(['category_id' => $this->cat->id, 'name' => 'Latte']);

    Livewire::test(ListItems::class)->call('updateTableColumnState', 'sold_out', $item->id, true);

    expect($item->fresh()->sold_out)->toBeTrue();
});

it('lists categories', function () {
    Item::create(['category_id' => $this->cat->id, 'name' => 'Latte']);

    Livewire::test(ListCategories::class)->assertCanSeeTableRecords(Category::all());
});

it('saves settings and logs the change', function () {
    Livewire::test(Settings::class)
        ->fillForm(['cafe_name' => 'Jaru Café', 'phone' => '0917 123 4567', 'hours' => 'Mon–Fri 8am–9pm'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Setting::get('phone'))->toBe('0917 123 4567')
        ->and(Activity::pluck('what')->last())->toContain('Settings changed: ')->toContain('phone');
});

it('rejects an invalid social link', function () {
    Livewire::test(Settings::class)->fillForm(['cafe_name' => 'Jaru', 'facebook' => 'not a link'])->call('save')
        ->assertHasFormErrors(['facebook']);
});

it('downloads a backup zip', function () {
    Livewire::test(Backup::class)->callAction('download')->assertFileDownloaded();
});

it('turns the preview menu notice off from settings', function () {
    expect(Setting::menuIsPreview())->toBeTrue();

    Livewire::test(Settings::class)->fillForm(['cafe_name' => 'Jaru', 'menu_preview' => false])->call('save');

    expect(Setting::menuIsPreview())->toBeFalse()
        ->and(Activity::pluck('what')->last())->toContain('preview menu notice off');
});
