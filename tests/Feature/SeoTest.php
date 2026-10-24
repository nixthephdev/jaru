<?php

use App\Models\Category;
use App\Models\Item;
use App\Models\Setting;
use App\Support\Seo;

beforeEach(function () {
    Setting::put('cafe_name', 'Jaru Café');
    Setting::put('address', 'Maharlika Highway, Concepcion Pequeña, Naga City');
    Setting::put('hours', "Sunday – Thursday: 10 AM – 10 PM\nFriday – Saturday: 10 AM – 11 PM");
    Setting::put('phone', '0921 716 1921');
});

function jsonLd(string $html, string $type): array
{
    preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $html, $m);
    foreach ($m[1] as $json) {
        $data = json_decode($json, true);
        if (($data['@type'] ?? '') === $type) {
            return $data;
        }
    }

    return [];
}

it('reads opening hours lines into schema.org hours', function () {
    expect(Seo::openingHours([
        'Sunday – Thursday: 10 AM – 10 PM',
        'Mon–Fri 8am–9:30pm',
        'Daily 7am to 12pm',
        'Closed on holidays',
    ]))->toBe([
        ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'], 'opens' => '10:00', 'closes' => '22:00'],
        ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], 'opens' => '08:00', 'closes' => '21:30'],
        ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'], 'opens' => '07:00', 'closes' => '12:00'],
    ]);
});

it('describes the café to search engines on every page', function (string $path) {
    $cafe = jsonLd($this->get($path)->assertOk()->getContent(), 'CafeOrCoffeeShop');

    expect($cafe['name'])->toBe('Jaru Café')
        ->and($cafe['address']['addressLocality'])->toBe('Naga City')
        ->and($cafe['address']['streetAddress'])->toBe('Maharlika Highway, Concepcion Pequeña')
        ->and($cafe['telephone'])->toBe('0921 716 1921')
        ->and($cafe['openingHoursSpecification'])->toHaveCount(2);
})->with(['/', '/menu', '/about', '/visit']);

it('puts the town in titles, the canonical link and the home heading', function () {
    $this->get('/')
        ->assertSee('<title>Jaru Café · Coffee &amp; matcha café in Naga City</title>', false)
        ->assertSee('<link rel="canonical" href="https://jarucafe.shop/">', false)
        ->assertSee('Café in Naga City');
    $this->get('/menu')->assertSee('<title>Menu · Jaru Café, Naga City</title>', false);
});

it('lists dishes with prices in the menu data and leaves out coming-soon ones', function () {
    $coffee = Category::create(['name' => 'Coffee']);
    $soon = Category::create(['name' => 'Ube Series', 'status' => 'soon']);
    Item::create(['category_id' => $coffee->id, 'name' => 'Café Latte', 'prices' => [['label' => '16oz', 'cents' => 12000]], 'sold_out' => true]);
    Item::create(['category_id' => $soon->id, 'name' => 'Ube Latte']);

    $menu = jsonLd($this->get('/menu')->getContent(), 'Menu');

    expect($menu['hasMenuSection'])->toHaveCount(1)
        ->and($menu['hasMenuSection'][0]['hasMenuItem'][0]['offers'][0])->toMatchArray([
            'name' => '16oz', 'price' => '120.00', 'priceCurrency' => 'PHP', 'availability' => 'https://schema.org/OutOfStock',
        ]);
    expect(jsonLd($this->get('/')->getContent(), 'CafeOrCoffeeShop')['priceRange'])->toBe('₱120–₱120');
});

it('cannot be broken out of by a dish name', function () {
    Item::create(['category_id' => Category::create(['name' => 'Coffee'])->id, 'name' => '</script><b>x']);

    $this->get('/menu')->assertDontSee('</script><b>x', false);
});

it('gives dish photos descriptive alt text', function () {
    expect(Seo::dishAlt('Dirty Matcha'))->toBe('Dirty Matcha at Jaru Café, Naga City');
    $this->get('/menu')->assertSee('data-alt-suffix="at Jaru Café, Naga City"', false);
});

it('serves a sitemap of the public pages', function () {
    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<loc>https://jarucafe.shop/</loc>', false)
        ->assertSee('<loc>https://jarucafe.shop/menu</loc>', false)
        ->assertDontSee('/admin');
});

it('loads Google Analytics only once an ID is set, and allows it in the CSP only then', function () {
    $this->get('/')->assertDontSee('googletagmanager');
    expect($this->get('/')->headers->get('Content-Security-Policy'))->not->toContain('googletagmanager');

    Setting::put('ga_id', ' g-abc123xyz ');

    $res = $this->get('/');
    $res->assertSee('<meta name="ga-id" content="G-ABC123XYZ">', false)
        ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-ABC123XYZ', false);
    expect($res->headers->get('Content-Security-Policy'))->toContain('script-src \'self\' https://www.googletagmanager.com');
});

it('has a privacy page, linked from every page, that mentions Analytics only while it is on', function () {
    $this->get('/')->assertSee('href="'.url('privacy').'"', false);
    $this->get('/privacy')->assertOk()->assertSee('Data Privacy Act')->assertDontSee('Google Analytics');
    $this->get('/sitemap.xml')->assertSee('<loc>https://jarucafe.shop/privacy</loc>', false);

    Setting::put('ga_id', 'G-ABC123XYZ');

    $this->get('/privacy')->assertSee('Google Analytics')->assertSee('opt-out add-on');
});

it('accepts the Search Console code or the whole meta tag', function () {
    expect(Seo::verificationCode('<meta name="google-site-verification" content="abcDEF123_-xyz789" />'))->toBe('abcDEF123_-xyz789')
        ->and(Seo::verificationCode('abcDEF123_-xyz789'))->toBe('abcDEF123_-xyz789')
        ->and(Seo::verificationCode('"><script>'))->toBe('');

    Setting::put('google_verification', 'abcDEF123_-xyz789');
    $this->get('/')->assertSee('<meta name="google-site-verification" content="abcDEF123_-xyz789">', false);
});
