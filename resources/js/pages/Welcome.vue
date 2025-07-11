<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// Mengakses props dari Inertia, termasuk informasi autentikasi pengguna
const page = usePage();
const user = computed(() => page.props.auth.user);

// Computed property untuk menentukan URL dashboard yang benar
const dashboardLink = computed(() => {
    if (user.value) {
        console.log(user.value)
        // Jika user sudah login
        if (user.value.role === 'superadmin') {
            // Jika user punya tenant_id dan slug tenant, arahkan ke dashboard tenant
            return route('superadmin.dashboard');
        } else if (user.value.tenant_id && user.value.tenant && user.value.tenant.slug) {
            // Jika user punya tenant_id dan slug tenant, arahkan ke dashboard tenant
            return route('tenant.dashboard', { tenantSlug: user.value.tenant.slug });
        } else {
            // Jika user login tapi belum terhubung ke tenant, arahkan ke halaman unassigned
            return route('tenant.unassigned');
        }
    }
    // Jika user belum login, arahkan ke halaman login
    return route('login');
});
</script>

<template>
    <Head title="Selamat Datang di Yualan POS">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 p-6 sm:p-8">
        <!-- Header Navigasi -->
        <header class="w-full max-w-7xl absolute top-0 right-0 p-6 sm:p-8">
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="user"
                    :href="dashboardLink"
                    class="px-4 py-2 rounded-md text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition-colors duration-200 shadow-md"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="px-4 py-2 rounded-md text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors duration-200"
                    >
                        Login
                    </Link>
                    <Link
                        :href="route('register')"
                        class="px-4 py-2 rounded-md text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition-colors duration-200 shadow-md"
                    >
                        Daftar
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Konten Utama -->
        <main class="flex flex-col items-center justify-center text-center max-w-2xl px-4 sm:px-6 lg:px-8 py-16">
            <h1 class="text-5xl sm:text-6xl font-extrabold text-gray-900 dark:text-gray-100 leading-tight mb-4">
                Yualan POS
            </h1>
            <p class="text-lg sm:text-xl text-gray-600 dark:text-gray-400 mb-8 max-w-xl">
                Sistem Point of Sale yang intuitif dan efisien untuk mengelola bisnis Anda, di mana pun Anda berada.
            </p>

            <div class="flex flex-col sm:flex-row gap-4">
                <Link
                    :href="route('login')"
                    class="w-full sm:w-auto px-8 py-3 rounded-lg text-lg font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition-colors duration-200 shadow-lg transform hover:scale-105"
                >
                    Mulai Sekarang (Login)
                </Link>
                <Link
                    :href="route('register')"
                    class="w-full sm:w-auto px-8 py-3 rounded-lg text-lg font-semibold border-2 border-indigo-600 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors duration-200 shadow-lg transform hover:scale-105"
                >
                    Buat Akun (Daftar)
                </Link>
            </div>
        </main>

        <!-- Footer (Opsional, bisa ditambahkan jika diperlukan) -->
        <footer class="mt-auto py-6 text-sm text-gray-500 dark:text-gray-400">
            &copy; {{ new Date().getFullYear() }} Yualan POS. All rights reserved.
        </footer>
    </div>
</template>

