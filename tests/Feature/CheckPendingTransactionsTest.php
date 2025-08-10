<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;

class CheckPendingTransactionsTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $user;
    protected $product;

    public function setUp(): void
    {
        parent::setUp();
        
        // Create test tenant
        $this->tenant = Tenant::factory()->create([
            'ipaymu_api_key' => 'test_api_key',
            'ipaymu_secret_key' => 'test_secret_key',
            'ipaymu_mode' => 'sandbox'
        ]);

        // Create test user
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        // Create test category and product
        $category = Category::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'price' => 10000,
            'stock' => 100
        ]);
    }

    /** @test */
    public function command_runs_successfully_when_no_pending_transactions()
    {
        $this->artisan('yualan:check-pending-transactions')
             ->expectsOutput('Memulai pengecekan transaksi pending...')
             ->expectsOutput('Tidak ada transaksi pending yang perlu dicek.')
             ->assertExitCode(0);
    }

    /** @test */
    public function command_finds_pending_ipaymu_transactions()
    {
        // Create a pending iPaymu sale
        $sale = Sale::create([
            'id' => Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'invoice_number' => 'INV-TEST-001',
            'subtotal_amount' => 10000,
            'total_amount' => 10000,
            'payment_method' => 'ipaymu',
            'status' => 'pending',
            'paid_amount' => 10000,
            'change_amount' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0
        ]);

        // Create payment record with transaction_id
        Payment::create([
            'id' => Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'sale_id' => $sale->id,
            'payment_method' => 'ipaymu',
            'amount' => 10000,
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_id' => 'TEST_TRX_123',
            'gateway_response' => ['test' => 'data'],
            'notes' => 'Test payment'
        ]);

        $this->artisan('yualan:check-pending-transactions')
             ->expectsOutput('Memulai pengecekan transaksi pending...')
             ->expectsOutputToContain('Ditemukan 1 transaksi pending untuk dicek.')
             ->expectsOutputToContain('Mengecek transaksi: INV-TEST-001');
    }

    /** @test */
    public function command_skips_cash_transactions()
    {
        // Create a pending cash sale (should be ignored)
        Sale::create([
            'id' => Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'invoice_number' => 'INV-CASH-001',
            'subtotal_amount' => 10000,
            'total_amount' => 10000,
            'payment_method' => 'cash', // This should be ignored
            'status' => 'pending',
            'paid_amount' => 10000,
            'change_amount' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0
        ]);

        $this->artisan('yualan:check-pending-transactions')
             ->expectsOutput('Memulai pengecekan transaksi pending...')
             ->expectsOutput('Tidak ada transaksi pending yang perlu dicek.')
             ->assertExitCode(0);
    }

    /** @test */
    public function command_respects_hours_parameter()
    {
        // Create an old pending transaction (25 hours ago)
        $oldSale = Sale::create([
            'id' => Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'invoice_number' => 'INV-OLD-001',
            'subtotal_amount' => 10000,
            'total_amount' => 10000,
            'payment_method' => 'ipaymu',
            'status' => 'pending',
            'paid_amount' => 10000,
            'change_amount' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'created_at' => now()->subHours(25), // 25 hours ago
        ]);

        // Create payment record
        Payment::create([
            'id' => Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'sale_id' => $oldSale->id,
            'payment_method' => 'ipaymu',
            'amount' => 10000,
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_id' => 'OLD_TRX_123',
        ]);

        // Command with default 24 hours should not find it
        $this->artisan('yualan:check-pending-transactions')
             ->expectsOutput('Tidak ada transaksi pending yang perlu dicek.');

        // Command with 48 hours should find it
        $this->artisan('yualan:check-pending-transactions --hours=48')
             ->expectsOutputToContain('Ditemukan 1 transaksi pending untuk dicek.');
    }

    /** @test */
    public function command_respects_limit_parameter()
    {
        // Create multiple pending sales
        for ($i = 1; $i <= 5; $i++) {
            $sale = Sale::create([
                'id' => Str::uuid(),
                'tenant_id' => $this->tenant->id,
                'user_id' => $this->user->id,
                'invoice_number' => "INV-TEST-00{$i}",
                'subtotal_amount' => 10000,
                'total_amount' => 10000,
                'payment_method' => 'ipaymu',
                'status' => 'pending',
                'paid_amount' => 10000,
                'change_amount' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0
            ]);

            Payment::create([
                'id' => Str::uuid(),
                'tenant_id' => $this->tenant->id,
                'sale_id' => $sale->id,
                'payment_method' => 'ipaymu',
                'amount' => 10000,
                'currency' => 'IDR',
                'status' => 'pending',
                'transaction_id' => "TEST_TRX_{$i}",
            ]);
        }

        // With limit=3, should only process 3 transactions
        $this->artisan('yualan:check-pending-transactions --limit=3')
             ->expectsOutputToContain('Ditemukan 3 transaksi pending untuk dicek.');
    }

    /** @test */
    public function command_handles_missing_transaction_id()
    {
        // Create a pending sale without payment record
        Sale::create([
            'id' => Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'invoice_number' => 'INV-NO-TRX-001',
            'subtotal_amount' => 10000,
            'total_amount' => 10000,
            'payment_method' => 'ipaymu',
            'status' => 'pending',
            'paid_amount' => 10000,
            'change_amount' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0
        ]);

        $this->artisan('yualan:check-pending-transactions')
             ->expectsOutputToContain('Tidak ada transaction_id iPaymu untuk sale INV-NO-TRX-001');
    }
}
