<?php

namespace App\Http\Controllers;

use App\Models\PricingPlan;
use App\Models\Tenant;
use App\Services\IpaymuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\Payment;

class SubscriptionController extends Controller
{
    protected $ipaymuService;

    public function __construct(IpaymuService $ipaymuService)
    {
        $this->ipaymuService = $ipaymuService;
    }

    public function info()
    {
        $user = Auth::user();
        $role = $user->role; // Assuming user has a 'role' attribute ('admin' or 'cashier')

        return Inertia::render('Subscription/Info', [
            'role' => $role
        ]);
    }

    public function payment()
    {
        $pricingPlans = PricingPlan::all();
        return Inertia::render('Subscription/Payment', [
            'pricingPlans' => $pricingPlans
        ]);
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:pricing_plans,id',
        ]);

        $plan = PricingPlan::find($request->plan_id);
        $tenant = Auth::user()->tenant;

        if (!$tenant) {
            return back()->with('error', 'User is not associated with a tenant.');
        }

        $tenant->load('owner');

        if (!$tenant->owner) {
            return back()->with('error', 'Tenant owner could not be found.');
        }

        $product = [$plan->plan_name];
        $qty = [1];
        $price = [$plan->price];
        $description = [$plan->plan_description];

        $paymentData = $this->ipaymuService->createSubscriptionPayment($tenant, $plan, $product, $qty, $price, $description);

        if ($paymentData && isset($paymentData['Data']['Url'])) {
            return Inertia::location($paymentData['Data']['Url']);
        }

        return back()->with('error', 'Failed to create payment link.');
    }

    public function notify(Request $request)
    {
        Log::info('iPaymu Subscription Notification Received', $request->all());

        $transactionId = $request->input('trx_id');
        $status = $request->input('status');
        $referenceId = $request->input('reference_id');

        if (strtolower($status) !== 'berhasil') {
            Log::warning("iPaymu notification for referenceId {$referenceId} was not successful.", ['status' => $status]);
            return response()->json(['status' => 'ok']);
        }

        // Use regex to handle potential hyphens in UUIDs
        if (!preg_match('/^SUB-([a-f0-9\-]+)-([a-f0-9\-]+)-(\d+)$/', $referenceId, $matches)) {
            Log::error("Invalid referenceId format: {$referenceId}");
            return response()->json(['status' => 'error', 'message' => 'Invalid referenceId format'], 400);
        }

        $tenantId = $matches[1];
        $planId = $matches[2];

        $tenant = Tenant::find($tenantId);
        $plan = PricingPlan::find($planId);

        if (!$tenant || !$plan) {
            Log::error("Tenant or Plan not found for referenceId: {$referenceId}");
            return response()->json(['status' => 'error', 'message' => 'Tenant or Plan not found'], 404);
        }

        if ($tenant->last_transaction_id === $transactionId) {
            Log::info("Duplicate notification for transactionId {$transactionId}. Skipping.");
            return response()->json(['status' => 'ok']);
        }

        try {
            $subscription_ends_at = now()->addMonths($plan->duration_months ?? 1);

            $tenant->update([
                'pricing_plan_id' => $plan->id,
                'is_subscribed' => true,
                'subscription_ends_at' => $subscription_ends_at,
                'last_transaction_id' => $transactionId,
            ]);

            Payment::create([
                'tenant_id' => $tenant->id,
                'amount' => $request->input('amount', $plan->price),
                'status' => 'completed',
                'transaction_id' => $transactionId,
                'payment_method' => $request->input('payment_method', 'ipaymu'),
                'description' => "Subscription to {$plan->plan_name}",
            ]);

            Log::info("Subscription for tenant {$tenant->id} to plan {$plan->id} successfully processed.");

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error("Error processing subscription for referenceId {$referenceId}: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Internal server error'], 500);
        }
    }

    public function success(Request $request)
    {
        if ($request->query('status') === 'berhasil') {
            return redirect()->route('dashboard.default')->with('subscription_success', 'true');
        }

        return redirect()->route('subscription.payment')->with('error', 'Payment failed or was cancelled.');
    }
}
