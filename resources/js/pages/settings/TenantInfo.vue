<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types'; // Import BreadcrumbItem type

interface TenantData {
    name: string;
    ipaymu_api_key: string | null;
    ipaymu_secret_key: string | null;
}

interface Props {
    tenant: TenantData;
    tenantSlug: string; // Passed from the controller
    status?: string;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Pengaturan Tenant',
        href: route('tenant.settings.info', { tenantSlug: props.tenantSlug }),
    },
];

const form = useForm({
    name: props.tenant.name,
    ipaymu_api_key: props.tenant.ipaymu_api_key || '',
    ipaymu_secret_key: props.tenant.ipaymu_secret_key || '',
});

const submit = () => {
    form.patch(route('tenant.settings.update', { tenantSlug: props.tenantSlug }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Pengaturan Tenant" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    title="Informasi Tenant"
                    description="Perbarui nama tenant dan kredensial iPaymu Anda."
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Nama Tenant</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autocomplete="organization"
                            placeholder="Nama Bisnis Anda"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ipaymu_api_key">iPaymu API Key (VA)</Label>
                        <Input
                            id="ipaymu_api_key"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.ipaymu_api_key"
                            autocomplete="off"
                            placeholder="Contoh: 117xxxxxxx"
                        />
                        <InputError class="mt-2" :message="form.errors.ipaymu_api_key" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ipaymu_secret_key">iPaymu Secret Key</Label>
                        <Input
                            id="ipaymu_secret_key"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="form.ipaymu_secret_key"
                            autocomplete="off"
                            placeholder="Contoh: pSxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                        />
                        <InputError class="mt-2" :message="form.errors.ipaymu_secret_key" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Simpan</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600 dark:text-neutral-400">Tersimpan.</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
