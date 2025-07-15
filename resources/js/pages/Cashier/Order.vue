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
import { LoaderCircle, PlusCircle, MinusCircle, XCircle, Search, ShoppingCart, DollarSign, CreditCard, User, RotateCcw, ImageIcon } from 'lucide-vue-next'; // Import ImageIcon
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { formatCurrency } from '@/utils/formatters'; // Import formatCurrency helper

// Props from controller
interface Product {
    id: string;
    name: string;
    price: number;
    stock: number;
    unit: string | null;
    image: string | null;
    category?: { id: string; name: string };
    category_id: string | null;
}

interface Category {
    id: string;
    name: string;
}

interface Customer {
    id: string;
    name: string;
    email: string | null;
    phone: string | null;
}

const props = defineProps<{
    products: Product[];
    categories: Category[];
    customers: Customer[];
    tenantSlug: string;
    tenantName: string;
    ipaymuConfigured: boolean;
}>();

// Inertia page props
const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('tenant.dashboard', { tenantSlug: props.tenantSlug }),
    },
    {
        title: 'Pemesanan',
        href: route('sales.order', { tenantSlug: props.tenantSlug }),
    },
];

// Reactive state for product filtering
const productSearch = ref('');
const selectedCategory = ref<string | null>(null);

// Reactive state for the order cart
interface CartItem extends Product {
    quantity: number;
}
const cart = ref<CartItem[]>([]);

// Reactive state for selected customer
const selectedCustomer = ref<string | null>(null); // Holds customer ID

// Reactive state for payment details
const discountAmount = ref(0);
const taxRate = ref(0); // in percentage, e.g., 10 for 10%
const paymentMethod = ref<'cash' | 'ipaymu'>('cash');
const paidAmount = ref(0); // For cash payments
const notes = ref('');

// Computed properties for order summary
const subtotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

const calculatedTaxAmount = computed(() => {
    return (subtotal.value - discountAmount.value) * (taxRate.value / 100);
});

const totalAmount = computed(() => {
    return subtotal.value - discountAmount.value + calculatedTaxAmount.value;
});

const changeAmount = computed(() => {
    if (paymentMethod.value === 'cash') {
        return paidAmount.value > totalAmount.value ? paidAmount.value - totalAmount.value : 0;
    }
    return 0;
});

// Filtered products for display
const filteredProducts = computed(() => {
    let products = props.products;

    if (selectedCategory.value) {
        products = products.filter(product => product.category_id === selectedCategory.value);
    }

    if (productSearch.value) {
        const searchTerm = productSearch.value.toLowerCase();
        products = products.filter(product =>
            product.name.toLowerCase().includes(searchTerm) ||
            product.unit?.toLowerCase().includes(searchTerm) ||
            product.category?.name.toLowerCase().includes(searchTerm)
        );
    }
    return products;
});

// Functions for cart management
const addToCart = (product: Product) => {
    const existingItem = cart.value.find(item => item.id === product.id);
    if (existingItem) {
        if (existingItem.quantity < product.stock) {
            existingItem.quantity++;
        } else {
            alert(`Stok ${product.name} tidak mencukupi.`);
        }
    } else {
        if (product.stock > 0) {
            cart.value.push({ ...product, quantity: 1 });
        } else {
            alert(`Stok ${product.name} kosong.`);
        }
    }
};

const updateCartQuantity = (item: CartItem, newQuantity: number) => {
    if (newQuantity < 1) {
        removeFromCart(item);
        return;
    }
    const product = props.products.find(p => p.id === item.id);
    if (product && newQuantity <= product.stock) {
        item.quantity = newQuantity;
    } else if (product) {
        alert(`Stok ${product.name} tidak mencukupi untuk jumlah ini.`);
        item.quantity = product.stock; // Set to max available stock
    }
};

const removeFromCart = (itemToRemove: CartItem) => {
    cart.value = cart.value.filter(item => item.id !== itemToRemove.id);
};

// Form for submitting the sale
const saleForm = useForm({
    items: [] as { product_id: string; quantity: number }[],
    customer_id: null as string | null,
    discount_amount: 0,
    tax_rate: 0,
    payment_method: 'cash',
    paid_amount: 0,
    notes: '',
});

// Function to handle sale submission
const processSale = () => {
    if (cart.value.length === 0) {
        alert('Keranjang belanja kosong. Tambahkan produk terlebih dahulu.');
        return;
    }

    saleForm.items = cart.value.map(item => ({
        product_id: item.id,
        quantity: item.quantity,
    }));
    saleForm.customer_id = selectedCustomer.value;
    saleForm.discount_amount = discountAmount.value;
    saleForm.tax_rate = taxRate.value;
    saleForm.payment_method = paymentMethod.value;
    saleForm.paid_amount = paidAmount.value;
    saleForm.notes = notes.value;

    saleForm.post(route('sales.store', { tenantSlug: props.tenantSlug }), {
        onSuccess: () => {
            // Reset cart and form after successful sale
            cart.value = [];
            selectedCustomer.value = null;
            discountAmount.value = 0;
            taxRate.value = 0;
            paymentMethod.value = 'cash';
            paidAmount.value = 0;
            notes.value = '';
            // Success message handled by Inertia flash messages from controller
        },
        onError: (errors) => {
            // Display errors (InputError components will handle this)
            console.error('Sale submission errors:', errors);
            alert('Terjadi kesalahan saat memproses penjualan. Silakan cek input Anda.');
        },
        onFinish: () => {
            // Any final actions
        }
    });
};

// Reset all fields
const resetOrder = () => {
    cart.value = [];
    productSearch.value = '';
    selectedCategory.value = null;
    selectedCustomer.value = null;
    discountAmount.value = 0;
    taxRate.value = 0;
    paymentMethod.value = 'cash';
    paidAmount.value = 0;
    notes.value = '';
    saleForm.reset();
};

// Watch for totalAmount changes to reset paidAmount if it becomes less than total
// This ensures paidAmount is never less than totalAmount if paymentMethod is cash
watch(totalAmount, (newTotal) => {
    if (paymentMethod.value === 'cash' && paidAmount.value < newTotal) {
        paidAmount.value = newTotal; // Set paid amount to at least total if it's less
    }
});

// Watch for paymentMethod change to adjust paidAmount
watch(paymentMethod, (newMethod) => {
    if (newMethod === 'ipaymu') {
        paidAmount.value = 0; // Clear paid amount for iPaymu
    } else if (newMethod === 'cash') {
        // When switching to cash, if paidAmount is 0 or less than total,
        // set it to totalAmount as a starting point, but allow user to change.
        if (paidAmount.value === 0 || paidAmount.value < totalAmount.value) {
            paidAmount.value = totalAmount.value;
        }
    }
});

</script>

<template>
    <Head title="Pemesanan & Pembayaran" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col lg:flex-row gap-4 p-4">
            <!-- Left Panel: Product Selection -->
            <div class="lg:w-2/3 flex flex-col gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 flex flex-col sm:flex-row gap-4">
                    <div class="relative flex-grow">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
                        <Input
                            type="text"
                            placeholder="Cari produk..."
                            v-model="productSearch"
                            class="pl-9 pr-3 py-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>
                    <div class="w-full sm:w-auto">
                        <Select v-model="selectedCategory">
                            <SelectTrigger class="w-full sm:w-[180px]">
                                <SelectValue placeholder="Filter Kategori" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Semua Kategori</SelectItem>
                                <SelectItem v-for="cat in props.categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Product Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 overflow-y-auto flex-grow pb-4">
                    <div v-if="filteredProducts.length === 0" class="col-span-full text-center text-muted-foreground py-8">
                        Tidak ada produk yang tersedia atau cocok dengan pencarian Anda.
                    </div>
                    <div
                        v-for="product in filteredProducts"
                        :key="product.id"
                        @click="addToCart(product)"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden cursor-pointer hover:shadow-lg transition-shadow duration-200 flex flex-col"
                        :class="{ 'opacity-50 cursor-not-allowed': product.stock === 0 }"
                    >
                        <div class="relative w-full h-32 bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                            <img v-if="product.image" :src="`/storage/${product.image}`" alt="Product Image" class="w-full h-full object-cover" />
                            <ImageIcon v-else class="h-16 w-16 text-gray-400" />
                            <span v-if="product.stock === 0" class="absolute top-1 right-1 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">Stok Habis</span>
                        </div>
                        <div class="p-3 flex-grow flex flex-col justify-between">
                            <div>
                                <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 truncate">{{ product.name }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ product.category?.name || 'Tanpa Kategori' }}</p>
                            </div>
                            <div class="flex justify-between items-center mt-2">
                                <span class="text-blue-600 dark:text-blue-400 font-bold text-lg">{{ formatCurrency(product.price) }}</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Stok: {{ product.stock }} {{ product.unit }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Order Cart & Payment -->
            <div class="lg:w-1/3 flex flex-col gap-4">
                <!-- Order Cart -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 flex-grow flex flex-col">
                    <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <ShoppingCart class="h-5 w-5" /> Keranjang Belanja
                    </h2>
                    <div v-if="cart.length === 0" class="text-center text-muted-foreground py-8 flex-grow flex items-center justify-center">
                        Keranjang kosong. Klik produk untuk menambahkannya.
                    </div>
                    <div v-else class="flex-grow overflow-y-auto pr-2 -mr-2">
                        <div v-for="item in cart" :key="item.id" class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                            <div class="flex-grow">
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ item.name }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ formatCurrency(item.price) }} x {{ item.quantity }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button variant="outline" size="icon" @click="updateCartQuantity(item, item.quantity - 1)" :disabled="item.quantity <= 1">
                                    <MinusCircle class="h-4 w-4" />
                                </Button>
                                <span class="font-semibold w-6 text-center">{{ item.quantity }}</span>
                                <Button variant="outline" size="icon" @click="updateCartQuantity(item, item.quantity + 1)" :disabled="item.quantity >= item.stock">
                                    <PlusCircle class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="icon" @click="removeFromCart(item)" class="text-red-500">
                                    <XCircle class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary & Payment -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 flex flex-col gap-3">
                    <h2 class="text-xl font-bold mb-2">Ringkasan Pesanan</h2>

                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>Subtotal:</span>
                        <span>{{ formatCurrency(subtotal) }}</span>
                    </div>

                    <div class="grid gap-2">
                        <Label for="discount_amount">Diskon (Rp)</Label>
                        <Input id="discount_amount" type="number" step="0.01" v-model.number="discountAmount" min="0" :max="subtotal" />
                        <InputError :message="saleForm.errors.discount_amount" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="tax_rate">Pajak (%)</Label>
                        <Input id="tax_rate" type="number" step="0.01" v-model.number="taxRate" min="0" max="100" />
                        <InputError :message="saleForm.errors.tax_rate" />
                    </div>

                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>Pajak (Rp):</span>
                        <span>{{ formatCurrency(calculatedTaxAmount) }}</span>
                    </div>

                    <div class="flex justify-between font-bold text-2xl text-gray-900 dark:text-gray-100 border-t pt-3 mt-3">
                        <span>TOTAL:</span>
                        <span>{{ formatCurrency(totalAmount) }}</span>
                    </div>

                    <h3 class="text-lg font-bold mt-4 mb-2">Detail Pembayaran</h3>

                    <div class="grid gap-2">
                        <Label for="customer_id">Pelanggan (Opsional)</Label>
                        <Select v-model="selectedCustomer">
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih Pelanggan" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Pelanggan Umum</SelectItem>
                                <SelectItem v-for="customer in props.customers" :key="customer.id" :value="customer.id">
                                    {{ customer.name }} ({{ customer.phone || customer.email || 'N/A' }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="saleForm.errors.customer_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="payment_method">Metode Pembayaran</Label>
                        <Select v-model="paymentMethod">
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih Metode" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="cash">Tunai</SelectItem>
                                <SelectItem value="ipaymu" :disabled="!ipaymuConfigured">
                                    iPaymu <span v-if="!ipaymuConfigured" class="text-red-500 text-xs">(Belum Dikonfigurasi)</span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="saleForm.errors.payment_method" />
                    </div>

                    <div v-if="paymentMethod === 'cash'" class="grid gap-2">
                        <Label for="paid_amount">Jumlah Dibayar (Rp)</Label>
                        <Input id="paid_amount" type="number" step="0.01" v-model.number="paidAmount" :min="totalAmount" />
                        <InputError :message="saleForm.errors.paid_amount" />
                    </div>

                    <div v-if="paymentMethod === 'cash'" class="flex justify-between font-bold text-xl text-green-600 dark:text-green-400">
                        <span>Kembalian:</span>
                        <span>{{ formatCurrency(changeAmount) }}</span>
                    </div>

                    <div class="grid gap-2">
                        <Label for="notes">Catatan (Opsional)</Label>
                        <Textarea id="notes" v-model="notes" rows="2" />
                        <InputError :message="saleForm.errors.notes" />
                    </div>

                    <div class="flex gap-2 mt-4">
                        <Button type="button" variant="outline" @click="resetOrder" class="flex-grow">
                            <RotateCcw class="h-4 w-4 mr-2" /> Reset
                        </Button>
                        <Button type="button" @click="processSale" :disabled="saleForm.processing || cart.length === 0 || (paymentMethod === 'cash' && paidAmount < totalAmount)" class="flex-grow">
                            <LoaderCircle v-if="saleForm.processing" class="h-4 w-4 animate-spin mr-2" />
                            Proses Pembayaran
                        </Button>
                    </div>
                    <InputError :message="saleForm.errors.items" />
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Hidden container for PDF generation (moved from IdCard.vue) -->
    <!-- This is only needed if you reuse the template for other PDF generations -->
    <!-- <div id="id-card-to-print-hidden" class="absolute -left-[9999px] -top-[9999px]">
        <CustomerIdCardTemplate
            v-if="customerToGeneratePdf"
            :customer="customerToGeneratePdf"
            :tenantName="tenantName"
        />
    </div> -->
</template>
