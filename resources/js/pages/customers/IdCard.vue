<script setup lang="ts">
import { computed } from 'vue';

interface Customer {
    id: string;
    name: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    created_at: string; // Add created_at for "Member Since"
}

const props = defineProps<{
    customer: Customer;
    tenantName: string;
}>();

// Format creation date for "Member Since"
const memberSince = computed(() => {
    if (!props.customer.created_at) return '-';
    return new Date(props.customer.created_at).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});
</script>

<template>
    <!-- ID Card Container - This is the element that html2pdf.js will convert -->
    <!-- Dimensions are set here for the HTML element that will be converted to PDF -->
    <div id="id-card-content"
         class="w-[85.6mm] h-[53.98mm] bg-gray-950 text-white shadow-xl flex flex-col justify-between p-4 relative overflow-hidden">
        <!-- Subtle Background Pattern -->
        <div class="absolute inset-0 opacity-10"
             style="background-image: radial-gradient(circle at top left, rgba(255,255,255,0.1) 0%, transparent 70%);">
        </div>
        <div class="absolute inset-0 opacity-5"
             style="background-image: linear-gradient(to bottom right, rgba(255,255,255,0.05), transparent 50%);">
        </div>

        <!-- Top Section: Customer Details -->
        <div class="relative z-10 flex-grow pt-1">
            <h1 class="text-lg font-bold mb-[2px] truncate leading-tight">{{ customer.name }}</h1>
            <p v-if="customer.phone" class="text-[9px] text-gray-300 truncate leading-tight">Telepon: {{ customer.phone }}</p>
            <p v-if="customer.email" class="text-[9px] text-gray-300 truncate leading-tight">Email: {{ customer.email }}</p>
            <p v-if="customer.address" class="text-[8px] text-gray-400 mt-[2px] line-clamp-2 leading-tight">Alamat: {{ customer.address }}</p>
        </div>

        <!-- Bottom Section: Tenant Info & Member Since -->
        <div class="relative z-10 text-right mt-2 flex justify-between items-end">
            <div>
                <p class="text-[8px] text-gray-400">Anggota Sejak:</p>
                <p class="text-[9px] font-semibold text-gray-200">{{ memberSince }}</p>
            </div>
            <div>
                <p class="text-lg font-semibold text-gray-100 leading-tight">{{ tenantName }}</p>
                <p class="text-[8px] text-gray-400">Kartu Pelanggan</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* No @media print styles needed here as html2pdf handles rendering */
/* Ensure the font is loaded if not using system fonts or Tailwind's default */
/* @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap'); */
/* body { font-family: 'Inter', sans-serif; } */
</style>
