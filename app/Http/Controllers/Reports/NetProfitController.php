<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class NetProfitController extends Controller
{
    public function index(Request $request, $tenantSlug)
    {
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');

        $salesQuery = Sale::query()
            ->whereHas('tenant', function ($q) use ($tenantSlug) {
                $q->where('slug', $tenantSlug);
            })
            ->where('status', 'completed')
            ->with(['customer', 'items']) // Eager load 'items' relation
            ->when($start_date, fn($q) => $q->whereDate('created_at', '>=', $start_date))
            ->when($end_date, fn($q) => $q->whereDate('created_at', '<=', $end_date));

        $sales = $salesQuery->get()->map(function ($sale) {
            $total_cogs = $sale->items ? $sale->items->sum('cost_price_at_sale') : 0; // Safe check
            $net_profit = $sale->total_amount - $total_cogs;
            return [
                'invoice_number' => $sale->invoice_number,
                'customer_name' => $sale->customer?->name ?? '-',
                'total_amount' => $sale->total_amount,
                'total_cogs' => $total_cogs,
                'net_profit' => $net_profit,
                'created_at' => $sale->created_at->format('Y-m-d'),
            ];
        });

        $totalRevenue = $sales->sum('total_amount');
        $totalCogs = $sales->sum('total_cogs');
        $grossProfit = $totalRevenue - $totalCogs;
        $netProfit = $sales->sum('net_profit');

        return inertia('Reports/NetProfit', [
            'totalRevenue' => $totalRevenue,
            'totalCogs' => $totalCogs,
            'grossProfit' => $grossProfit,
            'netProfit' => $netProfit,
            'sales' => $sales,
            'filters' => [
                'start_date' => $start_date,
                'end_date' => $end_date,
            ],
            'tenantSlug' => $tenantSlug,
            'tenantName' => optional($salesQuery->first()?->tenant)->name,
        ]);
    }
}
