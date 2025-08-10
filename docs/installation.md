# Panduan Instalasi Yualan POS

## Prasyarat

Pastikan sistem Anda memiliki requirements berikut:

### System Requirements

- **PHP:** 8.2 atau lebih tinggi
- **Composer:** 2.0 atau lebih tinggi  
- **Node.js:** 18.x atau 20.x
- **NPM/Yarn:** Latest version
- **Database:** SQLite (default) atau MySQL/PostgreSQL
- **Web Server:** Apache/Nginx (untuk production)

### PHP Extensions

Pastikan PHP extensions berikut telah terinstall:

```bash
# Ubuntu/Debian
sudo apt install php8.2-curl php8.2-dom php8.2-gd php8.2-intl php8.2-json php8.2-mbstring php8.2-openssl php8.2-pdo php8.2-sqlite3 php8.2-xml php8.2-zip

# CentOS/RHEL
sudo yum install php-curl php-dom php-gd php-intl php-json php-mbstring php-openssl php-pdo php-sqlite php-xml php-zip
```

## Instalasi Development

### 1. Clone Repository

```bash
git clone https://github.com/Abdurozzaq/Yualan.git
cd Yualan
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node.js Dependencies

```bash
npm install
# atau dengan yarn
yarn install
```

### 4. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 5. Database Setup

#### Dengan SQLite (Default)

```bash
# Buat database file
touch database/database.sqlite

# Jalankan migrations
php artisan migrate
```

#### Dengan MySQL/PostgreSQL

Edit `.env` file:

```env
DB_CONNECTION=mysql  # atau pgsql untuk PostgreSQL
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yualan_pos
DB_USERNAME=root
DB_PASSWORD=your_password
```

Kemudian jalankan migrations:

```bash
php artisan migrate
```

### 6. Seed Database (Opsional)

```bash
# Seed dengan data dummy untuk testing
php artisan db:seed
```

### 7. Asset Compilation

#### Development Mode

```bash
npm run dev
```

#### Production Build

```bash
npm run build
```

### 8. Storage Setup

```bash
# Buat symbolic link untuk storage
php artisan storage:link
```

### 9. Queue Configuration (Production)

Untuk production, setup queue worker:

```bash
# Install supervisor (Ubuntu/Debian)
sudo apt install supervisor

# Buat config file supervisor
sudo nano /etc/supervisor/conf.d/yualan-worker.conf
```

Isi config supervisor:

```ini
[program:yualan-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/yualan/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/path/to/yualan/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start yualan-worker:*
```

### 10. Cron Jobs Setup

Tambahkan cron job untuk Laravel scheduler:

```bash
crontab -e
```

Tambahkan baris berikut:

```cron
* * * * * cd /path/to/yualan && php artisan schedule:run >> /dev/null 2>&1
```

## Configuration

### 1. Mail Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@example.com
MAIL_FROM_NAME="Yualan POS"
```

### 2. Cache Configuration (Production)

```env
CACHE_DRIVER=redis  # atau file
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## Menjalankan Aplikasi

### Development Server

```bash
# Terminal 1 - Laravel server
php artisan serve

# Terminal 2 - Vite dev server
npm run dev

# Terminal 3 - Queue worker (opsional untuk development)
php artisan queue:work
```

Akses aplikasi di `http://localhost:8000`

### Production Deployment

Lihat [Panduan Deployment](deployment.md) untuk instruksi lengkap deployment ke production.

## Verifikasi Instalasi

### 1. Test Basic Functionality

```bash
# Test database connection
php artisan tinker
>>> App\Models\User::count()

# Test queue system
php artisan queue:work --once
```

### 2. Test Frontend Build

```bash
npm run build
```

### 3. Check Logs

```bash
tail -f storage/logs/laravel.log
```

## Troubleshooting

### Common Issues

#### 1. Permission Errors

```bash
# Fix permission untuk storage dan bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 755 storage bootstrap/cache
```

#### 2. Node.js Version Issues

```bash
# Install NVM dan gunakan Node.js versi yang tepat
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
nvm install 20
nvm use 20
```

#### 3. Composer Memory Issues

```bash
# Increase PHP memory limit
php -d memory_limit=2G composer install
```

#### 4. SQLite Permission Issues

```bash
# Fix SQLite permissions
sudo chown www-data:www-data database/database.sqlite
sudo chmod 664 database/database.sqlite
sudo chown www-data:www-data database/
```

### Error Logs Location

- Laravel logs: `storage/logs/laravel.log`
- Web server logs: `/var/log/apache2/` atau `/var/log/nginx/`
- PHP-FPM logs: `/var/log/php8.2-fpm.log`

## Development Tools (Opsional)

### Laravel Debugbar

```bash
composer require barryvdh/laravel-debugbar --dev
```

### Laravel Telescope

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### IDE Helper

```bash
composer require barryvdh/laravel-ide-helper --dev
php artisan ide-helper:generate
php artisan ide-helper:models
```

## Next Steps

Setelah instalasi berhasil, silakan lanjutkan ke:

1. [Development Guide](development-guide.md) - Panduan pengembangan
2. [Database Schema](database-schema.md) - Struktur database
3. [API Documentation](api-documentation.md) - Dokumentasi API

## Support

Jika mengalami kesulitan dalam instalasi:

1. Periksa [Troubleshooting Guide](troubleshooting.md)
2. Buka issue di GitHub repository
3. Bergabung dengan komunitas Discord/Telegram
