<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category; // The Eloquent Model
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
// Imports for Form Components
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
// Imports for Form Logic
use Illuminate\Support\Str;
use Filament\Forms\Set;
// Imports for Table Actions/Columns
use Filament\Tables\Actions\ActionGroup; 
use Filament\Tables\Actions\ViewAction; 
use Filament\Tables\Actions\EditAction; 
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\ImageColumn; // Added explicit ImageColumn import
use Filament\Tables\Columns\IconColumn; // Added explicit IconColumn import


class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make([
                    Grid::make(2) // Ensured grid has columns(2) here
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true) 
                                ->afterStateUpdated(function ($state, Set $set, $operation) {
                                    if ($operation === 'create' || empty($set->get('slug'))) {
                                        $set('slug', Str::slug($state));
                                    }
                                }),

                            TextInput::make('slug')
                                ->maxLength(255)
                                ->required()
                                ->unique(Category::class, 'slug', ignoreRecord: true)
                                ->disabled() 
                                ->dehydrated(true), 
                        ]),
                    
                    FileUpload::make('image')
                        ->image()
                        ->directory('categories')
                        ->disk('public')
                        ->columnSpanFull(), 

                    Toggle::make('is_active')
                        ->required()
                        ->default(true)
                        ->columnSpanFull(),
                ])->columns(2), // Set column count on the Section itself
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(), // Added sortable

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->sortable(), // Added sortable

                // 🟢 FIX: Cleaned up ImageColumn to match BrandResource
                ImageColumn::make('image')
                    ->disk('public') // Tells the column where to look
                    ->circular(), // Optional formatting
                // END FIX

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(), // Added sortable

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}