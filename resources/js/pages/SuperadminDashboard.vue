<script setup lang="ts">
import SuperadminLayout from '@/layouts/app/SuperadminLayout.vue'; // Menggunakan layout superadmin
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue'; // Asumsi path ini benar

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Superadmin Dashboard',
        href: '/superadmin/dashboard', // Rute khusus superadmin
    },
];
</script>

<template>
    <Head title="Superadmin Dashboard" />

    <SuperadminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Selamat Datang, Superadmin!</h1>
            <p class="text-gray-600 dark:text-gray-400">Ini adalah area rahasia Anda untuk mengelola seluruh sistem.</p>

            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                    <div class="absolute inset-0 flex items-center justify-center text-gray-500 text-sm">
                        Manajemen Tenant
                    </div>
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                    <div class="absolute inset-0 flex items-center justify-center text-gray-500 text-sm">
                        Laporan Global
                    </div>
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                    <div class="absolute inset-0 flex items-center justify-center text-gray-500 text-sm">
                        Pengaturan Sistem
                    </div>
                </div>
            </div>
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                <PlaceholderPattern />
                <div class="absolute inset-0 flex items-center justify-center text-gray-500 text-xl">
                    Konten Manajemen Superadmin
                </div>
            </div>
        </div>
    </SuperadminLayout>
</template>

