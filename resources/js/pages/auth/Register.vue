<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue'; // Assuming this path is correct
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';

// Reactive state for managing the current step and registration type
const currentStep = ref(1);
const registrationType = ref<'personal' | 'company' | null>(null);

// Form data using Inertia's useForm hook
const form = useForm({
    registration_type: '', // 'personal' or 'company'
    // User account details
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    // Personal registration specific
    invitation_code: '',
    // Company registration specific (maps to tenant fields)
    company_name: '',
    company_email: '', // Tenant email
    company_phone: '',
    company_address: '',
    company_city: '',
    company_state: '',
    company_zip_code: '',
    company_country: '',
    business_type: '',
});

// Computed property to determine the title based on the current step
const pageTitle = computed(() => {
    switch (currentStep.value) {
        case 1:
            return 'Pilih Tipe Pendaftaran';
        case 2:
            return 'Detail Akun Anda';
        case 3:
            return registrationType.value === 'personal' ? 'Kode Undangan & Persetujuan' : 'Detail Perusahaan Anda';
        default:
            return 'Daftar Akun Baru';
    }
});

// Computed property to determine the description based on the current step
const pageDescription = computed(() => {
    switch (currentStep.value) {
        case 1:
            return 'Pilih bagaimana Anda ingin mendaftar untuk memulai.';
        case 2:
            return 'Masukkan detail yang akan Anda gunakan untuk masuk.';
        case 3:
            return registrationType.value === 'personal'
                ? 'Masukkan kode undangan Anda'
                : 'Lengkapi informasi perusahaan Anda.';
        default:
            return 'Lengkapi detail Anda untuk membuat akun.';
    }
});

// Function to navigate to the next step
const nextStep = () => {
    if (currentStep.value === 1 && !registrationType.value) {
        // Basic validation for step 1
        alert('Silakan pilih tipe pendaftaran.'); // Using alert for simplicity, consider a custom modal
        return;
    }
    // Set the form's registration_type based on selection
    form.registration_type = registrationType.value || '';
    currentStep.value++;
};

// Function to navigate to the previous step
const prevStep = () => {
    currentStep.value--;
};

// Function to handle form submission
const submit = () => {
    // Determine the route based on registration type
    // You'll need to define a single backend route that handles both types
    // e.g., route('register.process')
    form.post(route('register'), { // Assuming 'register' route handles the logic
        onFinish: () => form.reset('password', 'password_confirmation'),
        onError: (errors) => {
            // If there are errors, check which step they belong to and navigate back
            if (errors.name || errors.email || errors.password || errors.password_confirmation) {
                currentStep.value = 2;
            } else if (errors.invitation_code || errors.company_name || errors.company_email || errors.business_type) {
                currentStep.value = 3;
            }
        }
    });
};
</script>

<template>
    <AuthBase :title="pageTitle" :description="pageDescription">
        <Head title="Register" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <!-- Step 1: Choose Registration Type -->
            <div v-if="currentStep === 1" class="grid gap-6">
                <div class="grid gap-4">
                    <Button
                        type="button"
                        @click="registrationType = 'personal'"
                        :class="{ 'bg-blue-600 hover:bg-blue-700 text-white': registrationType === 'personal', 'bg-gray-200 hover:bg-gray-300 text-gray-800': registrationType !== 'personal' }"
                        class="w-full py-4 rounded-md shadow-sm transition-colors duration-200"
                    >
                        Daftar sebagai Perorangan
                    </Button>
                    <p class="text-sm text-center text-muted-foreground">
                        Jika Anda mendaftar sebagai perorangan, Anda memerlukan kode undangan.
                    </p>
                </div>
                <div class="grid gap-4">
                    <Button
                        type="button"
                        @click="registrationType = 'company'"
                        :class="{ 'bg-blue-600 hover:bg-blue-700 text-white': registrationType === 'company', 'bg-gray-200 hover:bg-gray-300 text-gray-800': registrationType !== 'company' }"
                        class="w-full py-4 rounded-md shadow-sm transition-colors duration-200"
                    >
                        Daftar sebagai Perusahaan
                    </Button>
                    <p class="text-sm text-center text-muted-foreground">
                        Daftarkan perusahaan Anda untuk mengelola bisnis Anda.
                    </p>
                </div>
                <Button type="button" @click="nextStep" :disabled="!registrationType" class="mt-4 w-full">
                    Lanjutkan
                </Button>
            </div>

            <!-- Step 2: User Account Details -->
            <div v-else-if="currentStep === 2" class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Nama Lengkap</Label>
                    <Input id="name" type="text" required autofocus autocomplete="name" v-model="form.name" placeholder="Nama Lengkap Anda" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Alamat Email</Label>
                    <Input id="email" type="email" required autocomplete="email" v-model="form.email" placeholder="email@example.com" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Kata Sandi</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="Kata Sandi"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Konfirmasi Kata Sandi</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Konfirmasi Kata Sandi"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <div class="flex justify-between mt-4">
                    <Button type="button" @click="prevStep" variant="outline">
                        Kembali
                    </Button>
                    <Button type="button" @click="nextStep">
                        Lanjutkan
                    </Button>
                </div>
            </div>

            <!-- Step 3: Type-Specific Details -->
            <div v-else-if="currentStep === 3" class="grid gap-6">
                <div v-if="registrationType === 'personal'" class="grid gap-2">
                    <Label for="invitation_code">Kode Undangan</Label>
                    <Input id="invitation_code" type="text" required v-model="form.invitation_code" placeholder="Masukkan kode undangan Anda" />
                    <InputError :message="form.errors.invitation_code" />
                    <p class="text-sm text-muted-foreground mt-2">
                        Setelah mendaftar, akun Anda akan memerlukan kode undangan dari admin tenant. Jika tidak memiliki kode, hubungi admin Anda atau register sebagai tenant.
                    </p>
                </div>

                <div v-else-if="registrationType === 'company'" class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="company_name">Nama Perusahaan</Label>
                        <Input id="company_name" type="text" required v-model="form.company_name" placeholder="Nama Perusahaan Anda" />
                        <InputError :message="form.errors.company_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="company_email">Email Perusahaan</Label>
                        <Input id="company_email" type="email" required v-model="form.company_email" placeholder="email.perusahaan@example.com" />
                        <InputError :message="form.errors.company_email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="company_phone">Nomor Telepon Perusahaan</Label>
                        <Input id="company_phone" type="text" v-model="form.company_phone" placeholder="Nomor Telepon" />
                        <InputError :message="form.errors.company_phone" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="company_address">Alamat Perusahaan</Label>
                        <Input id="company_address" type="text" v-model="form.company_address" placeholder="Alamat Lengkap" />
                        <InputError :message="form.errors.company_address" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="company_city">Kota</Label>
                            <Input id="company_city" type="text" v-model="form.company_city" placeholder="Kota" />
                            <InputError :message="form.errors.company_city" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="company_state">Provinsi</Label>
                            <Input id="company_state" type="text" v-model="form.company_state" placeholder="Provinsi" />
                            <InputError :message="form.errors.company_state" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="company_zip_code">Kode Pos</Label>
                            <Input id="company_zip_code" type="text" v-model="form.company_zip_code" placeholder="Kode Pos" />
                            <InputError :message="form.errors.company_zip_code" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="company_country">Negara</Label>
                            <Input id="company_country" type="text" v-model="form.company_country" placeholder="Negara" />
                            <InputError :message="form.errors.company_country" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="business_type">Tipe Bisnis</Label>
                        <Input id="business_type" type="text" required v-model="form.business_type" placeholder="e.g., Toko, Restoran, Minimarket" />
                        <InputError :message="form.errors.business_type" />
                    </div>
                </div>

                <div class="flex justify-between mt-4">
                    <Button type="button" @click="prevStep" variant="outline">
                        Kembali
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        Daftar Akun
                    </Button>
                </div>
            </div>

            <div class="text-center text-sm text-muted-foreground mt-6">
                Sudah punya akun?
                <TextLink :href="route('login')" class="underline underline-offset-4">Masuk</TextLink>
            </div>
        </form>
    </AuthBase>
</template>

