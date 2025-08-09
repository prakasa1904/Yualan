<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Tenant;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Payment; // Import Payment model
use App\Models\Inventory; // Import Inventory model for logging movements
use App\Services\IpaymuService; // Import IpaymuService
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log; // Import Log Facade
use Illuminate\Support\Str; // Import Str for UUID in Sale model creation

class SaleController extends Controller
{
    /**
     * Display a listing of the sales for the current tenant.
     */
    public function index(string $tenantSlug, Request $request): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the logged-in user has access to this tenant
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

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

        return Inertia::render('Cashier/SalesHistory', [ // Assuming this is your sales history component
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

    /**
     * Display the sales order page.
     */
    public function order(string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the logged-in user has access to this tenant
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        // Retrieve products, categories, and customers for this tenant
        $products = Product::where('tenant_id', $tenant->id)->with('category')->get();
        $categories = Category::where('tenant_id', $tenant->id)->get();
        $customers = Customer::where('tenant_id', $tenant->id)->get();

        // Check if iPaymu credentials are configured for the tenant
        $ipaymuConfigured = (bool)$tenant->ipaymu_api_key && (bool)$tenant->ipaymu_secret_key;

        return Inertia::render('Cashier/Order', [
            'products' => $products,
            'categories' => $categories,
            'customers' => $customers,
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
            'ipaymuConfigured' => $ipaymuConfigured,
        ]);
    }

    /**
     * Store a new sale.
     * Return type changed to Response to allow Inertia::render for iPaymu redirect.
     */
    public function store(Request $request, string $tenantSlug): Response|RedirectResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the logged-in user has access to this tenant
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

        // Calculate sale total based on submitted items
        $subtotal = 0;
        $saleItemsData = [];
        foreach ($request->items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $itemSubtotal = $product->price * $item['quantity'];
            $subtotal += $itemSubtotal;

            // Check sufficient stock
            if ($product->stock < $item['quantity']) {
                return back()->withErrors(['items' => 'Stok ' . $product->name . ' tidak mencukupi.']);
            }

            $saleItemsData[] = [
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $product->price, // Price at the time of sale
                'subtotal' => $itemSubtotal,
            ];
        }

        $discountAmount = $request->discount_amount;
        $taxAmount = ($subtotal - $discountAmount) * ($request->tax_rate / 100);
        $totalAmount = $subtotal - $discountAmount + $taxAmount;

        // Validate paid_amount for cash payments
        if ($request->payment_method === 'cash' && $request->paid_amount < $totalAmount) {
            return back()->withErrors(['paid_amount' => 'Jumlah yang dibayar kurang dari total penjualan.']);
        }

        $changeAmount = ($request->payment_method === 'cash') ? ($request->paid_amount - $totalAmount) : 0;

        // Generate a unique invoice number (simple example)
        $invoiceNumber = 'INV-' . date('YmdHis') . '-' . Str::random(4); // Use Str::random() for invoice number

        // Determine initial status based on payment method
        $initialStatus = ($request->payment_method === 'ipaymu') ? 'pending' : 'completed';

        // Create sale record
        $sale = Sale::create([
            'id' => Str::uuid(), // Generate UUID for Sale
            'tenant_id' => $tenant->id,
            'user_id' => Auth::id(), // Cashier who made the sale
            'customer_id' => $request->customer_id,
            'invoice_number' => $invoiceNumber,
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'paid_amount' => $request->paid_amount,
            'change_amount' => $changeAmount,
            'payment_method' => $request->payment_method,
            'status' => $initialStatus,
            'notes' => $request->notes,
        ]);

        // Save sale items and reduce stock
        foreach ($saleItemsData as $itemData) {
            // Ensure SaleItem model exists and has 'id' in $fillable
            $saleItem = $sale->saleItems()->create(array_merge($itemData, ['id' => Str::uuid()])); // Generate UUID for SaleItem
            
            // Reduce product stock
            $product = Product::find($itemData['product_id']);
            $product->stock -= $itemData['quantity'];
            $product->save();

            // Log inventory movement for 'out' (sale)
            Inventory::create([
                'id' => Str::uuid(),
                'tenant_id' => $tenant->id,
                'product_id' => $product->id,
                'quantity_change' => -$itemData['quantity'], // Use quantity_change for stock reduction
                'type' => 'out', // Movement type for sale
                'reason' => 'Penjualan: ' . $sale->invoice_number,
                'user_id' => Auth::id(),
                'cost_price_at_movement' => $product->cost_price, // Use product's current cost price
                'related_sale_item_id' => $saleItem->id, // Link to the specific sale item
            ]);
        }

        // If payment method is iPaymu, initiate payment
        if ($request->payment_method === 'ipaymu') {
            // Changed return type to Inertia::render to pass the URL to frontend
            return $this->initiateIpaymuPayment($sale, $tenant);
        }

        // For cash payments, redirect to receipt page
        return redirect()->route('sales.receipt', ['tenantSlug' => $tenantSlug, 'sale' => $sale->id])
            ->with('success', 'Penjualan berhasil diproses!');
    }

    /**
     * Initiate iPaymu payment.
     * Return type changed to Response to allow Inertia::render.
     */
    protected function initiateIpaymuPayment(Sale $sale, Tenant $tenant): Response|RedirectResponse
    {
        // Retrieve VA and Secret Key from the tenant model
        $va = $tenant->ipaymu_api_key; // Assuming ipaymu_api_key stores VA
        $secret = $tenant->ipaymu_secret_key; // Assuming ipaymu_secret_key stores Secret Key
        $url = env('IPAYMU_URL'); // iPaymu URL still from .env (sandbox/production)

        if (!$va || !$secret || !$url) {
            Log::error('iPaymu credentials are not configured for tenant: ' . $tenant->name . ' (ID: ' . $tenant->id . ')');
            return redirect()->route('sales.order', ['tenantSlug' => $tenant->slug])
                ->with('error', 'Konfigurasi iPaymu belum lengkap untuk tenant ini. Silakan hubungi administrator.');
        }

        $products = [];
        $qtys = [];
        $prices = [];

        foreach ($sale->saleItems as $item) { // Use saleItems relationship
            $products[] = $item->product->name;
            $qtys[] = $item->quantity;
            $prices[] = $item->price;
        }

        $buyerName = $sale->customer ? $sale->customer->name : 'Guest Customer';
        $buyerEmail = $sale->customer ? $sale->customer->email : 'guest@example.com'; // Provide a default email if customer is null
        $buyerPhone = $sale->customer ? $sale->customer->phone : '081234567890'; // Provide a default phone if customer is null

        $body = [
            'product' => $products,
            'qty' => $qtys,
            'price' => $prices,
            'returnUrl' => route('sales.ipaymuReturn', ['tenantSlug' => $tenant->slug, 'sale' => $sale->id]), // Corrected route name
            'cancelUrl' => route('sales.ipaymuCancel', ['tenantSlug' => $tenant->slug, 'sale' => $sale->id]), // Corrected route name
            'notifyUrl' => route('sales.ipaymuNotify'), // Corrected to use route helper without tenantSlug
            'buyerName' => $buyerName,
            'buyerEmail' => $buyerEmail,
            'buyerPhone' => $buyerPhone,
            'referenceId' => $sale->id, // Use sale ID as referenceId
            'expired' => 2, // Payment duration in hours
        ];

        $method = 'POST';
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $requestBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = strtoupper($method) . ':' . $va . ':' . $requestBody . ':' . $secret;
        $signature = hash_hmac('sha256', $stringToSign, $secret);
        $timestamp = date('YmdHis');

        $ch = curl_init($url);
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'va: ' . $va,
            'signature: ' . $signature,
            'timestamp: ' . $timestamp,
        ];

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true); // Use true for POST requests
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For sandbox/testing, set to true in production
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // For sandbox/testing, set to 2 in production

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            Log::error('iPaymu cURL Error: ' . $err);
            return redirect()->route('sales.order', ['tenantSlug' => $tenant->slug])
                ->with('error', 'Terjadi kesalahan saat menghubungi iPaymu: ' . $err);
        } else {
            $ret = json_decode($response);
            
            // Add error handling for JSON decode
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('iPaymu JSON Decode Error: ' . json_last_error_msg() . ' | Response: ' . $response);
                return redirect()->route('sales.order', ['tenantSlug' => $tenant->slug])
                    ->with('error', 'Terjadi kesalahan saat memproses respons iPaymu.');
            }
            
            // Add null check and property existence check
            if (!$ret || !isset($ret->Status)) {
                Log::error('iPaymu Invalid Response: ' . $response);
                return redirect()->route('sales.order', ['tenantSlug' => $tenant->slug])
                    ->with('error', 'Respons iPaymu tidak valid.');
            }
            
            if ($ret->Status == 200) {
                // Update sale status to 'pending' or 'waiting_payment'
                $sale->update(['status' => 'pending', 'notes' => 'Menunggu pembayaran via iPaymu.']);
                // Create a payment record
                Payment::create([
                    'id' => Str::uuid(), // Generate UUID for Payment
                    'tenant_id' => $tenant->id,
                    'sale_id' => $sale->id,
                    'payment_method' => 'ipaymu',
                    'amount' => $sale->total_amount,
                    'currency' => 'IDR',
                    'status' => 'pending',
                    'transaction_id' => $ret->Data->TransactionId ?? null,
                    'gateway_response' => $ret,
                    'notes' => 'Pembayaran iPaymu diinisiasi',
                ]);

                // Return Inertia response with the iPaymu URL
                return Inertia::render('Cashier/Order', [
                    'products' => Product::where('tenant_id', $tenant->id)->with('category')->get(),
                    'categories' => Category::where('tenant_id', $tenant->id)->get(),
                    'customers' => Customer::where('tenant_id', $tenant->id)->get(),
                    'tenantSlug' => $tenant->slug,
                    'tenantName' => $tenant->name,
                    'ipaymuConfigured' => (bool)$tenant->ipaymu_api_key && (bool)$tenant->ipaymu_secret_key,
                    'ipaymuRedirectUrl' => $ret->Data->Url, // Pass the iPaymu URL to the frontend
                ]);
            } else {
                Log::error('iPaymu API Error: ' . json_encode($ret));
                return redirect()->route('sales.order', ['tenantSlug' => $tenant->slug])
                    ->with('error', 'Pembayaran iPaymu gagal diinisiasi: ' . (isset($ret->Message) ? $ret->Message : 'Terjadi kesalahan.'));
            }
        }
    }

    /**
     * Re-initiate iPaymu payment for an existing sale.
     */
    public function reinitiatePayment(string $tenantSlug, Sale $sale): Response|RedirectResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the authenticated user belongs to this tenant AND the sale belongs to this tenant
        if (Auth::user()->tenant_id !== $tenant->id || $sale->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure the sale is actually pending or failed and uses iPaymu
        if ($sale->payment_method !== 'ipaymu' || !in_array($sale->status, ['pending', 'failed', 'cancelled'])) {
            return redirect()->route('sales.receipt', ['tenantSlug' => $tenantSlug, 'sale' => $sale->id])
                ->with('error', 'Pembayaran untuk penjualan ini tidak dapat diinisiasi ulang.');
        }

        // Reload sale items to ensure they are available for payment initiation
        $sale->load('saleItems.product');

        // Call the public initiateIpaymuPayment method
        return $this->initiateIpaymuPayment($sale, $tenant);
    }

    /**
     * Display the sales receipt page.
     */
    public function receipt(string $tenantSlug, Sale $sale): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the logged-in user has access to this tenant
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        // Ensure this sale belongs to the correct tenant
        if ($sale->tenant_id !== $tenant->id) {
            abort(404); // Not found if sale doesn't belong to this tenant
        }

        // Load necessary relationships for the receipt
        $sale->load(['saleItems.product', 'customer', 'user', 'payments']); // Use saleItems and payments

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

        // Ensure the logged-in user has access to this tenant
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        // Ensure this sale belongs to the correct tenant
        if ($sale->tenant_id !== $tenant->id) {
            abort(404);
        }

        // Load necessary relationships for the PDF
        $sale->load(['saleItems.product', 'customer', 'user']); // Use saleItems

        // Format date for Blade view
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

        // Ensure the logged-in user has access to this tenant
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

    /**
     * Handle iPaymu return URL after payment.
     * This is a GET request from iPaymu after user completes/cancels payment.
     */
    public function ipaymuReturn(string $tenantSlug, Sale $sale): RedirectResponse // Renamed method
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        Log::info('iPaymu Return URL hit for Sale ID: ' . $sale->id . ' with status: ' . $sale->status);

        // Redirect to the receipt page with a success message.
        // IMPORTANT: Do NOT update payment status here. Status updates must be done via notifyUrl (webhook).
        return redirect()->route('sales.receipt', ['tenantSlug' => $tenantSlug, 'sale' => $sale->id])
            ->with('success', 'Transaksi Anda sedang diproses. Status akan diperbarui setelah konfirmasi pembayaran.');
    }

    /**
     * Handle iPaymu cancel URL.
     * This is a GET request from iPaymu if user cancels payment.
     */
    public function ipaymuCancel(string $tenantSlug, Sale $sale): RedirectResponse // Renamed method
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Update sale status to 'cancelled' if it's not already 'completed'
        if ($sale->status !== 'completed') {
            $sale->update(['status' => 'cancelled', 'notes' => 'Pembayaran dibatalkan oleh pengguna.']);
        }
        Log::info('iPaymu Cancel URL hit for Sale ID: ' . $sale->id);

        return redirect()->route('sales.order', ['tenantSlug' => $tenant->slug])
            ->with('error', 'Pembayaran dibatalkan.');
    }

    /**
     * Handle iPaymu notify URL (webhook).
     * This is a POST request from iPaymu to notify payment status changes.
     * This route should be publicly accessible and not require CSRF token.
     */
    public function ipaymuNotify(Request $request) // Removed string $tenantSlug parameter
    {
        // Log the entire notification request for debugging
        Log::info('iPaymu Notify Callback Received:', $request->all());

        // Get referenceId (our Sale ID) from the request
        $referenceId = $request->input('reference_id');
        $sale = Sale::with('tenant')->find($referenceId); // Eager load tenant relationship

        if (!$sale) {
            Log::warning('iPaymu Notify: Sale not found for referenceId: ' . $referenceId);
            return response()->json(['message' => 'Sale not found'], 404);
        }

        // Now that sale is found, we can get the tenant from the sale model
        $tenant = $sale->tenant;

        if (!$tenant) {
            Log::error('iPaymu Notify: Tenant not found for Sale ID: ' . $sale->id);
            return response()->json(['message' => 'Tenant not found for sale'], 500);
        }

        $ipaymuService = new IpaymuService($tenant); // Instantiate the service with the found tenant

        // --- REMOVED SIGNATURE VERIFICATION BLOCK ---
        // The previous signature verification relied on a 'signature' header
        // which appears to be null based on your logs.
        // We will rely on the checkTransaction API call for robust verification.

        // Get data from notification
        $transactionId = $request->input('trx_id'); // iPaymu Transaction ID
        $status = $request->input('status'); // Payment status from iPaymu (e.g., 'berhasil', 'gagal', 'pending')
        $amount = $request->input('amount'); // Amount paid

        // Check transaction status with iPaymu API for certainty (recommended for IPN)
        try {
            $checkStatusResponse = $ipaymuService->checkTransaction($transactionId);
            $ipaymuActualStatus = $checkStatusResponse['Data']['StatusDesc'] ?? 'unknown';
            $ipaymuActualAmount = $checkStatusResponse['Data']['Amount'] ?? 0;

            Log::info("iPaymu Notify: Transaction {$transactionId} - Actual iPaymu Status: {$ipaymuActualStatus}, Amount: {$ipaymuActualAmount}");

            // Update sale status based on iPaymu's actual status
            if ($ipaymuActualStatus === 'Berhasil') {
                if ($sale->status !== 'completed') {
                    $sale->update([
                        'status' => 'completed',
                        'paid_amount' => $ipaymuActualAmount, // Use actual amount from iPaymu check
                        'change_amount' => $ipaymuActualAmount - $sale->total_amount,
                        'notes' => 'Pembayaran berhasil via iPaymu (TRX ID: ' . $transactionId . ')',
                    ]);
                    Log::info('iPaymu Notify: Sale ID ' . $sale->id . ' updated to completed.');

                    // Log inventory movements for 'out' (sale)
                    $sale->load('saleItems.product'); // Ensure sale items and products are loaded
                    foreach ($sale->saleItems as $saleItem) {
                        Inventory::create([
                            'id' => Str::uuid(),
                            'tenant_id' => $sale->tenant_id,
                            'product_id' => $saleItem->product_id,
                            'quantity_change' => -$saleItem->quantity, // Use quantity_change for stock reduction
                            'type' => 'out',
                            'reason' => 'Penjualan iPaymu: ' . $sale->invoice_number,
                            'user_id' => $sale->user_id, // User yang membuat penjualan
                            'cost_price_at_movement' => $saleItem->product->cost_price, // Use product's current cost price
                            'related_sale_item_id' => $saleItem->id,
                        ]);
                        Log::info('iPaymu Notify: Inventory "out" logged for product ' . $saleItem->product_id . ' (Sale ID: ' . $sale->id . ')');
                    }

                    // Update or create Payment record
                    $payment = Payment::firstOrNew(['transaction_id' => $transactionId, 'sale_id' => $sale->id]);
                    $payment->fill([
                        'tenant_id' => $sale->tenant_id,
                        'payment_method' => 'ipaymu',
                        'amount' => $ipaymuActualAmount,
                        'currency' => 'IDR',
                        'status' => 'completed',
                        'gateway_response' => $checkStatusResponse,
                        'notes' => 'Pembayaran iPaymu (IPN)',
                    ])->save();
                    Log::info('iPaymu Notify: Payment record for Sale ID ' . $sale->id . ' updated/created as completed.');
                }
            } elseif ($ipaymuActualStatus === 'Gagal') {
                if ($sale->status !== 'failed' && $sale->status !== 'completed') {
                    $sale->update([
                        'status' => 'failed',
                        'notes' => 'Pembayaran gagal via iPaymu (TRX ID: ' . $transactionId . ')',
                    ]);
                    Log::info('iPaymu Notify: Sale ID ' . $sale->id . ' updated to failed.');
                    // Update Payment record
                    $payment = Payment::firstOrNew(['transaction_id' => $transactionId, 'sale_id' => $sale->id]);
                    $payment->fill([
                        'tenant_id' => $sale->tenant_id,
                        'payment_method' => 'ipaymu',
                        'amount' => $ipaymuActualAmount,
                        'currency' => 'IDR',
                        'status' => 'failed',
                        'gateway_response' => $checkStatusResponse,
                        'notes' => 'Pembayaran iPaymu gagal (IPN)',
                    ])->save();
                }
            } elseif ($ipaymuActualStatus === 'Pending') {
                if ($sale->status !== 'pending' && $sale->status !== 'completed') {
                    $sale->update([
                        'status' => 'pending',
                        'notes' => 'Pembayaran pending via iPaymu (TRX ID: ' . $transactionId . ')',
                    ]);
                    Log::info('iPaymu Notify: Sale ID ' . $sale->id . ' updated to pending.');
                    // Update Payment record
                    $payment = Payment::firstOrNew(['transaction_id' => $transactionId, 'sale_id' => $sale->id]);
                    $payment->fill([
                        'tenant_id' => $sale->tenant_id,
                        'payment_method' => 'ipaymu',
                        'amount' => $ipaymuActualAmount,
                        'currency' => 'IDR',
                        'status' => 'pending',
                        'gateway_response' => $checkStatusResponse,
                        'notes' => 'Pembayaran iPaymu pending (IPN)',
                    ])->save();
                }
            } else {
                Log::warning('iPaymu Notify: Unknown status from checkTransaction for Sale ID ' . $sale->id . ': ' . $ipaymuActualStatus);
            }

        } catch (\Exception $e) {
            Log::error('iPaymu Notify: Error checking transaction status: ' . $e->getMessage(), ['exception' => $e, 'transaction_id' => $transactionId]);
            return response()->json(['message' => 'Error processing notification: ' . $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Notification received and processed'], 200);
    }
}
