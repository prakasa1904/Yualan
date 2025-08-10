
<img width="1080" height="1080" alt="New Website ##Yualan POS dilengkapi dengan sistem subscription management yang canggih:

### � Subscription & Billing Sistem SaaS & Subscriptionlue Mockup Instagram - Laptop" src="https://github.com/user-attachments/assets/f4960632-a9a1-4a45-aa05-74e8acd74834" />

# ✨ Yualan POS

> **Solusi Point of Sale (POS) berbasis SaaS yang ringan, powerful, dan open source**

**Yualan** adalah aplikasi **Point of Sale (POS)** berbasis **SaaS (Software as a Service)** yang dirancang untuk menyederhanakan dan mengotomatiskan operasional bisnis ritel Anda. Dibangun dengan semangat open source, Yualan tersedia di bawah **GNU General Public License v2.0 (GPLv2)** — artinya Anda bebas untuk menggunakan, memodifikasi, dan mendistribusikannya.

## 🎯 Tentang Yualan

Yualan hadir sebagai solusi POS yang cocok untuk bisnis ritel modern, UMKM, dan pelaku usaha yang ingin memulai sistem penjualan berbasis cloud tanpa biaya lisensi mahal.

## ⭐ Fitur Utama

### 👥 Manajemen Multi-Tenant
- Pengelolaan penyewa/tenant dalam satu sistem POS
- Integrasi pembayaran otomatis dengan gateway seperti **iPaymu**

### 🔐 Akses Berbasis Peran
- **Superadmin**: Mengelola sistem dan semua penyewa
- **Admin (Pemilik Toko)**: Mengelola toko dan data bisnis mereka
- **Kasir**: Akses terbatas untuk penjualan & transaksi saja

### 🗃️ Manajemen Data Master
- **Produk**: SKU, kategori, harga, dan stok
- **Pelanggan**: Data kontak, histori transaksi
- **Kategori Produk**: Kelompokkan item dengan mudah

### � Sistem Transaksi
- Pembuatan **Pesanan** dan pemrosesan cepat
- Pilihan **Metode Pembayaran**: Tunai, QRIS, E-Wallet
- **Kwitansi** otomatis dan bisa dicetak
- **Riwayat Pemesanan** lengkap dan dapat difilter

## 📦 Manajemen Inventaris

### 🏪 Modul Inventaris
- **Supplier**: Kelola informasi pemasok dengan mudah
- **Inventaris & Ringkasan**: Lihat stok per produk secara real-time
- **Riwayat Pergerakan**: Telusuri keluar/masuk barang
- **Penerimaan Barang**: Catat pembelian dan penambahan stok
- **Penyesuaian Stok**: Update manual untuk koreksi stok fisik

### 📊 Laporan Bisnis
- **Laba Kotor (Gross Profit)**: Lihat profit dari penjualan dikurangi harga pokok
- **Nilai Stok**: Total nilai barang yang tersedia di gudang

---

## � Fitur SaaS & Subscription

Yualan POS dilengkapi dengan sistem **Subscription Management** yang canggih untuk pengalaman SaaS yang lebih baik:

### � Subscription & Billing
- **Paket Berlangganan**: Kelola pricing plan untuk tenant
- **Invoice Otomatis**: Sistem penagihan otomatis untuk SaaS
- **Status Langganan**: Tampilan status subscription di sidebar
- **Trial Days**: Pengaturan masa percobaan untuk tenant baru

### 🎨 User Experience
- **Sidebar Style**: Desain sidebar yang lebih modern dan informatif
- **Login Button**: Warna tombol login yang lebih menarik
- **Discount Pricing**: Perbaikan sistem diskon pada pricing plan

### ⚙️ Pengaturan Tenant
- **Trial Period Settings**: Konfigurasi hari percobaan untuk tenant baru
- **Subscription Status**: Monitor status berlangganan secara real-time
- **Automatic Billing**: Integrasi pembayaran otomatis dengan gateway

## 🤝 Kontribusi & Komunitas

Kami sangat terbuka untuk kolaborasi!  
Silakan bantu kami dengan:
- Menemukan dan memperbaiki bug
- Menambahkan fitur baru
- Meningkatkan dokumentasi

Semua ide dan pull request akan kami tinjau dengan senang hati.

## 🚀 Quick Start

```bash
# Clone repository
git clone https://github.com/Abdurozzaq/Yualan.git
cd Yualan

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate
php artisan db:seed

# Run development server
npm run dev        # Terminal 1
php artisan serve  # Terminal 2
```

Untuk instalasi lengkap, lihat [Installation Guide](docs/installation.md).

## 🛠️ Tech Stack

- **Backend**: Laravel 12.x, PHP 8.2+
- **Frontend**: Vue 3, TypeScript, Inertia.js
- **Styling**: Tailwind CSS, Radix Vue
- **Database**: MySQL/PostgreSQL/SQLite
- **Payment**: iPaymu Gateway Integration
- **Tools**: Vite, Composer, NPM

## 📚 Dokumentasi

Dokumentasi lengkap tersedia di folder `/docs`:

- **[📖 Overview & Setup](docs/README.md)** - Pengenalan dan arsitektur project
- **[🚀 Installation Guide](docs/installation.md)** - Panduan instalasi step-by-step  
- **[👨‍💻 Development Guide](docs/development-guide.md)** - Panduan pengembangan dan kontribusi
- **[🗄️ Database Schema](docs/database-schema.md)** - Struktur database lengkap
- **[☁️ Deployment Guide](docs/deployment.md)** - Panduan deployment production
- **[⚙️ Server Requirements](docs/server-requirement.md)** - Spesifikasi server dan konfigurasi
- **[⏰ Scheduler Guide](docs/scheduler.md)** - Automated tasks dan cron jobs
- **[🔧 Troubleshooting](docs/troubleshooting.md)** - Penyelesaian masalah umum
- **[🤝 Contributing Guide](docs/contributing.md)** - Cara berkontribusi ke project

## 📜 Lisensi

Yualan POS didistribusikan di bawah **GNU GPL v2.0 License**.  
Lihat file `LICENSE` untuk informasi lebih lanjut.

---

**Yualan POS – Yuk Jualan!**
