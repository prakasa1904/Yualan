<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
const appName = import.meta.env.VITE_APP_NAME || 'Yualan POS';
</script>
<template>
  <Head :title="`Kebijakan Refund | ${appName}`" />
  <div class="min-h-screen flex flex-col bg-gradient-to-br from-blue-500 to-indigo-600 text-gray-900 dark:text-gray-100 font-inter">
    <!-- Hero Section -->
    <section class="relative flex flex-col items-center justify-center text-center px-4 sm:px-6 lg:px-8 py-24 sm:py-32 md:py-40 text-white overflow-hidden">
      <div class="absolute inset-0 opacity-20 bg-pattern-dots"></div>
      <div class="relative z-10 max-w-3xl mx-auto">
        <h1 class="text-5xl sm:text-6xl font-extrabold leading-tight mb-6 animate-fade-in-up">
          Kebijakan <span class="text-yellow-300">Refund & Pembayaran</span>
        </h1>
        <p class="text-lg sm:text-xl mb-10 opacity-90 animate-fade-in-up delay-200">
          Ketentuan refund dan pembayaran melalui iPaymu untuk langganan <span class="font-bold">{{ appName }}</span>.
        </p>
      </div>
    </section>

    <!-- Card Section -->
    <section class="relative z-10 max-w-3xl mx-auto w-full -mt-32 mb-16 px-4 sm:px-8">
      <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl p-10 md:p-14 text-lg leading-relaxed border border-blue-100 dark:border-gray-700 animate-fade-in-up">
        <ul class="list-disc pl-6 mb-6 text-gray-700 dark:text-gray-200">
          <li class="mb-2">Pembayaran yang sudah diproses melalui iPaymu tidak dapat dibatalkan secara otomatis.</li>
          <li class="mb-2">Permintaan refund dapat diajukan maksimal 3x24 jam setelah pembayaran dengan alasan yang jelas.</li>
          <li class="mb-2">Proses refund akan diverifikasi oleh tim <b>{{ appName }}</b> dan iPaymu sesuai ketentuan yang berlaku.</li>
          <li class="mb-2">Biaya administrasi atau potongan dari iPaymu dapat berlaku pada proses refund.</li>
          <li class="mb-2">Untuk pengajuan refund, silakan hubungi support kami melalui email atau kontak yang tersedia.</li>
        </ul>
        <p class="mb-2 text-gray-800 dark:text-gray-100">Dengan melakukan pembayaran, Anda dianggap telah membaca dan menyetujui kebijakan refund ini.</p>
      </div>
      <div class="mt-8 text-center">
        <Link :href="route('home')" class="inline-block px-8 py-3 rounded-full text-lg font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition-all duration-300 shadow-xl transform hover:scale-105">Kembali ke Beranda</Link>
      </div>
    </section>
  </div>
</template>
<style scoped>
@keyframes fadeInFromBottom {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
  animation: fadeInFromBottom 0.8s ease-out forwards;
}
.animate-fade-in-up.delay-200 {
  animation-delay: 0.2s;
}
.bg-pattern-dots {
  background-image: radial-gradient(currentColor 1px, transparent 1px);
  background-size: 20px 20px;
  color: rgba(255,255,255,0.1);
}
.font-inter { font-family: 'Inter', sans-serif; }
</style>
