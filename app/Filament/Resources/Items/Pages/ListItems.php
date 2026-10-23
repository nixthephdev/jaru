<?php

namespace App\Filament\Resources\Items\Pages;

use App\Filament\Resources\Items\ItemResource;
use App\Filament\Resources\Items\Tables\ItemsTable;
use App\Models\Item;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListItems extends ListRecords
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('allAvailable')
                ->label('Mark all available')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Every sold-out item goes back on the menu as available.')
                ->disabled(fn () => ! Item::where('sold_out', true)->exists())
                ->action(function () {
                    $n = ItemsTable::allAvailable();
                    Notification::make()->success()->title("All items are available again ($n updated).")->send();
                }),
            CreateAction::make()->label('Add item'),
        ];
    }
}
