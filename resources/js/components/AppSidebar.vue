<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
// Pastikan semua ikon yang digunakan diimpor, termasuk History
import { BookOpen, Folder, LayoutGrid, Tag, Package, Users, ShoppingBag, History } from 'lucide-vue-next';
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
            // Gunakan fungsi route() dengan parameter slug
            href: tenantSlug.value ? route('tenant.dashboard', { tenantSlug: tenantSlug.value }) : route('dashboard.default'),
            icon: LayoutGrid,
        },
    ];

    // Hanya tambahkan tautan Kategori, Produk, dan Pelanggan jika tenantSlug tersedia dan user bukan superadmin
    if (tenantSlug.value && userRole.value !== 'superadmin') {
        items.push({
            title: 'Pemesanan', // Ordering link
            href: route('sales.order', { tenantSlug: tenantSlug.value }),
            icon: ShoppingBag,
        });
        items.push({
            title: 'Riwayat Penjualan', // New Sales History link
            href: route('sales.history', { tenantSlug: tenantSlug.value }),
            icon: History, // Ikon History
        });
        items.push({
            title: 'Kategori',
            href: route('categories.index', { tenantSlug: tenantSlug.value }),
            icon: Tag,
        });
        items.push({
            title: 'Produk',
            href: route('products.index', { tenantSlug: tenantSlug.value }),
            icon: Package,
        });
        items.push({
            title: 'Pelanggan',
            href: route('customers.index', { tenantSlug: tenantSlug.value }),
            icon: Users,
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
        href: 'https://yualan.com/documentation',
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
                        <!-- Pastikan link ke dashboard juga menggunakan slug -->
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
