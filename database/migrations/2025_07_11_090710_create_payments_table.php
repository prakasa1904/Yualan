<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('sale_id'); // Foreign key to sales table
            $table->string('payment_method'); // e.g., cash, card, iPaymu
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('IDR');
            $table->string('status')->default('pending')->comment('pending, completed, failed, refunded');
            $table->string('transaction_id')->nullable()->unique(); // Transaction ID from payment gateway (e.g., iPaymu)
            $table->jsonb('gateway_response')->nullable(); // Store raw response from iPaymu for debugging/details
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
