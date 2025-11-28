<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Models\Brand;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables;


class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';

    protected static ?int $navigationSort = 5;

    // ✅ Added navigation badge for consistency
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make([
                    Grid::make(2) // 2 columns for the section
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true) // Update when user tabs out
                                ->afterStateUpdated(function ($state, Set $set, $operation) {
                                    // Generate slug on create, or if slug is empty on edit
                                    $set('slug', Str::slug($state));
                                }), // Simplified slug logic to always update, as slug is disabled/dehydrated

                            TextInput::make('slug')
                                ->maxLength(255)
                                ->required()
                                ->unique(Brand::class, 'slug', ignoreRecord: true)
                                ->disabled()
                                ->dehydrated(true),
                        ]),
                    
                    FileUpload::make('image')
                        ->image()
                        ->directory('brands') // This is correct for FileUpload
                        ->visibility('public') // ✅ Best practice: use visibility instead of disk('public')
                        ->columnSpanFull(),

                    Toggle::make('is_active')
                        ->required()
                        ->default(true)
                        ->label('Visible')
                        ->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),

                ImageColumn::make('image')
                    ->circular(),
                    // ImageColumn automatically looks in storage/app/public/brands if configured in FileUpload

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('updated_at')
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
                ]),
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
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}