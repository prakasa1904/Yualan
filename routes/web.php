<?php

use App\Http\Controllers\TenantLinkController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {

    /**
     * START
     * ############### AUTH AREA ###############
     */
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::post('/tenant/link', [TenantLinkController::class, 'link'])
        ->name('tenant.link');
    /**
     * END
     * ############### AUTH AREA ###############
     */


    /**
     * START
     * ############### DASHBOARD REDIRECTION LOGIC ###############
     * This route is crucial for directing users after login/registration.
     */
    Route::get('/dashboard', function () {
        // Eager load the tenant relationship to ensure it's available
        $user = auth()->user()->load('tenant');

        // Prioritize redirect for Superadmin
        if ($user && $user->role === 'superadmin') {
            return redirect()->route('superadmin.dashboard');
        }
        // Redirect for regular tenant users
        if ($user && $user->tenant_id && ($tenant = $user->tenant) && $tenant->is_active) {
            return redirect()->route('tenant.dashboard', ['tenantSlug' => $tenant->slug]);
        }
        // Fallback for users not assigned to any active tenant
        return Inertia::render('TenantUnassigned');
    })->name('dashboard.default');
    /**
     * END
     * ############### DASHBOARD REDIRECTION LOGIC ###############
     */


    /**
     * START
     * ############### SUPERADMIN AREA ###############
     * Moved above USER AREA to prevent route conflicts.
     */

    // Rute khusus untuk Superadmin Dashboard
    Route::get('/superadmin/dashboard', function () {
        return Inertia::render('SuperadminDashboard');
    })->middleware(['superadmin.access'])->name('superadmin.dashboard');

    /**
     * END
     * ############### SUPERADMIN AREA ###############
     */


    /**
     * START
     * ############### USER AREA ###############
     */

    // Rute baru untuk user yang belum terhubung ke tenant
    Route::get('/tenant-unassigned', function () {
        return Inertia::render('TenantUnassigned');
    })->name('tenant.unassigned');

    Route::get('/{tenantSlug}/dashboard', function ($tenantSlug) {
        // Anda bisa mendapatkan objek tenant di sini jika perlu data lain
        $tenant = \App\Models\Tenant::where('slug', $tenantSlug)->firstOrFail();
        return Inertia::render('Dashboard', [
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name, // Kirim nama tenant juga
        ]);
    })->middleware('tenant.access')->name('tenant.dashboard');

    // Contoh rute lain yang memerlukan otorisasi tenant
    // Route::get('/{tenantSlug}/products', [ProductController::class, 'index'])->middleware('tenant.access')->name('tenant.products');

    /**
     * END
     * ############### USER AREA ###############
     */
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
