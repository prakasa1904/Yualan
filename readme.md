
<img width="1080" height="1080" alt="New Website Blue Mockup Instagram - Laptop" src="https://github.com/user-attachments/assets/f4960632-a9a1-4a45-aa05-74e8acd74834" />

# ✨ Yualan POS – Full Feature Note

**Yualan** adalah aplikasi **Point of Sale (POS)** berbasis **SaaS (Software as a Service)** yang dirancang untuk menyederhanakan dan mengotomatiskan operasional bisnis ritel Anda. Dibangun dengan semangat open source, Yualan kini tersedia di bawah **GNU General Public License v2.0 (GPLv2)** — artinya Anda bebas untuk menggunakan, memodifikasi, dan mendistribusikannya.

---

## 🎯 Tujuan Utama

Yualan hadir sebagai solusi POS ringan namun powerful yang cocok untuk bisnis ritel modern, UMKM, dan pelaku usaha yang ingin memulai sistem penjualan berbasis cloud tanpa biaya lisensi mahal.

---

## 🧩 Fitur Utama

### 1. 👥 **Manajemen Penyewa (Multi-Tenant System)**
- Pengelolaan penyewa/tenant dalam satu sistem POS
- Integrasi pembayaran otomatis dengan gateway seperti **iPaymu**

### 2. 🔐 **Akses Berbasis Peran**
- **Superadmin**: Mengelola sistem dan semua penyewa
- **Admin (Pemilik Toko)**: Mengelola toko dan data bisnis mereka
- **Kasir**: Akses terbatas untuk penjualan & transaksi saja

### 3. 🗃️ **Data Master**
- **Produk**: SKU, kategori, harga, dan stok
- **Pelanggan**: Data kontak, histori transaksi
- **Kategori Produk**: Kelompokkan item dengan mudah

### 4. 💵 **Alur Transaksi yang Efisien**
- Pembuatan **Pesanan** dan pemrosesan cepat
- Pilihan **Metode Pembayaran**: Tunai, QRIS, E-Wallet
- **Kwitansi** otomatis dan bisa dicetak
- **Riwayat Pemesanan** lengkap dan dapat difilter

---

## 🆕 Fitur Inventaris Terbaru (NEW!)

Kini Yualan POS semakin lengkap dengan modul **Manajemen Inventaris** dan **Laporan Laba**:

### 📦 **Modul Inventaris Lengkap**
- **Supplier**: Kelola informasi pemasok dengan mudah
- **Inventaris & Ringkasan**: Lihat stok per produk secara real-time
- **Riwayat Pergerakan**: Telusuri keluar/masuk barang
- **Penerimaan Barang**: Catat pembelian dan penambahan stok
- **Penyesuaian Stok**: Update manual untuk koreksi stok fisik

### 📊 **Modul Laporan Bisnis**
- **Laba Kotor (Gross Profit)**: Lihat profit dari penjualan dikurangi harga pokok
- **Nilai Stok**: Total nilai barang yang tersedia di gudang

---

## 🚀 Fitur SaaS & Subscription Terbaru (LATEST!)

Yualan POS kini dilengkapi dengan sistem **Subscription Management** yang canggih untuk pengalaman SaaS yang lebih baik:

### 💳 **Sistem Subscription & Billing**
- **Paket Berlangganan**: Kelola pricing plan untuk tenant
- **Invoice Otomatis**: Sistem penagihan otomatis untuk SaaS
- **Status Langganan**: Tampilan status subscription di sidebar
- **Trial Days**: Pengaturan masa percobaan untuk tenant baru

### 🎨 **Update User Experience**
- **Sidebar Style**: Desain sidebar yang lebih modern dan informatif
- **Login Button**: Warna tombol login yang lebih menarik
- **Discount Pricing**: Perbaikan sistem diskon pada pricing plan

### ⚙️ **Pengaturan Tenant**
- **Trial Period Settings**: Konfigurasi hari percobaan untuk tenant baru
- **Subscription Status**: Monitor status berlangganan secara real-time
- **Automatic Billing**: Integrasi pembayaran otomatis dengan gateway

---

## 🤝 Kontribusi & Komunitas

Kami sangat terbuka untuk kolaborasi!  
Silakan bantu kami dengan:
- Menemukan dan memperbaiki bug
- Menambahkan fitur baru
- Meningkatkan dokumentasi

Semua ide dan pull request akan kami tinjau dengan senang hati.

---

## 📜 Lisensi

Yualan POS didistribusikan di bawah **GNU GPL v2.0 License**.  
Lihat file `LICENSE` untuk informasi lebih lanjut.

---

## 💬 Yuk Jualan dengan Yualan!

Mulailah perjalanan digitalisasi bisnis Anda bersama Yualan.  
Solusi POS open-source yang ringan, fleksibel, dan siap berkembang bersama komunitas.

---

## 📚 Dokumentasi Lengkap

Dokumentasi komprehensif tersedia di folder `/docs`:

- **[📖 Overview & Setup](docs/README.md)** - Pengenalan dan arsitektur project
- **[🚀 Installation Guide](docs/installation.md)** - Panduan instalasi step-by-step  
- **[👨‍💻 Development Guide](docs/development-guide.md)** - Panduan pengembangan dan kontribusi
- **[🗄️ Database Schema](docs/database-schema.md)** - Struktur database lengkap
- **[🔌 API Documentation](docs/api-documentation.md)** - Dokumentasi REST API
- **[☁️ Deployment Guide](docs/deployment.md)** - Panduan deployment production
- **[⚙️ Server Requirements](docs/server-requirement.md)** - Spesifikasi server dan konfigurasi
- **[⏰ Scheduler Guide](docs/scheduler.md)** - Automated tasks dan cron jobs
- **[🔧 Troubleshooting](docs/troubleshooting.md)** - Penyelesaian masalah umum
- **[🤝 Contributing Guide](docs/contributing.md)** - Cara berkontribusi ke project

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

**Yualan POS – Yuk Jualan!**