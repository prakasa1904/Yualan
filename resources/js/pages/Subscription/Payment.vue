<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { defineProps } from 'vue';

const props = defineProps({
    pricingPlans: Array,
});

const form = useForm({
    plan_id: null,
});

const subscribe = (planId) => {
    form.plan_id = planId;
    form.post(route('subscription.subscribe'), {
        onFinish: () => {
            //
        },
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};
</script>

<template>
    <Head title="Subscription Payment" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Pembayaran Langganan</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-2xl font-bold mb-4">Pilih Paket Langganan Anda</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div v-for="plan in pricingPlans" :key="plan.id" class="border p-6 rounded-lg">
                                <h4 class="text-xl font-semibold">{{ plan.plan_name }}</h4>
                                <p class="text-gray-500 mt-2">{{ plan.plan_description }}</p>
                                <div class="text-3xl font-bold my-4">
                                    {{ formatCurrency(plan.price) }}
                                    <span class="text-lg font-normal">/{{ plan.period_type === 'monthly' ? 'bulan' : 'tahun' }}</span>
                                </div>
                                <button @click="subscribe(plan.id)" class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600" :disabled="form.processing">
                                    {{ form.processing ? 'Memproses...' : 'Pilih Paket' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
