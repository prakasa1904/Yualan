<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str; // Import Str for random string generation

class TenantSettingsController extends Controller
{
    /**
     * Display the tenant settings information page.
     */
    public function edit(string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the authenticated user belongs to this tenant
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        return Inertia::render('settings/TenantInfo', [
            'tenant' => [
                'name' => $tenant->name,
                'ipaymu_api_key' => $tenant->ipaymu_api_key,
                'ipaymu_secret_key' => $tenant->ipaymu_secret_key,
                'ipaymu_mode' => $tenant->ipaymu_mode,
                'invitation_code' => $tenant->invitation_code, // Pass the invitation code
            ],
            'tenantSlug' => $tenantSlug,
        ]);
    }

    /**
     * Update the tenant settings information.
     */
    public function update(Request $request, string $tenantSlug): RedirectResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the authenticated user belongs to this tenant
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ipaymu_api_key' => ['nullable', 'string', 'max:255'],
            'ipaymu_secret_key' => ['nullable', 'string', 'max:255'],
            'ipaymu_mode' => ['required', Rule::in(['production', 'sandbox'])],
            // Validate uniqueness of invitation_code, ignoring the current tenant's ID
            'invitation_code' => ['nullable', 'string', 'max:255', Rule::unique('tenants')->ignore($tenant->id)],
        ]);

        $tenant->update([
            'name' => $request->name,
            'ipaymu_api_key' => $request->ipaymu_api_key,
            'ipaymu_secret_key' => $request->ipaymu_secret_key,
            'ipaymu_mode' => $request->ipaymu_mode,
            'invitation_code' => $request->invitation_code, // Update the invitation code
        ]);

        return back()->with('success', 'Informasi tenant berhasil diperbarui.');
    }

    /**
     * Generate a new random invitation code for the tenant.
     */
    public function generateInvitationCode(string $tenantSlug): RedirectResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the authenticated user belongs to this tenant
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        // Generate a new random code (e.g., 10 characters alphanumeric)
        $newCode = Str::random(10);

        // Ensure uniqueness (though highly unlikely for 10 random chars)
        // Loop until a unique code is found
        while (Tenant::where('invitation_code', $newCode)->exists()) {
            $newCode = Str::random(10);
        }

        $tenant->update(['invitation_code' => $newCode]);

        // Redirect back with a success message and the new code in flash data
        return back()->with('success', 'Kode undangan baru berhasil dibuat.')->with('newInvitationCode', $newCode);
    }
}
