<script setup lang="ts">
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue'; // Import ref for reactive state
import { ChevronDown, ChevronUp } from 'lucide-vue-next'; // Import icons for expand/collapse

interface Props {
    items: NavItem[]; // Now 'items' is required and expected to be passed
}

const props = defineProps<Props>();

// Reactive object to keep track of which submenus are open
// Key is the item.title, value is a boolean (true for open, false for closed)
const openSubmenus = ref<Record<string, boolean>>({});

// Function to toggle the open/closed state of a submenu
const toggleSubmenu = (itemTitle: string) => {
    openSubmenus.value[itemTitle] = !openSubmenus.value[itemTitle];
};
</script>

<template>
    <SidebarMenu>
        <template v-for="item in props.items" :key="item.title">
            <SidebarMenuItem>
                <!-- If item has children, render a toggle button -->
                <template v-if="item.children && item.children.length > 0">
                    <SidebarMenuButton @click="toggleSubmenu(item.title)">
                        <component :is="item.icon" class="h-4 w-4" />
                        {{ item.title }}
                        <component :is="openSubmenus[item.title] ? ChevronUp : ChevronDown" class="ml-auto h-4 w-4 transition-transform duration-200" />
                    </SidebarMenuButton>
                    <!-- Submenu items, conditionally rendered -->
                    <div v-if="openSubmenus[item.title]" class="ml-4 mt-1 space-y-1">
                        <SidebarMenuItem v-for="child in item.children" :key="child.title">
                            <SidebarMenuButton as-child>
                                <Link :href="child.href">
                                    <component :is="child.icon" v-if="child.icon" class="h-4 w-4" />
                                    {{ child.title }}
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </div>
                </template>
                <!-- If item has no children, render a direct link -->
                <template v-else>
                    <SidebarMenuButton as-child>
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </SidebarMenuButton>
                </template>
            </SidebarMenuItem>
        </template>
    </SidebarMenu>
</template>
