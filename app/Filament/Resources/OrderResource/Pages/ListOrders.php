<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    // The OrderStats widget is automatically included on this page because 
    // it is defined in OrderResource::getWidgets().

    public function getTabs(): array
    {
        // Get the base query once for efficient badge counting
        $baseQuery = OrderResource::getEloquentQuery();

        return [
            // All Orders Tab
            'all' => Tab::make()
                ->label('All Orders')
                ->badge($baseQuery->count()),

            // New Orders Tab (Status = 'new')
            'new' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'new'))
                ->badge($baseQuery->clone()->where('status', 'new')->count()) // Use clone for accurate count
                ->icon('heroicon-m-sparkles'),

            // Processing Orders Tab (Status = 'processing')
            'processing' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'processing'))
                ->badge($baseQuery->clone()->where('status', 'processing')->count()) // Use clone for accurate count
                ->icon('heroicon-m-arrow-path'),

            // Shipped Orders Tab (Status = 'shipped')
            'shipped' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'shipped'))
                ->badge($baseQuery->clone()->where('status', 'shipped')->count()) // Use clone for accurate count
                ->icon('heroicon-m-truck'),

            // Delivered Orders Tab (Status = 'delivered')
            'delivered' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'delivered'))
                ->badge($baseQuery->clone()->where('status', 'delivered')->count()) // Use clone for accurate count
                ->icon('heroicon-m-check-badge'),

            // Cancelled Orders Tab (Status = 'cancelled')
            'cancelled' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled'))
                ->badge($baseQuery->clone()->where('status', 'cancelled')->count()) // Use clone for accurate count
                ->icon('heroicon-m-x-circle'),
        ];
    }
}