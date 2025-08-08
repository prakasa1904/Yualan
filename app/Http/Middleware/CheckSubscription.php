<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Bypass if no user is authenticated or if the user is on the subscription info/payment page
        if (!$user || $request->routeIs('subscription.*')) {
            return $next($request);
        }

        $tenant = $user->tenant;

        // Bypass if user has no tenant
        if (!$tenant) {
            return $next($request);
        }

        $subscriptionExpired = $tenant->subscription_ends_at && Carbon::now()->gt($tenant->subscription_ends_at);

        if (!$tenant->is_subscribed || $subscriptionExpired) {
            // Redirect to subscription info page
            return redirect()->route('subscription.info');
        }

        return $next($request);
    }
}
