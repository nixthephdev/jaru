<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(1)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(60),
                        TextInput::make('note')
                            ->label('Short note')
                            ->helperText('Optional, e.g. "Served with rice".')
                            ->maxLength(120),
                        Radio::make('status')
                            ->options(Category::STATUSES)
                            ->default('active')
                            ->required(),
                    ]),
            ]);
    }
}
