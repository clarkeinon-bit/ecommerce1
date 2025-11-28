<?php

// ✅ CRITICAL FIX: Namespace MUST match the file path
namespace App\Filament\Resources\OrderResource\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderStats extends BaseWidget
{
    protected static ?int $sort = 2; // Position it below the default Stats

    protected function getStats(): array
    {
        return [
            Stat::make('New Orders', Order::where('status', 'new')->count())
                ->description('Total new orders placed')
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color('info'),

            Stat::make('Processing Orders', Order::where('status', 'processing')->count())
                ->description('Orders currently being prepared')
                ->descriptionIcon('heroicon-o-cog')
                ->color('warning'),

            Stat::make('Delivered Orders', Order::where('status', 'delivered')->count())
                ->description('Orders successfully completed')
                ->descriptionIcon('heroicon-o-truck')
                ->color('success'),
            
            Stat::make('Total Revenue (INR)', number_format(Order::where('payment_status', 'paid')->sum('grand_total'), 2))
                ->description('Total revenue from paid orders')
                ->descriptionIcon('heroicon-o-currency-rupee')
                ->color('primary'),
        ];
    }
}