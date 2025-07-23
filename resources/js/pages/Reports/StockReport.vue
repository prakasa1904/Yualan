<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency } from '@/utils/formatters'; // Import the formatter
import { Package, AlertTriangle, CheckCircle } from 'lucide-vue-next'; // Icons for stock status

interface Product {
    name: string;
    sku: string | null;
    stock: number;
    cost_price: number;
    price: number;
    unit: string | null;
}

const props = defineProps<{
    products: Product[];
    totalStockValue: number;
    tenantSlug: string;
    tenantName: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('tenant.dashboard', { tenantSlug: props.tenantSlug }),
    },
    {
        title: 'Laporan',
        href: '#', // Placeholder for reports main page if any
    },
    {
        title: 'Nilai Stok',
        href: route('reports.stock', { tenantSlug: props.tenantSlug }),
    },
];

// Computed property for sorting (if needed, though controller already sorts by name)
const sortedProducts = computed(() => {
    // Products are already sorted by name from the controller,
    // but you can add client-side sorting here if more dynamic sorting is needed.
    return props.products;
});

// Function to determine stock status color/icon
const getStockStatus = (stock: number) => {
    if (stock <= 0) {
        return { text: 'Stok Habis', class: 'text-red-600 dark:text-red-400', icon: AlertTriangle };
    } else if (stock <= 5) { // Assuming 5 is a low stock threshold
        return { text: 'Stok Rendah', class: 'text-orange-600 dark:text-orange-400', icon: AlertTriangle };
    } else {
        return { text: 'Tersedia', class: 'text-green-600 dark:text-green-400', icon: CheckCircle };
    }
};
</script>

<template>
    <Head title="Laporan Nilai Stok" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Laporan Nilai Stok {{ tenantName ? `(${tenantName})` : '' }}
                </h1>
            </div>

            <!-- Total Stock Value Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700 mb-6">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Total Nilai Stok (Harga Pokok)</h3>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ formatCurrency(totalStockValue) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Ini adalah perkiraan nilai total inventaris Anda berdasarkan harga pokok rata-rata produk.
                </p>
            </div>

            <!-- Product Stock List Table -->
            <div class="rounded-lg border bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[50px]">No.</TableHead>
                            <TableHead>Nama Produk</TableHead>
                            <TableHead>SKU</TableHead>
                            <TableHead>Stok Saat Ini</TableHead>
                            <TableHead>Unit</TableHead>
                            <TableHead>Harga Pokok Rata-rata</TableHead>
                            <TableHead>Harga Jual</TableHead>
                            <TableHead>Nilai Stok Produk</TableHead>
                            <TableHead>Status Stok</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="sortedProducts.length === 0">
                            <TableCell colspan="9" class="text-center text-muted-foreground py-8">
                                Belum ada produk yang tercatat dalam inventaris.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="(product, index) in sortedProducts" :key="product.sku || product.name + index">
                            <TableCell>{{ index + 1 }}</TableCell>
                            <TableCell class="font-medium">{{ product.name }}</TableCell>
                            <TableCell>{{ product.sku || '-' }}</TableCell>
                            <TableCell>{{ product.stock }}</TableCell>
                            <TableCell>{{ product.unit || '-' }}</TableCell>
                            <TableCell>{{ formatCurrency(product.cost_price) }}</TableCell>
                            <TableCell>{{ formatCurrency(product.price) }}</TableCell>
                            <TableCell class="font-semibold">{{ formatCurrency(product.stock * product.cost_price) }}</TableCell>
                            <TableCell>
                                <span :class="['inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold', getStockStatus(product.stock).class]">
                                    <component :is="getStockStatus(product.stock).icon" class="h-3 w-3" />
                                    {{ getStockStatus(product.stock).text }}
                                </span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700 mt-6">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Informasi Laporan Stok</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Laporan ini memberikan gambaran tentang inventaris Anda saat ini, termasuk kuantitas stok, harga pokok, harga jual, dan total nilai stok berdasarkan harga pokok.
                </p>
                <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 mt-4">
                    <li>Stok Saat Ini: Jumlah unit produk yang tersedia di gudang atau toko Anda.</li>
                    <li>Harga Pokok Rata-rata: Biaya rata-rata per unit produk yang dihitung berdasarkan metode rata-rata tertimbang dari penerimaan barang.</li>
                    <li>Nilai Stok Produk: Total biaya produk yang tersedia di stok (Stok Saat Ini x Harga Pokok Rata-rata).</li>
                    <li>Status Stok: Indikator cepat untuk mengidentifikasi produk dengan stok rendah atau habis.</li>
                </ul>
                <p class="text-gray-600 dark:text-gray-400 mt-4">
                    Memantau laporan stok ini secara teratur membantu Anda mengelola inventaris secara efisien, menghindari kehabisan stok, dan mengidentifikasi kelebihan stok.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
