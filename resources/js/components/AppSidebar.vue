<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
// Pastikan semua ikon yang digunakan diimpor
import { BookOpen, Folder, LayoutGrid, Tag, Package, Users, ShoppingBag, History, Warehouse, BarChart, Truck } from 'lucide-vue-next'; // Tambahkan Warehouse dan BarChart

import AppLogo from './AppLogo.vue';
import { computed } from 'vue';

const page = usePage();
const tenantSlug = computed(() => page.props.tenantSlug as string | undefined);
const userRole = computed(() => page.props.auth.user?.role as string | undefined); // Dapatkan userRole dari props Inertia

// Definisikan item navigasi utama sebagai computed property
const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: tenantSlug.value ? route('tenant.dashboard', { tenantSlug: tenantSlug.value }) : route('dashboard.default'),
            icon: LayoutGrid,
        },
    ];

    // Jika superadmin (tanpa tenantSlug), tambahkan menu Superadmin
    if (!tenantSlug.value && userRole.value === 'superadmin') {
        items.push({
            title: 'Pricing Plans',
            href: route('superadmin.pricing.index'),
            icon: Tag,
        });
        items.push({
            title: 'SaaS Settings',
            href: route('superadmin.settings.index'),
            icon: Tag,
        });
        
    }

    // Hanya tambahkan tautan jika tenantSlug tersedia dan user bukan superadmin
    if (tenantSlug.value && userRole.value !== 'superadmin') {
        items.push({
            title: 'Pemesanan',
            href: route('sales.order', { tenantSlug: tenantSlug.value }),
            icon: ShoppingBag,
        });
        items.push({
            title: 'Riwayat Penjualan',
            href: route('sales.history', { tenantSlug: tenantSlug.value }),
            icon: History,
        });
        items.push({
            title: 'Master Data',
            children: [
                { title: 'Kategori', href: route('categories.index', { tenantSlug: tenantSlug.value }) },
                { title: 'Produk', href: route('products.index', { tenantSlug: tenantSlug.value }) },
                { title: 'Pelanggan', href: route('customers.index', { tenantSlug: tenantSlug.value }) },
                { title: 'Supplier', href: route('suppliers.index', { tenantSlug: tenantSlug.value }) },
            ],
            icon: Folder,
            href: '' // href for parent is optional if children exist
        });
        items.push({
            title: 'Inventaris', // NEW MAIN MENU ITEM
            children: [
                { title: 'Ringkasan Inventaris', href: route('inventory.overview', { tenantSlug: tenantSlug.value }) },
                { title: 'Riwayat Pergerakan', href: route('inventory.movements', { tenantSlug: tenantSlug.value }) },
                { title: 'Penerimaan Barang', href: route('inventory.receive.form', { tenantSlug: tenantSlug.value }) },
                { title: 'Penyesuaian Stok', href: route('inventory.adjust.form', { tenantSlug: tenantSlug.value }) },
                // Optional: Return Goods (uncomment if you add the route and component)
                // { title: 'Pengembalian Barang', href: route('inventory.return.form', { tenantSlug: tenantSlug.value }) },
            ],
            icon: Warehouse,
            href: '' // href for parent is optional if children exist
        });
        items.push({
            title: 'Laporan', // NEW MAIN MENU ITEM
            children: [
                { title: 'Laba Kotor', href: route('reports.grossProfit', { tenantSlug: tenantSlug.value }) },
                { title: 'Nilai Stok', href: route('reports.stock', { tenantSlug: tenantSlug.value }) },
            ],
            icon: BarChart,
            href: '' // href for parent is optional if children exist
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://yualan.or.id',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="tenantSlug ? route('tenant.dashboard', { tenantSlug: tenantSlug }) : route('dashboard.default')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
