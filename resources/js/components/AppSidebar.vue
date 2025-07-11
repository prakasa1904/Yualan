<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3'; // Import usePage
import { BookOpen, Box, Folder, LayoutGrid } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed } from 'vue'; // Import computed

const page = usePage();
const tenantSlug = computed(() => page.props.tenantSlug as string | undefined);

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        // Gunakan fungsi route() dengan parameter slug
        href: tenantSlug.value ? route('tenant.dashboard', { tenantSlug: tenantSlug.value }) : route('dashboard.default'),
        icon: LayoutGrid,
    },
    {
        title: 'Category',
        href: tenantSlug.value ? route('categories.index', { tenantSlug: tenantSlug.value }) : '#',
        icon: Box,
    },
    // Tambahkan item navigasi lain yang mungkin memerlukan slug
    // {
    //     title: 'Produk',
    //     href: tenantSlug.value ? route('tenant.products', { tenantSlug: tenantSlug.value }) : '#',
    //     icon: Box,
    // },
];

const footerNavItems: NavItem[] = [
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

