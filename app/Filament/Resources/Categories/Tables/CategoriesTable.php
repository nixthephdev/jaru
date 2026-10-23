<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')
                    ->weight('semibold')
                    ->description(fn (Category $c) => $c->note ?: null)
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ['active' => 'Shown', 'soon' => 'Coming soon', 'hidden' => 'Hidden'][$state] ?? $state)
                    ->color(fn (string $state) => ['active' => 'success', 'soon' => 'warning', 'hidden' => 'gray'][$state] ?? 'gray'),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items')
                    ->alignEnd(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalDescription('Items in these categories are deleted too. This cannot be undone.'),
                ]),
            ])
            ->emptyStateHeading('No categories yet');
    }
}
