<?php

use App\Models\Setting;
use App\Support\Money;

function setting(string $key): string
{
    return Setting::get($key);
}

function setting_lines(string $key): array
{
    return Setting::lines($key);
}

function peso(?int $cents): string
{
    return Money::peso($cents);
}

function tel(string $phone): string
{
    return 'tel:'.preg_replace('/[^\d+]/', '', $phone);
}

function maps_link(): string
{
    return 'https://www.google.com/maps/search/?api=1&query='
        .rawurlencode(setting('map_query') ?: setting('cafe_name').', '.setting('address'));
}
