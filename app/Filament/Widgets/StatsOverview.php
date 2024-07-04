<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Product;
use App\Models\Order;
use App\Models\Expense;
use Illuminate\Support\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;
    protected static ?int $sort = 0;
    protected function getStats(): array
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        // Check if startDate filter is provided
        if (!empty($this->filters['startDate'])) {
            $startDate = Carbon::parse($this->filters['startDate']);
        }

        // Check if endDate filter is provided
        if (!empty($this->filters['endDate'])) {
            $endDate = Carbon::parse($this->filters['endDate']);
        }

        $product_count = Product::count();
        $order_count = Order::whereBetween('created_at', [$startDate, $endDate])->count();
        $omset = Order::whereBetween('created_at', [$startDate, $endDate])->sum('total_price');


        $expense = Expense::whereBetween('date_expense', [$startDate, $endDate])->sum('amount');
        $lababersih = $omset - $expense;
        return [
            Stat::make('Total Produk', $product_count),
            Stat::make('Total Order', $order_count),
            Stat::make('Pengeluaran', 'Rp. ' . number_format($expense, 0, ',', '.')),
            Stat::make('Laba kotor', 'Rp. ' . number_format($omset, 0, ',', '.')),
            Stat::make('Laba bersih', 'Rp. ' . number_format($lababersih, 0, ',', '.'))
        ];
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user->hasAnyRole(['Super Admin', 'Owner']);
    }
}
