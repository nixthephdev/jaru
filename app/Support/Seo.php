<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Item;
use App\Models\Setting;
use Illuminate\Support\Collection;

/** Structured data (schema.org JSON-LD) and the small text helpers search engines read. */
class Seo
{
    private const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    public static function site(): string
    {
        return rtrim(Setting::get('site_url') ?: 'https://jarucafe.shop/', '/');
    }

    /** The town from the address ("…, Naga City" gives "Naga City"), used in alt text and titles. */
    public static function city(): string
    {
        $parts = array_map('trim', explode(',', Setting::get('address')));

        return end($parts) ?: '';
    }

    /** "Café Latte at Jaru Café, Naga City": alt text for a dish photo. */
    public static function dishAlt(string $dish): string
    {
        return trim($dish.' at '.Setting::get('cafe_name').(self::city() !== '' ? ', '.self::city() : ''));
    }

    /**
     * Opening-hours lines as schema.org specifications. Understands "Sunday – Thursday: 10 AM – 10 PM",
     * "Mon–Fri 8am–9pm", "Saturday: 9:30am - 6pm" and "Daily 7am–10pm"; lines it cannot read are skipped.
     */
    public static function openingHours(array $lines): array
    {
        $day = '(mon|tue|wed|thu|fri|sat|sun)[a-z]*\.?';
        $time = '(\d{1,2})(?::(\d{2}))?\s*([ap])\.?\s*m\.?';
        $dash = '\s*(?:[-–—]|to)\s*';
        $out = [];

        foreach ($lines as $line) {
            if (! preg_match("/^\s*(?:(daily|everyday|every day)|$day(?:$dash$day)?)\s*:?\s*$time$dash$time\s*$/iu", $line, $m)) {
                continue;
            }
            $all = $m[1] !== '';
            $from = $all ? 0 : self::dayIndex($m[2]);
            $to = $all ? 6 : (($m[3] ?? '') !== '' ? self::dayIndex($m[3]) : $from);
            $days = [];
            for ($i = $from; ; $i = ($i + 1) % 7) { // wraps, so "Sunday – Thursday" works
                $days[] = self::DAYS[$i];
                if ($i === $to) {
                    break;
                }
            }
            $out[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $days,
                'opens' => self::clock($m[4], $m[5], $m[6]),
                'closes' => self::clock($m[7], $m[8], $m[9]),
            ];
        }

        return $out;
    }

    private static function dayIndex(string $word): int
    {
        return array_search(strtolower(substr($word, 0, 3)), ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'], true);
    }

    private static function clock(string $h, string $min, string $ampm): string
    {
        $h = (int) $h % 12 + (strtolower($ampm) === 'p' ? 12 : 0);

        return sprintf('%02d:%02d', $h, (int) $min);
    }

    /** The café itself, on every page. */
    public static function business(): array
    {
        $name = Setting::get('cafe_name');
        $photos = array_values(array_filter([Setting::get('cover'), Setting::get('hero')]));
        $parts = array_map('trim', explode(',', Setting::get('address')));
        $locality = count($parts) > 1 ? array_pop($parts) : '';
        $cents = Item::where('hidden', false)->get()->flatMap(fn (Item $i) => array_column($i->pricedRows(), 'cents'));

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'CafeOrCoffeeShop',
            '@id' => self::site().'/#cafe',
            'name' => $name,
            'slogan' => Setting::get('tagline'),
            'description' => Setting::lines('about_text')[0] ?? Setting::get('tagline'),
            'url' => self::site().'/',
            'menu' => self::site().'/menu',
            'telephone' => Setting::get('phone'),
            'email' => Setting::get('email'),
            'logo' => Setting::get('logo') ? Photos::url(Setting::get('logo')) : null,
            'image' => array_map(fn ($f) => Photos::url($f), $photos) ?: null,
            'address' => Setting::get('address') ? array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => implode(', ', $parts),
                'addressLocality' => $locality,
                'addressCountry' => 'PH',
            ]) : null,
            'hasMap' => maps_link(),
            'openingHoursSpecification' => self::openingHours(Setting::lines('hours')) ?: null,
            'servesCuisine' => ['Coffee', 'Matcha', 'Filipino'],
            'priceRange' => $cents->isNotEmpty() ? Money::peso($cents->min()).'–'.Money::peso($cents->max()) : null,
            'currenciesAccepted' => 'PHP',
            'sameAs' => array_values(array_filter([Setting::get('instagram'), Setting::get('facebook')])) ?: null,
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    /** The menu, with each dish and its prices (coming-soon categories are left out). */
    public static function menu(Collection $cats): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Menu',
            'name' => Setting::get('cafe_name').' menu',
            'url' => self::site().'/menu',
            'inLanguage' => 'en-PH',
            'hasMenuSection' => $cats->where('status', 'active')->map(fn (Category $c) => array_filter([
                '@type' => 'MenuSection',
                'name' => $c->name,
                'description' => $c->note ?: null,
                'hasMenuItem' => $c->items->map(fn (Item $i) => array_filter([
                    '@type' => 'MenuItem',
                    'name' => $i->name,
                    'description' => $i->description ?: null,
                    'image' => $i->photo !== '' ? $i->photoUrl() : null,
                    'offers' => array_map(fn ($p) => array_filter([
                        '@type' => 'Offer',
                        'name' => $p['label'] ?: null,
                        'price' => number_format($p['cents'] / 100, 2, '.', ''),
                        'priceCurrency' => 'PHP',
                        'availability' => 'https://schema.org/'.($i->sold_out ? 'OutOfStock' : 'InStock'),
                    ]), $i->pricedRows()) ?: null,
                ]))->values()->all(),
            ]))->values()->all(),
        ];
    }

    /** JSON-LD script tag. Escaped so a "</script>" in a name cannot break out of it. */
    public static function jsonLd(array $data): string
    {
        return '<script type="application/ld+json">'
            .json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP)
            .'</script>';
    }

    /** "G-ABC123" from whatever the owner pastes; '' if it is not a GA4 measurement ID. */
    public static function analyticsId(string $raw): string
    {
        return preg_match('/\bG-[A-Z0-9]{4,12}\b/i', $raw, $m) ? strtoupper($m[0]) : '';
    }

    /** The code from a Search Console verification, whether the owner pastes the code or the whole meta tag. */
    public static function verificationCode(string $raw): string
    {
        if (preg_match('/content=["\']([^"\']+)["\']/i', $raw, $m)) {
            $raw = $m[1];
        }

        return preg_match('/^[\w-]{10,100}$/', trim($raw)) ? trim($raw) : '';
    }
}
