<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, useForm, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch, nextTick } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { LoaderCircle, PlusCircle, Edit, Trash2, ChevronUp, ChevronDown, Search, Image as ImageIcon, XCircle } from 'lucide-vue-next';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { formatCurrency } from '@/utils/formatters'; // Import formatCurrency

// Props from controller
interface Product {
    id: string;
    tenant_id: string;
    category_id: string | null;
    category?: { id: string; name: string }; // Optional category object
    name: string;
    sku: string | null;
    description: string | null;
    price: number;
    cost_price: number; // New field
    stock: number;
    unit: string | null;
    image: string | null;
    is_food_item: boolean; // New field
    ingredients: string | null; // New field
    created_at: string;
    updated_at: string;
}

interface PaginatedProducts {
    data: Product[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Filters {
    sortBy: string;
    sortDirection: 'asc' | 'desc';
    perPage: number;
    search: string | null;
    filterField: string | null;
}

interface Category {
    id: string;
    name: string;
}

const props = defineProps<{
    products: PaginatedProducts;
    filters: Filters;
    categories: Category[]; // Categories for the dropdown
    tenantSlug: string;
    tenantName: string;
}>();

// Inertia page props
const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('tenant.dashboard', { tenantSlug: props.tenantSlug }),
    },
    {
        title: 'Produk',
        href: route('products.index', { tenantSlug: props.tenantSlug }),
    },
];

// Reactive state for filters and sorting
const currentPerPage = ref(props.filters.perPage);
const currentSortBy = ref(props.filters.sortBy);
const currentSortDirection = ref(props.filters.sortDirection);
const currentSearch = ref(props.filters.search || '');
const currentFilterField = ref(props.filters.filterField || 'name'); // Default filter field

// Form state for adding/editing products
const form = useForm({
    id: null as string | null, // Used for editing
    category_id: null as string | null,
    name: '',
    sku: '',
    description: '',
    price: 0,
    cost_price: 0, // New field
    stock: 0,
    unit: '',
    image: null as File | null,
    is_food_item: false as boolean, // New field
    ingredients: '', // New field
    _method: 'post' as 'post' | 'put', // For Inertia form method override
    clear_image: false, // New flag for clearing image
});

// State for dialogs
const isFormDialogOpen = ref(false);
const isConfirmDeleteDialogOpen = ref(false);
const productToDelete = ref<Product | null>(null);
const currentProductImage = ref<string | null>(null); // To display existing image in edit form

// Form title for dialog
const formTitle = computed(() => (form.id ? 'Edit Produk' : 'Tambah Produk Baru'));

// Function to open the add/edit dialog
const openFormDialog = (product: Product | null = null) => {
    form.reset(); // Reset form state
    form.clearErrors(); // Clear any previous errors
    currentProductImage.value = null; // Clear image preview

    if (product) {
        form.id = product.id;
        form.category_id = product.category_id;
        form.name = product.name;
        form.sku = product.sku || '';
        form.description = product.description || '';
        form.price = product.price;
        form.cost_price = product.cost_price; // Set cost_price
        form.stock = product.stock || 0;
        form.unit = product.unit || '';
        form.is_food_item = product.is_food_item; // Set is_food_item
        form.ingredients = product.ingredients || ''; // Set ingredients
        form._method = 'put'; // Change method to PUT for update
        currentProductImage.value = product.image; // Set current image for display
    } else {
        form._method = 'post'; // Default to POST for new creation
    }
    isFormDialogOpen.value = true;
};

// Handle image file selection
const handleImageChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.image = target.files[0];
        form.clear_image = false; // If new image is selected, don't clear old one
    } else {
        form.image = null;
    }
};

// Function to clear the image
const clearImage = () => {
    form.image = null;
    currentProductImage.value = null; // Clear displayed image
    form.clear_image = true; // Set flag to clear image on backend
    // If there's a file input, reset it
    const fileInput = document.getElementById('image') as HTMLInputElement;
    if (fileInput) {
        fileInput.value = '';
    }
};

// Function to handle form submission (create or update)
const submitForm = () => {
    if (form.id) {
        // Update existing product
        form.post(route('products.update', { tenantSlug: props.tenantSlug, product: form.id }), {
            onSuccess: () => {
                isFormDialogOpen.value = false;
                form.reset();
                currentProductImage.value = null; // Clear image preview
            },
            onError: (errors) => {
                console.error("Form errors:", errors);
            },
        });
    } else {
        // Create new product
        form.post(route('products.store', { tenantSlug: props.tenantSlug }), {
            onSuccess: () => {
                isFormDialogOpen.value = false;
                form.reset();
                currentProductImage.value = null; // Clear image preview
            },
            onError: (errors) => {
                console.error("Form errors:", errors);
            },
        });
    }
};

// Function to open delete confirmation dialog
const openConfirmDeleteDialog = (product: Product) => {
    productToDelete.value = product;
    isConfirmDeleteDialogOpen.value = true;
};

// Function to handle product deletion
const deleteProduct = () => {
    if (!productToDelete.value) {
        alert('Produk tidak ditemukan. Tidak dapat menghapus.');
        return;
    }
    form.delete(route('products.destroy', { tenantSlug: props.tenantSlug, product: productToDelete.value.id }), {
        onSuccess: () => {
            isConfirmDeleteDialogOpen.value = false;
            productToDelete.value = null;
        },
        onError: () => {
            // Handle error, maybe show a toast
        },
    }, {
        preserveScroll: true, // Keep scroll position after deletion
    });
};

// Function to handle sorting
const handleSort = (field: string) => {
    if (currentSortBy.value === field) {
        currentSortDirection.value = currentSortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortBy.value = field;
        currentSortDirection.value = 'asc';
    }
};

// Watch for changes in filters and trigger Inertia visit
watch([currentPerPage, currentSortBy, currentSortDirection, currentSearch, currentFilterField], () => {
    router.get(route('products.index', { tenantSlug: props.tenantSlug }), {
        perPage: currentPerPage.value,
        sortBy: currentSortBy.value,
        sortDirection: currentSortDirection.value,
        search: currentSearch.value,
        filterField: currentFilterField.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['products', 'filters'],
    });
}, { deep: true });

// Function to apply search filter on input change (e.g., debounce)
let searchTimeout: ReturnType<typeof setTimeout>;
const applySearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('products.index', { tenantSlug: props.tenantSlug }), {
            perPage: currentPerPage.value,
            sortBy: currentSortBy.value,
            sortDirection: currentSortDirection.value,
            search: currentSearch.value,
            filterField: currentFilterField.value,
        }, {
            preserveState: true,
            preserveScroll: true,
            only: ['products', 'filters'],
        });
    }, 300); // Debounce for 300ms
};

// Computed property for image preview URL
const imagePreviewUrl = computed(() => {
    if (form.image) {
        return URL.createObjectURL(form.image);
    } else if (currentProductImage.value) {
        return `/storage/${currentProductImage.value}`;
    }
    return null;
});

// Clean up URL object when component unmounts or image changes
watch(() => form.image, (newImage, oldImage) => {
    if (oldImage && typeof oldImage === 'object') {
        URL.revokeObjectURL(oldImage as any); // Revoke old URL to prevent memory leaks
    }
});
</script>

<template>
    <Head title="Master Produk" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Master Produk {{ tenantName ? `(${tenantName})` : '' }}
                </h1>
                <Button @click="openFormDialog()" class="flex items-center gap-2">
                    <PlusCircle class="h-4 w-4" />
                    Tambah Produk
                </Button>
            </div>

            <!-- Filter and Search Section -->
            <div class="flex flex-col sm:flex-row items-center gap-4 mb-4">
                <div class="relative w-full sm:w-1/2 md:w-1/3">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
                    <Input
                        type="text"
                        placeholder="Cari produk..."
                        v-model="currentSearch"
                        @input="applySearch"
                        class="pl-9 pr-3 py-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>
                <div class="w-full sm:w-auto">
                    <Select v-model="currentFilterField">
                        <SelectTrigger class="w-full sm:w-[180px]">
                            <SelectValue placeholder="Filter Berdasarkan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="name">Nama Produk</SelectItem>
                            <SelectItem value="sku">SKU</SelectItem>
                            <SelectItem value="description">Deskripsi</SelectItem>
                            <SelectItem value="unit">Unit</SelectItem>
                            <SelectItem value="ingredients">Bahan-bahan</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="w-full sm:w-auto">
                    <Select v-model.number="currentPerPage">
                        <SelectTrigger class="w-full sm:w-[100px]">
                            <SelectValue placeholder="Per Halaman" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="5">5</SelectItem>
                            <SelectItem :value="10">10</SelectItem>
                            <SelectItem :value="25">25</SelectItem>
                            <SelectItem :value="50">50</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Product List Table -->
            <div class="rounded-lg border bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[50px]">No.</TableHead>
                            <TableHead>Gambar</TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                @click="handleSort('name')"
                            >
                                <div class="flex items-center gap-1">
                                    Nama Produk
                                    <template v-if="currentSortBy === 'name'">
                                        <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                                        <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                                    </template>
                                </div>
                            </TableHead>
                            <TableHead>Kategori</TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                @click="handleSort('sku')"
                            >
                                <div class="flex items-center gap-1">
                                    SKU
                                    <template v-if="currentSortBy === 'sku'">
                                        <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                                        <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                                    </template>
                                </div>
                            </TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                @click="handleSort('price')"
                            >
                                <div class="flex items-center gap-1">
                                    Harga Jual
                                    <template v-if="currentSortBy === 'price'">
                                        <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                                        <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                                    </template>
                                </div>
                            </TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                @click="handleSort('cost_price')"
                            >
                                <div class="flex items-center gap-1">
                                    Harga Pokok
                                    <template v-if="currentSortBy === 'cost_price'">
                                        <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                                        <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                                    </template>
                                </div>
                            </TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                                @click="handleSort('stock')"
                            >
                                <div class="flex items-center gap-1">
                                    Stok
                                    <template v-if="currentSortBy === 'stock'">
                                        <ChevronUp v-if="currentSortDirection === 'asc'" class="h-4 w-4 text-blue-500" />
                                        <ChevronDown v-else class="h-4 w-4 text-blue-500" />
                                    </template>
                                </div>
                            </TableHead>
                            <TableHead>Unit</TableHead>
                            <TableHead class="w-[100px] text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="props.products.data.length === 0">
                            <TableCell colspan="10" class="text-center text-muted-foreground py-8">
                                Belum ada produk yang ditambahkan atau tidak ada hasil yang cocok.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="(product, index) in props.products.data" :key="product.id">
                            <TableCell>{{ props.products.from + index }}</TableCell>
                            <TableCell>
                                <img
                                    v-if="product.image"
                                    :src="`/storage/${product.image}`"
                                    alt="Product Image"
                                    class="w-12 h-12 object-cover rounded-md"
                                />
                                <div v-else class="w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded-md flex items-center justify-center text-gray-500 dark:text-gray-400">
                                    <ImageIcon class="w-6 h-6" />
                                </div>
                            </TableCell>
                            <TableCell class="font-medium">{{ product.name }}</TableCell>
                            <TableCell>{{ product.category?.name || '-' }}</TableCell>
                            <TableCell>{{ product.sku || '-' }}</TableCell>
                            <TableCell>{{ formatCurrency(product.price) }}</TableCell>
                            <TableCell>{{ formatCurrency(product.cost_price) }}</TableCell>
                            <TableCell>{{ product.stock }}</TableCell>
                            <TableCell>{{ product.unit || '-' }}</TableCell>
                            <TableCell class="text-right">
                                <Button variant="ghost" size="icon" @click="openFormDialog(product)" class="mr-1">
                                    <Edit class="h-4 w-4" />
                                    <span class="sr-only">Edit</span>
                                </Button>
                                <Button variant="ghost" size="icon" @click="openConfirmDeleteDialog(product)">
                                    <Trash2 class="h-4 w-4 text-red-500" />
                                    <span class="sr-only">Delete</span>
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination Controls -->
            <div v-if="props.products.last_page > 1" class="flex justify-between items-center mt-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Menampilkan {{ props.products.from }} hingga {{ props.products.to }} dari {{ props.products.total }} produk
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-for="(link, index) in props.products.links"
                        :key="index"
                        :as="Link"
                        :href="link.url || '#'"
                        :disabled="!link.url"
                        :variant="link.active ? 'default' : 'outline'"
                        class="px-3 py-1 rounded-md text-sm"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Add/Edit Product Dialog -->
        <Dialog v-model:open="isFormDialogOpen">
            <DialogContent class="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{{ formTitle }}</DialogTitle>
                    <DialogDescription>
                        Isi detail produk di bawah ini. Klik simpan saat Anda selesai.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitForm" class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="name" class="text-right">Nama Produk</Label>
                        <Input id="name" v-model="form.name" required class="col-span-3" />
                        <InputError :message="form.errors.name" class="col-span-4 col-start-2" />
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="category_id" class="text-right">Kategori</Label>
                        <Select v-model="form.category_id">
                            <SelectTrigger class="col-span-3">
                                <SelectValue placeholder="Pilih Kategori (Opsional)" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Tidak Ada Kategori</SelectItem>
                                <SelectItem v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.category_id" class="col-span-4 col-start-2" />
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="sku" class="text-right">SKU (Opsional)</Label>
                        <Input id="sku" v-model="form.sku" class="col-span-3" />
                        <InputError :message="form.errors.sku" class="col-span-4 col-start-2" />
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="description" class="text-right">Deskripsi (Opsional)</Label>
                        <Textarea id="description" v-model="form.description" rows="3" class="col-span-3" />
                        <InputError :message="form.errors.description" class="col-span-4 col-start-2" />
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="price" class="text-right">Harga Jual</Label>
                        <Input id="price" type="number" step="0.01" v-model.number="form.price" required class="col-span-3" min="0" />
                        <InputError :message="form.errors.price" class="col-span-4 col-start-2" />
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="cost_price" class="text-right">Harga Pokok</Label>
                        <Input id="cost_price" type="number" step="0.01" v-model.number="form.cost_price" required class="col-span-3" min="0" />
                        <InputError :message="form.errors.cost_price" class="col-span-4 col-start-2" />
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="stock" class="text-right">Stok</Label>
                        <Input readonly ="stock" type="number" v-model.number="form.stock" required class="col-span-3" min="0" />
                        <InputError :message="form.errors.stock" class="col-span-4 col-start-2" />
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="unit" class="text-right">Unit (Opsional)</Label>
                        <Input id="unit" v-model="form.unit" class="col-span-3" placeholder="e.g., pcs, kg, liter" />
                        <InputError :message="form.errors.unit" class="col-span-4 col-start-2" />
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="image" class="text-right">Gambar (Opsional)</Label>
                        <div class="col-span-3 flex flex-col gap-2">
                            <Input id="image" type="file" @change="handleImageChange" accept="image/*" class="col-span-3" />
                            <InputError :message="form.errors.image" />
                            <div v-if="imagePreviewUrl" class="relative w-32 h-32 border rounded-md overflow-hidden bg-gray-100 dark:bg-gray-700">
                                <img :src="imagePreviewUrl" alt="Image Preview" class="w-full h-full object-cover" />
                                <Button
                                    type="button"
                                    variant="destructive"
                                    size="icon"
                                    class="absolute top-1 right-1 h-6 w-6 rounded-full"
                                    @click="clearImage"
                                >
                                    <XCircle class="h-4 w-4" />
                                </Button>
                            </div>
                            <p v-else class="text-sm text-muted-foreground">Tidak ada gambar yang dipilih.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="is_food_item" class="text-right">Item Makanan?</Label>
                        <div class="col-span-3 flex items-center">
                            <Checkbox id="is_food_item" v-model:checked="form.is_food_item" />
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Ya, ini adalah item makanan.</span>
                        </div>
                        <InputError :message="form.errors.is_food_item" class="col-span-4 col-start-2" />
                    </div>

                    <div v-if="form.is_food_item" class="grid grid-cols-4 items-center gap-4">
                        <Label for="ingredients" class="text-right">Bahan-bahan (Opsional)</Label>
                        <Textarea id="ingredients" v-model="form.ingredients" rows="3" class="col-span-3" placeholder="Daftar bahan-bahan, pisahkan dengan koma." />
                        <InputError :message="form.errors.ingredients" class="col-span-4 col-start-2" />
                    </div>

                    <DialogFooter>
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                            Simpan Produk
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="isConfirmDeleteDialogOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Konfirmasi Penghapusan</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus produk "<strong>{{ productToDelete?.name }}</strong>"? Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="isConfirmDeleteDialogOpen = false">Batal</Button>
                    <Button variant="destructive" @click="deleteProduct" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                        Hapus
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
