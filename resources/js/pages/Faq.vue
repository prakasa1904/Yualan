<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
const appName = import.meta.env.VITE_APP_NAME || 'Yualan POS';
const faqs = [
  {
    q: 'Apa itu Yualan POS?',
    a: 'Yualan POS adalah sistem Point of Sale berbasis cloud yang memudahkan pengelolaan bisnis, penjualan, inventaris, dan laporan secara online.'
  },
  {
    q: 'Bagaimana cara mendaftar dan mulai menggunakan Yualan POS?',
    a: 'Klik tombol Daftar di halaman utama, isi data yang diperlukan, dan ikuti instruksi aktivasi melalui email.'
  },
  {
    q: 'Apakah data saya aman di Yualan POS?',
    a: 'Ya, kami menggunakan standar keamanan tinggi dan enkripsi untuk melindungi data bisnis Anda.'
  },
  {
    q: 'Bagaimana sistem pembayaran dan langganan?',
    a: 'Pembayaran dilakukan secara online melalui iPaymu. Anda dapat memilih paket langganan sesuai kebutuhan.'
  },
  {
    q: 'Bagaimana jika saya ingin refund?',
    a: 'Silakan baca kebijakan refund kami di halaman Refund. Pengajuan refund dapat dilakukan maksimal 3x24 jam setelah pembayaran.'
  },
  {
    q: 'Siapa yang bisa saya hubungi untuk bantuan?',
    a: 'Anda dapat menghubungi tim support kami melalui email support@yualan.com atau kontak yang tersedia di aplikasi.'
  }
];
</script>
<template>
  <Head :title="`FAQ | ${appName}`" />
  <div class="min-h-screen flex flex-col bg-gradient-to-br from-blue-500 to-indigo-600 text-gray-900 dark:text-gray-100 font-inter">
    <!-- Hero Section -->
    <section class="relative flex flex-col items-center justify-center text-center px-4 sm:px-6 lg:px-8 py-24 sm:py-32 md:py-40 text-white overflow-hidden">
      <div class="absolute inset-0 opacity-20 bg-pattern-dots"></div>
      <div class="relative z-10 max-w-3xl mx-auto">
        <h1 class="text-5xl sm:text-6xl font-extrabold leading-tight mb-6 animate-fade-in-up">
          Frequently Asked <span class="text-yellow-300">Questions</span>
        </h1>
        <p class="text-lg sm:text-xl mb-10 opacity-90 animate-fade-in-up delay-200">
          Temukan jawaban atas pertanyaan umum seputar <span class="font-bold">{{ appName }}</span>.
        </p>
      </div>
    </section>

    <!-- Card Section -->
    <section class="relative z-10 max-w-3xl mx-auto w-full -mt-32 mb-16 px-4 sm:px-8">
      <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl p-10 md:p-14 border border-blue-100 dark:border-gray-700 animate-fade-in-up">
        <div v-for="(item, idx) in faqs" :key="idx" class="mb-10">
          <h2 class="text-xl font-bold mb-2 text-blue-700 dark:text-yellow-300">Q: {{ item.q }}</h2>
          <p class="text-gray-700 dark:text-gray-200">A: {{ item.a }}</p>
        </div>
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
