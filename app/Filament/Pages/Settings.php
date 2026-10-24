<?php

namespace App\Filament\Pages;

use App\Models\Activity;
use App\Models\Setting;
use App\Support\Photos;
use App\Support\Seo;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

/**
 * @property-read Schema $form
 */
class Settings extends Page
{
    protected string $view = 'filament.pages.settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 5;

    /** @var array<string, mixed> */
    public ?array $data = [];

    private const TEXT_KEYS = ['cafe_name', 'tagline', 'address', 'landmark', 'map_query', 'hours', 'phone', 'email',
        'facebook', 'instagram', 'about_text', 'site_url', 'ga_id', 'google_verification'];

    public function mount(): void
    {
        $this->form->fill(collect(array_merge(self::TEXT_KEYS, Setting::PHOTOS))
            ->mapWithKeys(fn (string $k) => [$k => Setting::get($k)])
            ->put('menu_preview', Setting::menuIsPreview())
            ->all());
    }

    public function form(Schema $schema): Schema
    {
        $link = fn (string $name, string $label) => TextInput::make($name)->label($label)->url()->maxLength(200)->placeholder('https://');

        return $schema
            ->statePath('data')
            ->components([
                Section::make('The café')
                    ->description('Every empty field is simply hidden on the website.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('cafe_name')->label('Café name')->required()->maxLength(200),
                        TextInput::make('tagline')->helperText('Big headline on the home page.')->maxLength(200),
                        TextInput::make('address')->helperText('e.g. 123 Magsaysay Ave, Naga City')->maxLength(200),
                        TextInput::make('landmark')->helperText('Helps people find you, e.g. "Beside Rosam, near SSS".')->maxLength(200),
                        TextInput::make('map_query')->label('Map location')->helperText('Optional. What Google Maps should search, if the address alone finds the wrong place.')->maxLength(200)->columnSpanFull(),
                        Textarea::make('hours')->label('Opening hours')->rows(3)->helperText('One line each, e.g. "Mon–Fri 8am–9pm".')->maxLength(3000),
                        Textarea::make('about_text')->label('Our story')->rows(6)->helperText('Shown on the About page. One paragraph per line. The first lines also appear on the home page.')->maxLength(3000),
                    ]),
                Section::make('Menu')
                    ->schema([
                        Toggle::make('menu_preview')
                            ->label('Show the "preview menu" notice')
                            ->helperText('Turn this on while the menu, photos and prices are still being filled in. Turn it off once the final menu is in.'),
                    ]),
                Section::make('Contact and links')
                    ->columns(2)
                    ->schema([
                        TextInput::make('phone')->tel()->maxLength(200),
                        TextInput::make('email')->email()->maxLength(200),
                        $link('facebook', 'Facebook page link'),
                        $link('instagram', 'Instagram link'),
                        $link('site_url', 'Website address')->helperText('Used for the QR code and link previews.')->columnSpanFull(),
                    ]),
                Section::make('Google')
                    ->description('Optional. Leave empty and nothing from Google is loaded on the website.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('ga_id')
                            ->label('Google Analytics ID')
                            ->placeholder('G-XXXXXXXXXX')
                            ->regex('/^\s*G-[A-Z0-9]{4,12}\s*$/i')
                            ->validationMessages(['regex' => 'This should look like G-ABC123XYZ (Analytics › Admin › Data streams).'])
                            ->helperText('Counts visitors. Find it in Google Analytics › Admin › Data streams.')
                            ->maxLength(40),
                        TextInput::make('google_verification')
                            ->label('Google Search Console code')
                            ->helperText('Proves to Google you own the site. Paste the code or the whole <meta> tag from Search Console (HTML tag method).')
                            ->rule(fn () => fn (string $attribute, $value, $fail) => filled($value) && Seo::verificationCode($value) === ''
                                ? $fail('Paste the code from Search Console, or the whole <meta name="google-site-verification" …> tag.') : null)
                            ->maxLength(300),
                    ]),
                Section::make('Photos')
                    ->columns(3)
                    ->schema([
                        self::photo('logo', 'Logo', 'Square works best. Used in the site header, QR cards and browser tab.', '1:1'),
                        self::photo('hero', 'Second photo', 'A wide-ish photo (the counter works well). Shown beside the story on Home and on About.', '4:3'),
                        self::photo('cover', 'Home featured photo', 'A tall (portrait) photo inside the café. Shown in an arch under the Home headline, and on About.', '4:5'),
                    ]),
            ]);
    }

    private static function photo(string $name, string $label, string $help, string $ratio): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->helperText($help)
            ->disk('uploads')
            ->image()
            ->imageEditor()
            ->imageEditorAspectRatioOptions([$ratio])
            ->maxSize(12 * 1024)
            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file) use ($name) {
                try {
                    return Photos::save($file->getRealPath());
                } catch (RuntimeException $e) {
                    throw ValidationException::withMessages(["data.$name" => $e->getMessage()]);
                }
            })
            ->deleteUploadedFileUsing(fn () => null);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $changed = [];

        foreach (array_merge(self::TEXT_KEYS, Setting::PHOTOS) as $key) {
            $new = trim(str_replace("\r\n", "\n", (string) ($data[$key] ?? '')));
            if ($key === 'cafe_name' && $new === '') {
                continue;
            }
            $old = Setting::get($key);
            if ($new === $old) {
                continue;
            }

            Setting::put($key, $new);
            if (in_array($key, Setting::PHOTOS, true)) {
                Photos::delete($old);
                $changed[] = ['logo' => 'logo', 'hero' => 'second photo', 'cover' => 'home featured photo'][$key];
            } else {
                $changed[] = str_replace('_', ' ', $key);
            }
        }

        if ((bool) ($data['menu_preview'] ?? false) !== Setting::menuIsPreview()) {
            Setting::put('menu_preview', $data['menu_preview'] ? '1' : '0');
            $changed[] = 'preview menu notice '.($data['menu_preview'] ? 'on' : 'off');
        }

        if ($changed) {
            Activity::log('Settings changed: '.implode(', ', $changed));
        }

        Notification::make()
            ->success()
            ->title($changed ? 'Settings saved.' : 'Nothing changed.')
            ->send();
    }
}
