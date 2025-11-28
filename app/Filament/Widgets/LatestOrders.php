<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;

class LatestOrders extends BaseWidget
{
    // Define the widget to span the full width of the dashboard
    protected int | string | array $columnSpan = 'full';

    // 🌟 CORRECTION: Use the dedicated method to set the heading, which avoids
    // the static/non-static property inheritance conflict.
    protected function getTableHeading(): ?string
    {
        return 'Latest Orders';
    }

    protected function getTableQuery(): Builder
    {
        // Get the base query from the OrderResource
        return OrderResource::getEloquentQuery()
            ->latest()
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('id')
                ->label('Order ID')
                ->searchable(),
            
            TextColumn::make('user.name')
                ->label('Customer')
                ->searchable(),
            
            TextColumn::make('grand_total')
                ->money('INR')
                ->sortable(),

            TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'new' => 'info',
                    'processing' => 'warning',
                    'shipped' => 'success',
                    'delivered' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),

            TextColumn::make('payment_method')
                ->searchable()
                ->sortable(),
            
            TextColumn::make('created_at')
                ->label('Order Date')
                ->dateTime()
                ->sortable(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('view')
                // Direct link to the Order Resource's 'edit' page
                ->url(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record]))
                ->icon('heroicon-m-eye'),
        ];
    }
}