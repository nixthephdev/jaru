<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Items\ItemResource;
use App\Models\Category;
use App\Models\Item;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MenuStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $sold = Item::where('sold_out', true)->count();
        $noPrice = Item::where('prices', 'like', '%"cents":null%')->count();

        return [
            Stat::make('Menu items', Item::count())
                ->description(Category::count().' categories')
                ->icon('heroicon-o-cake')
                ->url(ItemResource::getUrl()),
            Stat::make('Sold out', $sold)
                ->description($sold ? 'Tap to review' : 'Everything is available')
                ->color($sold ? 'danger' : 'success')
                ->icon('heroicon-o-x-circle')
                ->url(ItemResource::getUrl(parameters: ['filters' => ['sold_out' => ['isActive' => true]]])),
            Stat::make('No price yet', $noPrice)
                ->description($noPrice ? 'Customers see no price for these' : 'All items are priced')
                ->color($noPrice ? 'warning' : 'success')
                ->icon('heroicon-o-banknotes')
                ->url(ItemResource::getUrl(parameters: ['filters' => ['no_price' => ['isActive' => true]]])),
        ];
    }
}
