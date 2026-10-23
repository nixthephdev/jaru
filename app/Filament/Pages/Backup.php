<?php

namespace App\Filament\Pages;

use App\Models\Activity;
use App\Support\Photos;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class Backup extends Page
{
    protected string $view = 'filament.pages.backup';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?int $navigationSort = 4;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label('Download backup')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->download()),
        ];
    }

    /** Streams a zip of the database and all photos. Restore: extract it over the site's database/ and public/uploads/. */
    public function download(): ?StreamedResponse
    {
        if (! class_exists(ZipArchive::class)) {
            Notification::make()->danger()
                ->title('Backup needs the PHP "zip" extension.')
                ->body('Turn it on in cPanel › Select PHP Version › Extensions.')
                ->send();

            return null;
        }

        $snap = tempnam(sys_get_temp_dir(), 'jarudb');
        $zipFile = tempnam(sys_get_temp_dir(), 'jaruzip');
        @unlink($snap);

        try {
            // Consistent copy even if someone is saving at the same moment.
            DB::statement('VACUUM INTO '.DB::getPdo()->quote($snap));
        } catch (\Throwable) {
            copy(database_path('database.sqlite'), $snap);
        }

        $zip = new ZipArchive;
        $zip->open($zipFile, ZipArchive::OVERWRITE);
        $zip->addFile($snap, 'database/database.sqlite');
        foreach (glob(Photos::dir().'/*.jpg') ?: [] as $f) {
            $zip->addFile($f, 'public/uploads/'.basename($f));
        }
        $zip->close();

        Activity::log('Backup downloaded');

        return response()->streamDownload(function () use ($zipFile, $snap) {
            readfile($zipFile);
            @unlink($zipFile);
            @unlink($snap);
        }, 'jaru-menu-backup-'.now()->format('Y-m-d').'.zip', ['Content-Type' => 'application/zip', 'Cache-Control' => 'no-store']);
    }
}
