<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { ref, watch } from 'vue'; // Import ref and watch

interface TenantData {
    name: string;
    ipaymu_api_key: string | null;
    ipaymu_secret_key: string | null;
    invitation_code: string | null; // Add invitation_code to the interface
}

interface Props {
    tenant: TenantData;
    tenantSlug: string;
    status?: string;
    newInvitationCode?: string; // Prop to receive new code after generation (from flash data)
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
    invitation_code: props.tenant.invitation_code || '', // Initialize with existing code
});

// Watch for changes in the `newInvitationCode` prop (from flash data)
// and update the form's invitation_code field accordingly.
watch(() => props.newInvitationCode, (newCode) => {
    if (newCode) {
        form.invitation_code = newCode;
    }
});

const submit = () => {
    form.patch(route('tenant.settings.update', { tenantSlug: props.tenantSlug }), {
        preserveScroll: true,
    });
};

const generateNewCode = () => {
    // This will send a POST request to the backend to generate a new code.
    // The backend will then redirect back, and the new code will be available
    // in the `newInvitationCode` prop via Inertia's flash data.
    useForm({}).post(route('tenant.settings.generateInvitationCode', { tenantSlug: props.tenantSlug }), {
        preserveScroll: true,
        onSuccess: () => {
            // The `watch` effect above will automatically update `form.invitation_code`
            // when `props.newInvitationCode` is received.
        },
        onError: (errors) => {
            console.error("Error generating code:", errors);
            // You might want to display a more user-friendly error message here
        }
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

                    <!-- New: Invitation Code Field -->
                    <div class="grid gap-2">
                        <Label for="invitation_code">Kode Undangan Tenant</Label>
                        <div class="flex items-center gap-2">
                            <Input
                                id="invitation_code"
                                type="text"
                                class="block w-full"
                                v-model="form.invitation_code"
                                placeholder="Kode unik untuk undangan tenant"
                            />
                            <Button type="button" @click="generateNewCode" :disabled="form.processing" variant="outline">
                                Generate Baru
                            </Button>
                        </div>
                        <InputError class="mt-2" :message="form.errors.invitation_code" />
                        <p class="text-sm text-muted-foreground">
                            Gunakan kode ini untuk mengundang pengguna baru ke tenant Anda.
                        </p>
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
