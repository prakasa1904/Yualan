# Yualan POS - Dokumentasi Project

<img width="1080" height="1080" alt="Yualan POS Logo" src="https://github.com/user-attachments/assets/f4960632-a9a1-4a45-aa05-74e8acd74834" />

## Daftar Isi

1. [Pengenalan](#pengenalan)
2. [Arsitektur Project](#arsitektur-project)
3. [Instalasi & Setup](installation.md)
4. [Panduan Pengembangan](development-guide.md)
6. [Database Schema](database-schema.md)
7. [Deployment](deployment.md)
8. [Troubleshooting](troubleshooting.md)
9. [Kontribusi](contributing.md)

## Pengenalan

**Yualan POS** adalah aplikasi Point of Sale (POS) berbasis web yang dibangun dengan arsitektur SaaS (Software as a Service). Project ini menggunakan teknologi modern seperti Laravel 12, Vue 3, TypeScript, dan Tailwind CSS untuk memberikan pengalaman pengguna yang optimal dan performa yang tinggi.

### Fitur Utama

- 🏪 **Multi-Tenant SaaS Architecture** - Satu sistem untuk banyak toko
- 💰 **Payment Gateway Integration** - Integrasi dengan iPaymu
- 📊 **Manajemen Inventaris** - Kontrol stok real-time
- 🛒 **Point of Sale** - Sistem kasir yang mudah digunakan
- 📈 **Laporan Bisnis** - Analytics dan reporting
- 👥 **Role-Based Access Control** - Manajemen akses berbasis peran
- 📱 **Responsive Design** - Kompatibel dengan desktop dan mobile

### Tech Stack

**Backend:**
- Laravel 12.x
- PHP 8.2+
- SQLite Database (dapat diganti ke PostgreSQL/MySQL)
- UUID-based primary keys
- Queue Jobs untuk background processing

**Frontend:**
- Vue 3 dengan Composition API
- TypeScript untuk type safety
- Inertia.js untuk SPA experience
- Tailwind CSS untuk styling
- Radix Vue untuk UI components
- Vite untuk bundling

**Payment & External Services:**
- iPaymu Payment Gateway
- PDF generation dengan DomPDF

### Struktur Proyek

```
yualan/
├── app/                    # Core aplikasi Laravel
│   ├── Console/           # Artisan commands
│   ├── Http/              # Controllers, Middleware, Requests
│   ├── Models/            # Eloquent models
│   ├── Providers/         # Service providers
│   └── Services/          # Business logic services
├── database/              # Database migrations, seeders, factories
├── resources/             # Frontend assets (Vue components, CSS)
├── routes/                # Route definitions
├── docs/                  # Dokumentasi project (folder ini)
├── public/                # Public assets
├── storage/               # File storage & logs
└── vendor/                # Composer dependencies
```

## Arsitektur Project

### Multi-Tenant Architecture

Yualan POS menggunakan arsitektur multi-tenant dengan pendekatan "single database, multiple schemas". Setiap tenant memiliki:

- **Unique slug** untuk routing (`/tenant/myshop`)
- **Isolated data** melalui tenant_id di setiap tabel
- **Subscription management** dengan pricing plans
- **Custom payment gateway configuration**

### Database Design

- **UUID Primary Keys** - Untuk keamanan dan skalabilitas
- **Soft Deletes** - Untuk audit trail dan data recovery
- **Foreign Key Constraints** - Untuk integritas referensial
- **JSON/JSONB fields** - Untuk data fleksibel seperti gateway responses

### Security Features

- **Role-based access control** (Superadmin, Admin, Kasir)
- **Tenant data isolation** - Data hanya accessible oleh tenant pemilik
- **Secure payment processing** dengan encrypted gateway keys
- **CSRF protection** dan input validation

## Quick Start

Untuk memulai development, silakan baca [Panduan Instalasi](installation.md) dan [Development Guide](development-guide.md).

## Kontribusi

Project ini terbuka untuk kontribusi. Silakan baca [Panduan Kontribusi](contributing.md) untuk detail lebih lanjut.

## Lisensi

Yualan POS didistribusikan di bawah **GNU GPL v2.0 License**. Lihat file `LICENSE` untuk informasi lengkap.

---

**Yualan POS - Yuk Jualan!** 🚀
