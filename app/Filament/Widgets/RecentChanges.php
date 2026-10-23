<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentChanges extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent changes';

    public function table(Table $table): Table
    {
        return $table
            ->query(Activity::query()->latest('id'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->tooltip(fn (Activity $a) => $a->created_at->format('M j, g:i a'))
                    ->width('1%'),
                TextColumn::make('what')
                    ->label('What changed')
                    ->wrap(),
            ])
            ->paginated([10, 25])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Nothing yet');
    }
}
