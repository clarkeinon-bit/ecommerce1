<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
// Forms Imports
use App\Filament\Resources\OrderResource\RelationManagers\AddressRelationManager;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Actions\Action as FormAction; 

// Tables Imports
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
// Logic Imports
use Filament\Forms\Set;
use Filament\Forms\Get;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use Illuminate\Validation\Rules\Unique;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() > 10 ? 'danger' : 'success';
    }

    // Helper function to calculate total from items and shipping
    public static function updateTotals(Set $set, Get $get): void
    {
        $items = $get('items') ?? [];
        $shippingAmount = (float)($get('shipping_amount') ?? 0);
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += (float)($item['total_amount'] ?? 0);
        }

        $grandTotal = $subtotal + $shippingAmount;

        $set('grand_total', number_format($grandTotal, 2, '.', ''));
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Order Information')
                            ->schema([
                                Select::make('user_id')
                                    ->label('Customer')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('payment_method')
                                    ->options([
                                        'stripe' => 'Stripe',
                                        'cod' => 'Cash on Delivery',
                                    ])
                                    ->required(),

                                Select::make('payment_status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'paid' => 'Paid',
                                        'failed' => 'Failed',
                                    ])
                                    ->default('pending')
                                    ->required(),

                                ToggleButtons::make('status')
                                    ->inline()
                                    ->options([
                                        'new' => 'New',
                                        'processing' => 'Processing',
                                        'shipped' => 'Shipped',
                                        'delivered' => 'Delivered',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->colors([
                                        'new' => 'info',
                                        'processing' => 'warning',
                                        'shipped' => 'success',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger',
                                    ])
                                    ->default('new')
                                    ->required(),

                                Select::make('currency')
                                    ->options([
                                        'INR' => 'INR',
                                        'USD' => 'USD',
                                        'EUR' => 'EUR',
                                        'GBP' => 'GBP',
                                    ])
                                    ->default('INR')
                                    ->required(),
                                
                                Select::make('shipping_method')
                                    ->options([
                                        'fedex' => 'FedEx',
                                        'ups' => 'UPS',
                                        'dhl' => 'DHL',
                                        'usps' => 'USPS',
                                    ])
                                    ->nullable(),
                                
                                TextInput::make('shipping_amount')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, Get $get) => static::updateTotals($set, $get)),
                                
                                Textarea::make('notes')
                                    ->columnSpanFull()

                            ])->columns(2),

                        Section::make('Order Items')
                            ->live() 
                            ->afterStateUpdated(fn (Set $set, Get $get) => static::updateTotals($set, $get))
                            ->schema([
                                Repeater::make('items')
                                    ->relationship()
                                    ->dehydrated(true)
                                    ->schema([
                                        Select::make('product_id')
                                            ->relationship('product', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->distinct()
                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                            ->reactive()
                                            ->afterStateUpdated(function (Set $set, ?string $state, Get $get) {
                                                if ($state) {
                                                    $product = Product::find($state);
                                                    $price = $product ? (float)($product->price ?? 0) : 0;
                                                    
                                                    $set('unit_amount', number_format($price, 2, '.', ''));
                                                    
                                                    $quantity = (float)($get('quantity') ?? 1);
                                                    $set('total_amount', number_format($price * $quantity, 2, '.', ''));
                                                }
                                            })
                                            ->columnSpan(4),

                                        TextInput::make('quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                                $unitAmount = (float)($get('unit_amount') ?? 0);
                                                $quantity = is_numeric($state) ? (float)$state : 0; 
                                                $total = $unitAmount * $quantity;
                                                $set('total_amount', number_format($total, 2, '.', ''));
                                            })
                                            ->columnSpan(2),

                                        TextInput::make('unit_amount')
                                            ->numeric()
                                            ->required()
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(3),

                                        TextInput::make('total_amount')
                                            ->numeric()
                                            ->required()
                                            ->dehydrated()
                                            ->disabled()
                                            ->columnSpan(3),

                                    ])->columns(12)
                                    ->defaultItems(1)
                                    ->columnSpanFull()
                                    ->deleteAction(
                                        // Customizing the repeater delete action
                                        fn (FormAction $action) => $action->icon('heroicon-m-trash')->color('danger')
                                    ),

                                Hidden::make('grand_total')
                                    ->default(0)
                                    ->dehydrated(true),
                                
                                Placeholder::make('grand_total_placeholder')
                                    ->content(fn (Get $get) => Number::currency($get('grand_total') ?? 0, $get('currency') ?? 'INR'))
                                    ->columnSpanFull()
                                    ->label('Grand Total (Including Shipping)'),

                            ])
                            ->collapsible(),
                    ])->columnSpanFull() 

            ])->columns(1); 
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('grand_total')
                    ->money('INR')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('payment_status')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),


                TextColumn::make('status')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'info',
                        'processing' => 'warning',
                        'shipped' => 'success',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                
                TextColumn::make('currency')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('shipping_method')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filters can be added here
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
            AddressRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        // ✅ CRITICAL: Using the new resource-scoped path for the widget
        return [
            \App\Filament\Resources\OrderResource\Widgets\OrderStats::class, 
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}