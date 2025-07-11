<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    /**
     * Handle an incoming authentication request.
     * @throws ValidationException
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Eager load the tenant relationship for the authenticated user
        $user = Auth::user()->load('tenant'); // Load the tenant relationship here

        // Prioritaskan redirect untuk Superadmin
        if ($user && $user->role === 'superadmin') {
            return redirect()->route('superadmin.dashboard');
        }

        // Redirect ke dashboard tenant yang sesuai setelah login (untuk admin/cashier)
        // Sekarang $user->tenant pasti sudah dimuat jika tenant_id ada
        if ($user && $user->tenant_id) {
            $tenant = $user->tenant;
            if ($tenant && $tenant->is_active) {
                return redirect()->route('tenant.dashboard', ['tenantSlug' => $tenant->slug]);
            }
        }

        // Fallback jika user tidak memiliki tenant_id atau tenant tidak ditemukan/tidak aktif
        // Arahkan ke halaman khusus untuk user yang belum terhubung ke tenant
        return redirect()->route('tenant.unassigned');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
