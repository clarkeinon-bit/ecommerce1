<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

// Forms Imports
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput; // Ensure this is imported
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Str;
use Filament\Forms\Set;
// Tables Imports
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Products';

    protected static ?string $modelLabel = 'Products';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Product Information')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $state, Set $set) {
                                        $set('slug', Str::slug($state));
                                    }),

                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->unique(Product::class, 'slug', ignoreRecord: true),

                                MarkdownEditor::make('description')
                                    ->columnSpanFull()
                                    ->fileAttachmentsDirectory('products')
                                    ->fileAttachmentsDisk('public'), // Correct Filament 3 property

                            ])->columns(2),

                        Section::make('Images')
                            ->schema([
                                // 🌟 FIX: Use the 'images' field name (typically for multiple/JSON storage) 
                                // and add ->multiple() to allow several images in one field.
                                FileUpload::make('images') 
                                    ->image()
                                    ->multiple() // <-- THIS allows multiple files in one field
                                    ->directory('products')
                                    ->visibility('public')
                                    ->maxFiles(5) // Optional: set a limit
                                    ->imageEditor()
                                    ->columnSpanFull(),

                                // The old single FileUpload::make('image') is now gone.
                            ])->columns(1),
                    ])->columnSpan(2),

                Group::make()
                    ->schema([
                        Section::make('Status')
                            ->schema([
                                Select::make('category_id')
                                    ->label('Category')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(), // ✅ CRITICAL: Required to fix NOT NULL constraint error

                                Select::make('brand_id')
                                    ->label('Brand')
                                    ->relationship('brand', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(), // ✅ CRITICAL: Required to fix NOT NULL constraint error

                                Toggle::make('in_stock')
                                    ->label('In Stock')
                                    ->default(true)
                                    ->required(),

                                Toggle::make('is_active')
                                    ->label('Is Active')
                                    ->default(true)
                                    ->required(),

                                Toggle::make('is_featured')
                                    ->label('Is Featured')
                                    ->default(false)
                                    ->required(),

                                Toggle::make('on_sale')
                                    ->label('On Sale')
                                    ->default(false)
                                    ->required(),
                            ]),

                        Section::make('Pricing & Inventory')
                            ->schema([
                                TextInput::make('price')
                                    ->numeric()
                                    ->required()
                                    ->prefix('INR')
                                    ->minValue(1),

                                TextInput::make('quantity') // ✅ ADDED BACK
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0),
                            ])
                    ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label('ID'),

                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('category.name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('brand.name')
                    ->sortable()
                    ->searchable(),

                // 🌟 FIX APPLIED: Changed from 'image' to 'images' to read the JSON array.
                // ImageColumn is smart enough to use the first image path from the array if one exists.
                ImageColumn::make('images')
                    ->disk('public')
                    ->circular(),

                TextColumn::make('price')
                    ->sortable()
                    ->money('INR'),
                
                TextColumn::make('quantity') // ✅ ADDED BACK to table
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->sortable()
                    ->boolean(),

                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->sortable()
                    ->boolean(),

                TextColumn::make('created_at')
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}