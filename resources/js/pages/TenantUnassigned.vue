<script setup lang="ts">
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3'; // Import Link and usePage
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue'; // Import computed

// Mengakses props dari Inertia, termasuk informasi autentikasi pengguna
const page = usePage();
const user = computed(() => page.props.auth.user);

// Computed property untuk menentukan URL yang benar untuk tombol "Login"
const loginOrDashboardLink = computed(() => {
    if (user.value) {
        // Jika user sudah login
        if (user.value.tenant_id && user.value.tenant && user.value.tenant.slug) {
            // Jika user punya tenant_id dan slug tenant, arahkan ke dashboard tenant
            return route('tenant.dashboard', { tenantSlug: user.value.tenant.slug });
        } else if (user.value.role === 'superadmin') {
            // Jika user adalah superadmin, arahkan ke dashboard superadmin
            return route('superadmin.dashboard');
        }
    }
    // Jika user belum login, atau login tapi belum terhubung ke tenant (dan bukan superadmin), arahkan ke halaman login
    return route('login');
});

// Form data to link user to a tenant
const form = useForm({
    invitation_code: '',
    email: '', // User's email to verify identity
});

// Function to handle form submission
const submit = () => {
    form.post(route('tenant.link'), { // We will create this new route in Laravel
        onSuccess: () => {
            alert('Permintaan penggabungan tenant berhasil dikirim! Silakan login kembali.');
            form.reset(); // Clear the form
            window.location.href = route('login'); // Redirect to login page
        },
        onError: () => {
            // Errors will be displayed by InputError component
        },
        onFinish: () => {
            // Any final actions after success or error
        }
    });
};
</script>

<template>
    <AuthBase title="Belum Terhubung ke Tenant" description="Anda belum memiliki akses ke tenant mana pun.">
        <Head title="Tenant Tidak Terhubung" />

        <div class="flex flex-col items-center justify-center text-center gap-4 p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                Anda Belum Masuk ke Tenant Mana Pun
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Silakan hubungi pemilik tenant Anda untuk mendapatkan kode undangan. Setelah itu, masukkan kode tersebut di bawah ini bersama dengan email Anda untuk mendapatkan akses.
            </p>

            <form @submit.prevent="submit" class="w-full max-w-sm mt-4 space-y-4">
                <div class="grid gap-2">
                    <Label for="invitation_code">Kode Undangan Tenant</Label>
                    <Input
                        id="invitation_code"
                        type="text"
                        placeholder="Masukkan kode undangan tenant Anda"
                        v-model="form.invitation_code"
                        required
                        autofocus
                    />
                    <InputError :message="form.errors.invitation_code" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Alamat Email Anda</Label>
                    <Input
                        id="email"
                        type="email"
                        placeholder="Masukkan email yang terdaftar"
                        v-model="form.email"
                        required
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <Button type="submit" class="w-full mt-4" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                    Kirim Kode Tenant
                </Button>
            </form>

            <div class="text-sm text-muted-foreground mt-4">
                Sudah terhubung ke tenant?
                <Link :href="loginOrDashboardLink" class="underline underline-offset-4">
                    {{ user ? 'Kembali ke Dashboard' : 'Login' }}
                </Link>
            </div>
        </div>
    </AuthBase>
</template>

