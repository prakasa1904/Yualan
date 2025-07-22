<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TenantSettingsController extends Controller
{
    /**
     * Display the tenant information settings page.
     */
    public function edit(string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the logged-in user has access to this tenant
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        return Inertia::render('settings/TenantInfo', [
            'tenant' => [
                'name' => $tenant->name,
                'ipaymu_api_key' => $tenant->ipaymu_api_key,
                'ipaymu_secret_key' => $tenant->ipaymu_secret_key,
            ],
            'tenantSlug' => $tenantSlug, // Pass tenantSlug to the frontend
        ]);
    }

    /**
     * Update the tenant's information.
     */
    public function update(Request $request, string $tenantSlug): RedirectResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the logged-in user has access to this tenant
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ipaymu_api_key' => ['nullable', 'string', 'max:255'],
            'ipaymu_secret_key' => ['nullable', 'string', 'max:255'],
        ]);

        $tenant->update($validated);

        return back()->with('status', 'tenant-info-updated');
    }
}
