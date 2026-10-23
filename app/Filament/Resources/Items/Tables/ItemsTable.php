<?php

namespace App\Filament\Resources\Items\Tables;

use App\Models\Activity;
use App\Models\Item;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                ImageColumn::make('photo')
                    ->disk('uploads')
                    ->label('')
                    ->square()
                    ->imageSize(48)
                    ->getStateUsing(fn (Item $i) => $i->photo ? preg_replace('/\.jpg$/', '_sm.jpg', $i->photo) : null),
                TextColumn::make('name')
                    ->weight('semibold')
                    ->description(fn (Item $i) => $i->priceText() ?: 'No price yet')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->badge()
                    ->color('gray'),
                ToggleColumn::make('sold_out')
                    ->label('Sold out')
                    ->onColor('danger'),
                ToggleColumn::make('featured')
                    ->label('★ Popular'),
                ToggleColumn::make('hidden')
                    ->label('Hidden'),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),
                Filter::make('sold_out')
                    ->label('Sold out only')
                    ->query(fn (Builder $q) => $q->where('sold_out', true)),
                Filter::make('no_price')
                    ->label('No price yet')
                    ->query(fn (Builder $q) => $q->where('prices', 'like', '%"cents":null%')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('available')
                        ->label('Mark available')
                        ->icon('heroicon-o-check')
                        ->action(fn (Collection $records) => $records->each->update(['sold_out' => false]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('sold_out')
                        ->label('Mark sold out')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn (Collection $records) => $records->each->update(['sold_out' => true]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No items yet')
            ->paginated([25, 50, 100]);
    }

    /** Header action used by the list page; kept here beside the table it affects. */
    public static function allAvailable(): int
    {
        $n = Item::where('sold_out', true)->update(['sold_out' => false]);
        Activity::log("Marked all available ($n items)");

        return $n;
    }
}
