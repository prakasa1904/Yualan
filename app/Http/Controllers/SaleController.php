<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Tenant;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;

class SaleController extends Controller
{
    /**
     * Display the sales order page.
     */
    public function index(string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Pastikan user yang login memiliki akses ke tenant ini
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        // Ambil data produk, kategori, dan pelanggan untuk tenant ini
        $products = Product::where('tenant_id', $tenant->id)->with('category')->get();
        $categories = Category::where('tenant_id', $tenant->id)->get();
        $customers = Customer::where('tenant_id', $tenant->id)->get();

        return Inertia::render('Cashier/Order', [
            'products' => $products,
            'categories' => $categories,
            'customers' => $customers,
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
            'ipaymuConfigured' => false, // Sesuaikan dengan konfigurasi iPaymu Anda
        ]);
    }

    /**
     * Store a new sale.
     */
    public function store(Request $request, string $tenantSlug): RedirectResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Pastikan user yang login memiliki akses ke tenant ini
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'string', 'exists:customers,id'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'payment_method' => ['required', 'string', 'in:cash,ipaymu'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Hitung total penjualan berdasarkan item yang dikirim
        $subtotal = 0;
        $saleItemsData = [];
        foreach ($request->items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $itemSubtotal = $product->price * $item['quantity'];
            $subtotal += $itemSubtotal;

            // Pastikan stok mencukupi
            if ($product->stock < $item['quantity']) {
                return back()->withErrors(['items' => 'Stok ' . $product->name . ' tidak mencukupi.']);
            }

            $saleItemsData[] = [
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $product->price, // Harga saat penjualan
                'subtotal' => $itemSubtotal,
            ];
        }

        $discountAmount = $request->discount_amount;
        $taxAmount = ($subtotal - $discountAmount) * ($request->tax_rate / 100);
        $totalAmount = $subtotal - $discountAmount + $taxAmount;

        // Validasi paid_amount untuk pembayaran tunai
        if ($request->payment_method === 'cash' && $request->paid_amount < $totalAmount) {
            return back()->withErrors(['paid_amount' => 'Jumlah yang dibayar kurang dari total penjualan.']);
        }

        $changeAmount = ($request->payment_method === 'cash') ? ($request->paid_amount - $totalAmount) : 0;

        // Buat nomor invoice unik (contoh sederhana)
        $invoiceNumber = 'INV-' . date('YmdHis') . '-' . rand(1000, 9999);

        // Buat record penjualan
        $sale = Sale::create([
            'tenant_id' => $tenant->id,
            'user_id' => Auth::id(), // Kasir yang melakukan penjualan
            'customer_id' => $request->customer_id,
            'invoice_number' => $invoiceNumber,
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'paid_amount' => $request->paid_amount,
            'change_amount' => $changeAmount,
            'payment_method' => $request->payment_method,
            'status' => 'completed', // Atau 'pending' jika ada proses pembayaran eksternal
            'notes' => $request->notes,
        ]);

        // Simpan item penjualan dan kurangi stok
        foreach ($saleItemsData as $itemData) {
            $sale->sale_items()->create($itemData);
            // Kurangi stok produk
            $product = Product::find($itemData['product_id']);
            $product->stock -= $itemData['quantity'];
            $product->save();
        }

        return redirect()->route('sales.receipt', ['tenantSlug' => $tenantSlug, 'sale' => $sale->id])
            ->with('success', 'Penjualan berhasil diproses!');
    }


    /**
     * Display the sales receipt page.
     */
    public function receipt(string $tenantSlug, Sale $sale): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Pastikan user yang login memiliki akses ke tenant ini
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        // Pastikan penjualan ini milik tenant yang benar
        if ($sale->tenant_id !== $tenant->id) {
            abort(404); // Not found if sale doesn't belong to this tenant
        }

        // Load relasi yang diperlukan untuk resi
        $sale->load(['sale_items.product', 'customer', 'user']);

        return Inertia::render('Cashier/Receipt', [
            'sale' => $sale,
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
        ]);
    }

    /**
     * Generate PDF receipt for a specific sale.
     */
    public function generateReceiptPdf(string $tenantSlug, Sale $sale)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Pastikan user yang login memiliki akses ke tenant ini
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        // Pastikan penjualan ini milik tenant yang benar
        if ($sale->tenant_id !== $tenant->id) {
            abort(404); // Not found if sale doesn't belong to this tenant
        }

        // Load relasi yang diperlukan untuk PDF
        $sale->load(['sale_items.product', 'customer', 'user']);

        // Format tanggal untuk Blade view
        $formattedDate = (new \DateTime($sale->created_at))->format('d F Y H:i');

        // Render the Blade view to PDF
        $pdf = Pdf::loadView('pdf.receipt', [
            'sale' => $sale,
            'tenantName' => $tenant->name,
            'formattedDate' => $formattedDate,
        ]);

        // Return the PDF as a download
        return $pdf->download('resi-penjualan-' . $sale->invoice_number . '.pdf');
    }

    /**
     * Display the sales history page for the current tenant.
     */
    public function history(string $tenantSlug, Request $request): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Pastikan user yang login memiliki akses ke tenant ini
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        // Default sorting and pagination
        $sortBy = $request->input('sortBy', 'created_at'); // Default sort by creation date
        $sortDirection = $request->input('sortDirection', 'desc'); // Default sort descending
        $perPage = $request->input('perPage', 10); // Default items per page
        $search = $request->input('search'); // Search term for invoice number or customer name

        $salesQuery = Sale::where('tenant_id', $tenant->id)
            ->with(['customer', 'user']); // Eager load customer and user

        // Apply search filter
        if ($search) {
            $salesQuery->where(function ($query) use ($search) {
                $query->where('invoice_number', 'ILIKE', '%' . $search . '%')
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'ILIKE', '%' . $search . '%');
                    })
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'ILIKE', '%' . $search . '%');
                    });
            });
        }

        // Apply sorting
        $salesQuery->orderBy($sortBy, $sortDirection);

        // Get paginated results
        $sales = $salesQuery->paginate($perPage)->withQueryString();

        return Inertia::render('Cashier/SalesHistory', [
            'sales' => $sales,
            'filters' => [
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
                'perPage' => (int)$perPage,
                'search' => $search,
            ],
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
        ]);
    }
}
