<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB; // For database queries

class ReportController extends Controller
{
    /**
     * Display the Gross Profit Report.
     */
    public function grossProfitReport(Request $request, string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this tenant\'s reports.');
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        // Calculate Gross Profit
        $salesData = Sale::where('tenant_id', $tenant->id)
            ->where('status', 'completed') // Only consider completed sales for gross profit
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with('saleItems') // Eager load sale items to get product details and cost_price_at_sale
            ->get();

        $totalRevenue = 0;
        $totalCogs = 0; // Cost of Goods Sold

        foreach ($salesData as $sale) {
            $totalRevenue += $sale->total_amount; // Total amount from sale
            foreach ($sale->saleItems as $item) {
                // COGS = quantity * cost_price_at_sale (harga pokok saat penjualan terjadi)
                $totalCogs += ($item->quantity * $item->cost_price_at_sale);
            }
        }

        $grossProfit = $totalRevenue - $totalCogs;

        return Inertia::render('Reports/GrossProfit', [ // Assuming you have this Vue component
            'totalRevenue' => round($totalRevenue, 2),
            'totalCogs' => round($totalCogs, 2),
            'grossProfit' => round($grossProfit, 2),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
        ]);
    }

    /**
     * Display the current Stock Report.
     */
    public function stockReport(string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this tenant\'s reports.');
        }

        // Get all products with their current stock and cost price for the tenant
        $products = Product::where('tenant_id', $tenant->id)
                           ->orderBy('name')
                           ->get(['name', 'sku', 'stock', 'cost_price', 'price', 'unit']); // Include unit for display

        // Calculate total stock value (based on cost price)
        $totalStockValue = $products->sum(function ($product) {
            return $product->stock * $product->cost_price;
        });

        return Inertia::render('Reports/StockReport', [ // Assuming you have this Vue component
            'products' => $products,
            'totalStockValue' => round($totalStockValue, 2),
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
        ]);
    }
}
