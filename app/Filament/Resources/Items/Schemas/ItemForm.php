<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Support\Photos;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['lg' => 3])
            ->components([
                Section::make('Details')
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(80),
                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->default(fn () => request()->integer('category') ?: null)
                            ->required()
                            ->native(false),
                        Textarea::make('description')
                            ->rows(2)
                            ->maxLength(300),
                        Repeater::make('prices')
                            ->label('Price')
                            ->helperText('One price: leave Size blank. Different sizes: one row per size, e.g. 12oz and 16oz.')
                            ->schema([
                                TextInput::make('label')
                                    ->label('Size')
                                    ->placeholder('e.g. 16oz')
                                    ->maxLength(20),
                                TextInput::make('cents')
                                    ->label('Price')
                                    ->prefix('₱')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(999999)
                                    ->inputMode('decimal')
                                    // Stored as centavos, edited as pesos.
                                    ->formatStateUsing(fn ($state) => $state === null ? null : $state / 100)
                                    ->dehydrateStateUsing(fn ($state) => ($state === null || $state === '') ? null : (int) round((float) $state * 100)),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Add another size')
                            ->reorderable(false),
                    ]),

                Section::make('Photo and status')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        FileUpload::make('photo')
                            ->disk('uploads')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatioOptions(['1:1'])
                            ->maxSize(12 * 1024)
                            ->helperText('JPG, PNG or WEBP. Big phone photos are made smaller automatically.')
                            // Re-encode to the 1200px + 480px copies the website uses; the model deletes replaced files.
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file) {
                                try {
                                    return Photos::save($file->getRealPath());
                                } catch (RuntimeException $e) {
                                    throw ValidationException::withMessages(['data.photo' => $e->getMessage()]);
                                }
                            })
                            ->deleteUploadedFileUsing(fn () => null),
                        Toggle::make('featured')
                            ->label('★ Popular')
                            ->helperText('Show in the Popular row at the top.'),
                        Toggle::make('sold_out')
                            ->label('Sold out'),
                        Toggle::make('hidden')
                            ->label('Hide from menu'),
                    ]),
            ]);
    }
}
