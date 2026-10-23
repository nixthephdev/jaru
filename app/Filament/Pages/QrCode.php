<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Photos;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class QrCode extends Page
{
    protected string $view = 'filament.pages.qr-code';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static ?string $navigationLabel = 'QR code';

    protected static ?string $title = 'QR code';

    protected static ?int $navigationSort = 3;

    /** The QR opens the Menu page, not home: people scanning at a table want the menu. */
    protected function getViewData(): array
    {
        $url = rtrim(Setting::get('site_url') ?: 'https://jarucafe.shop/', '/').'/menu';
        $logo = Setting::get('logo');

        return [
            'url' => $url,
            'short' => preg_replace('~^https?://(www\.)?~', '', $url),
            'name' => Setting::get('cafe_name'),
            'logo' => Photos::url($logo),
            'logoSmall' => Photos::url($logo, small: true),
            'chalk' => asset('images/chalk.jpg'),
        ];
    }
}
