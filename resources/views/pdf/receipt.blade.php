<!DOCTYPE html>
<html>
<head>
    <title>Resi Penjualan #{{ $sale->invoice_number }}</title>
    <style>
        /* Global Styles */
        body {
            font-family: 'Inter', sans-serif; /* Fallback to generic sans-serif */
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.6;
            font-size: 11px; /* Slightly larger base font for readability */
            background-color: #f8f8f8; /* Light background for the page */
        }
        .container {
            max-width: 550px; /* Slightly wider for better layout */
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px; /* Slightly more rounded corners */
            box-shadow: 0 4px 12px rgba(0,0,0,0.08); /* Softer, more pronounced shadow */
            padding: 30px; /* More padding inside the container */
            border: 1px solid #e0e0e0; /* Subtle border */
        }

        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            margin-top: 0;
            margin-bottom: 0.5em;
            color: #222;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }
        .font-semibold { font-weight: 600; }
        .font-medium { font-weight: 500; }
        .font-extrabold { font-weight: 800; }

        .text-heading-lg { font-size: 24px; font-weight: 800; color: #1a202c; } /* tenantName */
        .text-heading-md { font-size: 18px; font-weight: 700; color: #1a202c; } /* INVOICE # */
        .text-heading-sm { font-size: 16px; font-weight: 700; color: #1a202c; } /* Detail Pesanan */
        .text-body { font-size: 11px; color: #4a5568; }
        .text-muted { font-size: 10px; color: #718096; }
        .text-highlight { font-size: 20px; font-weight: 700; color: #1a202c; } /* TOTAL */
        .text-success { font-size: 18px; font-weight: 700; color: #38a169; } /* Kembalian */
        .text-danger { color: #e53e3e; }
        .text-warning { color: #d69e2e; }

        /* Layout & Spacing */
        .flex-container { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .flex-item { flex-grow: 1; }
        .flex-item-right { text-align: right; }
        .section-divider {
            border-bottom: 1px dashed #e0e0e0; /* Dashed line for a softer look */
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dotted #f0f0f0; /* Lighter dotted line for items */
        }
        .item-row:last-child {
            border-bottom: none;
        }
        .item-name { flex-grow: 1; margin-right: 10px; }
        .item-qty-price { width: 120px; text-align: right; }
        .item-subtotal { width: 80px; text-align: right; font-weight: 600; }

        /* Summary & Payment Sections */
        .summary-row, .payment-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        .summary-total {
            border-top: 2px solid #e0e0e0; /* Thicker line for total */
            padding-top: 10px;
            margin-top: 10px;
        }

        /* Status Badge */
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
            text-transform: uppercase;
        }
        .status-completed { background-color: #d4edda; color: #155724; } /* Light green */
        .status-pending { background-color: #fff3cd; color: #856404; }   /* Light yellow */
        .status-cancelled, .status-failed { background-color: #f8d7da; color: #721c24; } /* Light red */

        /* Footer */
        .footer-text {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px dashed #e0e0e0;
            font-size: 10px;
            color: #718096;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="text-center mb-6">
        <h2 class="text-heading-lg">{{ $tenantName }}</h2>
        <p class="text-body">Terima kasih atas pesanan Anda!</p>
    </div>

    <div class="flex-container section-divider">
        <div>
            <p class="text-heading-md">INVOICE #{{ $sale->invoice_number }}</p>
            <p class="text-body">Tanggal: {{ $formattedDate }}</p>
            <p class="text-body">Kasir: {{ $sale->user->name }}</p>
            @if($sale->customer)
                <p class="text-body">Pelanggan: {{ $sale->customer->name }}</p>
            @endif
        </div>
        <div class="flex-item-right">
                <span class="status-badge
                    @if($sale->status === 'completed') status-completed
                    @elseif($sale->status === 'pending') status-pending
                    @else status-cancelled @endif">
                    {{ strtoupper($sale->status) }}
                </span>
        </div>
    </div>

    <div class="mb-6">
        <h3 class="text-heading-sm mb-3">Detail Pesanan:</h3>
        <div class="item-row font-semibold text-body" style="border-bottom: 1px solid #e0e0e0;">
            <span class="item-name">Produk</span>
            <span class="item-qty-price" style="text-align: right;">Harga x Qty</span>
            <span class="item-subtotal">Subtotal</span>
        </div>
        @foreach($sale->sale_items as $item)
            <div class="item-row text-body">
                <span class="item-name">{{ $item->product->name }} ({{ $item->product->unit ?? 'pcs' }})</span>
                <span class="item-qty-price">{{ number_format($item->price, 0, ',', '.') }} x {{ $item->quantity }}</span>
                <span class="item-subtotal">{{ number_format($item->subtotal, 0, ',', '.') }}</span>
            </div>
        @endforeach
    </div>

    <div class="section-divider">
        <div class="summary-row text-body">
            <span>Subtotal:</span>
            <span>{{ number_format($sale->subtotal_amount, 0, ',', '.') }}</span>
        </div>
        <div class="summary-row text-body">
            <span>Diskon:</span>
            <span class="text-danger">- {{ number_format($sale->discount_amount, 0, ',', '.') }}</span>
        </div>
        <div class="summary-row text-body">
            <span>Pajak:</span>
            <span>+ {{ number_format($sale->tax_amount, 0, ',', '.') }}</span>
        </div>
        <div class="summary-row summary-total text-highlight">
            <span>TOTAL:</span>
            <span>{{ number_format($sale->total_amount, 0, ',', '.') }}</span>
        </div>
    </div>

    <div>
        <div class="payment-row text-body">
            <span>Metode Pembayaran:</span>
            <span class="font-semibold">{{ strtoupper($sale->payment_method) }}</span>
        </div>
        <div class="payment-row text-body">
            <span>Jumlah Dibayar:</span>
            <span>{{ number_format($sale->paid_amount, 0, ',', '.') }}</span>
        </div>
        <div class="payment-row text-success">
            <span>Kembalian:</span>
            <span>{{ number_format($sale->change_amount, 0, ',', '.') }}</span>
        </div>
    </div>

    @if($sale->notes)
        <p class="text-muted mt-6">
            Catatan: {{ $sale->notes }}
        </p>
    @endif

    <div class="text-center footer-text">
        Terima kasih telah berbelanja di {{ $tenantName }}!
    </div>
</div>
</body>
</html>
