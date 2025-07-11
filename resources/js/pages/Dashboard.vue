<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import { computed } from 'vue';

// Mengambil props dari Inertia
const page = usePage();
const tenantSlug = computed(() => page.props.tenantSlug as string | undefined);
const tenantName = computed(() => page.props.tenantName as string | undefined); // Jika Anda ingin menampilkan nama tenant

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        // Pastikan href menggunakan slug tenant
        href: tenantSlug.value ? route('tenant.dashboard', { tenantSlug: tenantSlug.value }) : route('dashboard.default'),
    },
];
</script>

<template>
    <Head :title="tenantName ? `Dashboard - ${tenantName}` : 'Dashboard'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <h1 class="text-2xl font-bold">
                Selamat Datang di Dashboard {{ tenantName ? `Tenant ${tenantName}` : '' }}!
            </h1>
            <p class="text-muted-foreground">
                Anda sedang melihat dashboard untuk tenant dengan slug: <span class="font-semibold">{{ tenantSlug || 'N/A' }}</span>
            </p>
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
            </div>
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                <PlaceholderPattern />
            </div>
        </div>
    </AppLayout>
</template>

