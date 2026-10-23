<?php

use App\Models\Category;
use App\Models\Item;
use App\Models\Setting;

beforeEach(function () {
    Setting::put('cafe_name', 'Jaru Café');
    $this->coffee = Category::create(['name' => 'Coffee']);
    $this->soon = Category::create(['name' => 'Ube Series', 'status' => 'soon']);
    $this->hiddenCat = Category::create(['name' => 'Secret', 'status' => 'hidden']);
});

it('serves every public page', function (string $path) {
    $this->get($path)->assertOk()->assertSee('Jaru Café');
})->with(['/', '/menu', '/about', '/visit']);

it('shows priced items and hides hidden ones on the menu', function () {
    Item::create(['category_id' => $this->coffee->id, 'name' => 'Café Latte', 'prices' => [['label' => '16oz', 'cents' => 12000]]]);
    Item::create(['category_id' => $this->coffee->id, 'name' => 'Staff Blend', 'hidden' => true]);
    Item::create(['category_id' => $this->hiddenCat->id, 'name' => 'Hidden Cat Item']);

    $this->get('/menu')
        ->assertSee('Café Latte')
        ->assertSee('₱120')
        ->assertDontSee('Staff Blend')
        ->assertDontSee('Hidden Cat Item');
});

it('flags sold out items', function () {
    Item::create(['category_id' => $this->coffee->id, 'name' => 'Iced Tea', 'sold_out' => true]);

    $this->get('/menu')->assertSee('Sold out');
});

it('lists coming-soon categories on the home page', function () {
    Item::create(['category_id' => $this->soon->id, 'name' => 'Ube Latte']);

    $this->get('/')->assertSee('Coming soon')->assertSee('Ube Series');
});

it('sends security headers', function () {
    $this->get('/menu')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Content-Security-Policy');
});

it('shows the preview menu notice by default and hides it when turned off', function () {
    $this->get('/menu')->assertSee('Preview menu');

    Setting::put('menu_preview', '0');

    $this->get('/menu')->assertDontSee('Preview menu');
});

it('shows photo placeholders on the home page until photos are added', function () {
    $this->get('/')->assertSee('Photo coming soon');

    Setting::put('cover', 'seed-cover.jpg');
    Setting::put('hero', 'seed-cover.jpg');

    $this->get('/')->assertDontSee('Photo coming soon');
});

it('shows a placeholder for a popular item without a photo', function () {
    Item::create(['category_id' => $this->coffee->id, 'name' => 'Iced Tea', 'featured' => true]);

    $this->get('/')->assertSee('Iced Tea')->assertSee('Photo coming soon');
});
