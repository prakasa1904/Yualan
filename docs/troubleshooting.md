# Troubleshooting Guide

## Overview

Panduan ini membantu mengatasi masalah umum yang mungkin terjadi saat menggunakan atau mengembangkan Yualan POS. Setiap masalah disertai dengan solusi step-by-step.

---

## Installation Issues

### 1. Composer Install Gagal

**Problem:** Error saat menjalankan `composer install`

```bash
# Error message contoh:
Your requirements could not be resolved to an installable set of packages.
```

**Solutions:**

```bash
# Solusi 1: Update composer
composer self-update

# Solusi 2: Clear cache
composer clear-cache
composer install

# Solusi 3: Install dengan memory limit lebih besar
php -d memory_limit=2G composer install

# Solusi 4: Install tanpa dev dependencies
composer install --no-dev --optimize-autoloader
```

### 2. NPM Install Error

**Problem:** Error saat `npm install` atau `npm ci`

**Solutions:**

```bash
# Solusi 1: Clear npm cache
npm cache clean --force
rm -rf node_modules package-lock.json
npm install

# Solusi 2: Use specific Node.js version
nvm install 20
nvm use 20
npm install

# Solusi 3: Increase memory limit
export NODE_OPTIONS="--max_old_space_size=4096"
npm install
```

### 3. Laravel Key Generate Error

**Problem:** `php artisan key:generate` tidak berjalan

**Solutions:**

```bash
# Solusi 1: Pastikan .env file ada
cp .env.example .env

# Solusi 2: Set permission yang benar
chmod 644 .env
php artisan key:generate

# Solusi 3: Generate manual
php -r "echo 'APP_KEY=' . 'base64:' . base64_encode(random_bytes(32)) . \"\n\";"
```

### 4. Database Migration Error

**Problem:** Migration gagal dengan error foreign key

**Solutions:**

```bash
# Solusi 1: Drop semua tabel dan migrate ulang
php artisan migrate:fresh

# Solusi 2: Cek urutan migration files
ls -la database/migrations/

# Solusi 3: Untuk MySQL, disable foreign key checks
mysql -u root -p
SET FOREIGN_KEY_CHECKS=0;
# Kemudian jalankan migration

# Solusi 4: Reset migration
php artisan migrate:reset
php artisan migrate
```

---

## Runtime Errors

### 1. 500 Internal Server Error

**Problem:** Aplikasi menampilkan error 500

**Debug Steps:**

```bash
# 1. Check Laravel logs
tail -f storage/logs/laravel.log

# 2. Enable debug mode sementara
# Edit .env:
APP_DEBUG=true

# 3. Clear all cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 4. Check web server logs
# Apache:
tail -f /var/log/apache2/error.log

# Nginx:
tail -f /var/log/nginx/error.log
```

**Common Solutions:**

```bash
# Permission issues
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Missing APP_KEY
php artisan key:generate

# Database connection issues
php artisan tinker
>>> DB::connection()->getPdo();
```

### 2. CSRF Token Mismatch

**Problem:** Form submission gagal dengan CSRF error

**Solutions:**

```bash
# Solusi 1: Clear session
php artisan session:clear

# Solusi 2: Periksa session config
# .env file:
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_DOMAIN=.yourdomain.com

# Solusi 3: Clear browser cache
# Atau test di incognito mode
```

### 3. Queue Jobs Tidak Berjalan

**Problem:** Background jobs tidak diproses

**Debug Steps:**

```bash
# 1. Check queue connection
# .env file:
QUEUE_CONNECTION=database  # atau redis

# 2. Test job dispatch
php artisan tinker
>>> Illuminate\Support\Facades\Queue::push(new App\Jobs\TestJob());

# 3. Run worker manually
php artisan queue:work --verbose

# 4. Check failed jobs
php artisan queue:failed
```

**Solutions:**

```bash
# Restart queue workers
php artisan queue:restart

# For supervisor:
sudo supervisorctl restart yualan-worker:*

# Clear failed jobs
php artisan queue:flush

# Retry failed jobs
php artisan queue:retry all
```

### 4. Memory Limit Exceeded

**Problem:** PHP memory limit error

**Solutions:**

```bash
# Solusi 1: Increase PHP memory limit
# php.ini:
memory_limit = 512M

# Solusi 2: Untuk specific command
php -d memory_limit=1G artisan command:name

# Solusi 3: Optimize code untuk memory usage
# Gunakan chunking untuk query besar:
Model::chunk(100, function ($items) {
    foreach ($items as $item) {
        // Process item
    }
});
```

---

## Database Issues

### 1. Connection Refused

**Problem:** Database connection error

**Debug Steps:**

```bash
# 1. Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# 2. Check database service
# MySQL:
sudo systemctl status mysql
sudo systemctl start mysql

# PostgreSQL:
sudo systemctl status postgresql
sudo systemctl start postgresql

# 3. Check connection config
# .env file verification
```

**Solutions:**

```bash
# MySQL connection issues
# 1. Reset MySQL password
sudo mysql -u root
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'newpassword';
FLUSH PRIVILEGES;

# 2. Create new database user
CREATE USER 'yualan'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON yualan_pos.* TO 'yualan'@'localhost';
FLUSH PRIVILEGES;

# 3. Check MySQL bind address
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
# Uncomment: bind-address = 127.0.0.1
```

### 2. Migration Rollback Issues

**Problem:** Cannot rollback migration

**Solutions:**

```bash
# 1. Check migration status
php artisan migrate:status

# 2. Force rollback
php artisan migrate:rollback --force

# 3. Reset specific migration
php artisan migrate:rollback --path=database/migrations/2024_01_01_000000_create_table.php

# 4. Fresh migration (WARNING: akan hapus data)
php artisan migrate:fresh
```

### 3. Foreign Key Constraint Error

**Problem:** Error saat insert/update dengan foreign key

**Solutions:**

```php
// 1. Check parent record exists
$parent = ParentModel::find($parentId);
if (!$parent) {
    throw new Exception('Parent record not found');
}

// 2. Disable foreign key checks (MySQL)
DB::statement('SET FOREIGN_KEY_CHECKS=0;');
// Your operations
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

// 3. Use nullable foreign key
Schema::table('table_name', function (Blueprint $table) {
    $table->uuid('parent_id')->nullable()->change();
});
```

---

## Frontend Issues

### 1. Vite Build Errors

**Problem:** `npm run build` atau `npm run dev` error

**Solutions:**

```bash
# 1. Clear cache
rm -rf node_modules package-lock.json
npm install

# 2. Check Node.js version
node --version  # Should be 18.x or 20.x
nvm use 20

# 3. Increase memory limit
export NODE_OPTIONS="--max_old_space_size=4096"
npm run build

# 4. Build without optimization (debug)
npm run dev
```

### 2. TypeScript Errors

**Problem:** TypeScript compilation errors

**Solutions:**

```bash
# 1. Check TypeScript config
npx tsc --noEmit

# 2. Update types
npm update @types/node

# 3. Skip type checking temporarily
# vite.config.ts:
export default defineConfig({
  plugins: [
    vue({
      template: {
        compilerOptions: {
          isCustomElement: (tag) => tag.startsWith('custom-')
        }
      }
    }),
    // Skip TypeScript check
    // typescript({ check: false })
  ]
});
```

### 3. Vue Component Not Rendering

**Problem:** Vue component tidak tampil

**Debug Steps:**

```javascript
// 1. Check console for errors
console.log('Component mounted');

// 2. Verify component registration
import MyComponent from './MyComponent.vue';
console.log(MyComponent); // Should not be undefined

// 3. Check props
const props = defineProps<{
  data: MyData
}>();
console.log('Props:', props);
```

**Solutions:**

```vue
<!-- 1. Add error boundary -->
<template>
  <div>
    <ErrorBoundary>
      <MyComponent :data="data" />
    </ErrorBoundary>
  </div>
</template>

<!-- 2. Add loading state -->
<template>
  <div>
    <div v-if="loading">Loading...</div>
    <MyComponent v-else :data="data" />
  </div>
</template>

<script setup lang="ts">
// 3. Handle async data properly
const { data, pending: loading } = await useFetch('/api/data');
</script>
```

### 4. Inertia.js Issues

**Problem:** Page tidak update atau form tidak submit

**Solutions:**

```javascript
// 1. Clear Inertia cache
// Browser dev tools > Application > Storage > Clear storage

// 2. Check Inertia version compatibility
npm ls @inertiajs/vue3

// 3. Proper form handling
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  name: '',
  email: ''
});

const submit = () => {
  form.post('/submit', {
    onSuccess: () => {
      console.log('Success');
    },
    onError: (errors) => {
      console.log('Errors:', errors);
    }
  });
};
```

---

## Performance Issues

### 1. Slow Page Load

**Problem:** Aplikasi lambat loading

**Debug Steps:**

```bash
# 1. Enable query logging
# AppServiceProvider.php:
if (config('app.debug')) {
    DB::listen(function ($query) {
        logger($query->sql, $query->bindings, $query->time);
    });
}

# 2. Check slow queries
tail -f storage/logs/laravel.log | grep "select"

# 3. Profile dengan Telescope (development)
composer require laravel/telescope --dev
php artisan telescope:install
```

**Solutions:**

```php
// 1. Add database indexes
Schema::table('products', function (Blueprint $table) {
    $table->index(['tenant_id', 'is_active']);
    $table->index('created_at');
});

// 2. Eager loading relationships
$products = Product::with(['category', 'supplier'])->get();

// 3. Use pagination
$products = Product::paginate(15);

// 4. Cache expensive queries
$categories = Cache::remember('categories', 3600, function () {
    return Category::all();
});
```

### 2. High Memory Usage

**Problem:** Aplikasi menggunakan terlalu banyak memory

**Solutions:**

```php
// 1. Use chunking untuk data besar
Product::chunk(100, function ($products) {
    foreach ($products as $product) {
        // Process product
    }
});

// 2. Clear unused variables
unset($largeVariable);

// 3. Use generators
function getProducts() {
    $products = DB::table('products')->cursor();
    foreach ($products as $product) {
        yield $product;
    }
}

// 4. Optimize images
// Resize images before upload
$image = Image::make($file)->resize(800, 600)->save();
```

### 3. Slow Database Queries

**Problem:** Query database lambat

**Solutions:**

```sql
-- 1. Add proper indexes
CREATE INDEX idx_products_tenant_active ON products(tenant_id, is_active);
CREATE INDEX idx_sales_created_at ON sales(created_at);

-- 2. Analyze slow queries (MySQL)
SHOW PROCESSLIST;
EXPLAIN SELECT * FROM products WHERE tenant_id = 'xxx';

-- 3. Optimize queries
-- Bad:
SELECT * FROM products WHERE name LIKE '%search%';

-- Good:
SELECT * FROM products WHERE name LIKE 'search%';
-- Or use full-text search
```

---

## Payment Gateway Issues

### 1. iPaymu Payment Gagal

**Problem:** Pembayaran melalui iPaymu tidak berhasil

**Debug Steps:**

```php
// 1. Check iPaymu credentials
Log::info('iPaymu Config', [
    'api_key' => substr($apiKey, 0, 10) . '***',
    'mode' => $mode
]);

// 2. Log payment request
Log::info('iPaymu Request', $requestData);

// 3. Log response
Log::info('iPaymu Response', $response);
```

**Solutions:**

```php
// 1. Verify credentials
// .env file:
IPAYMU_API_KEY=your_correct_api_key
IPAYMU_SECRET_KEY=your_correct_secret_key
IPAYMU_MODE=sandbox  // atau production

// 2. Check request format
$requestData = [
    'product' => $productName,
    'qty' => 1,
    'price' => $amount,
    'buyerName' => $customerName,
    'buyerEmail' => $customerEmail,
    'buyerPhone' => $customerPhone,
    'referenceId' => $referenceId,
];

// 3. Handle timeout
try {
    $response = Http::timeout(30)->post($url, $data);
} catch (RequestException $e) {
    Log::error('iPaymu timeout', ['error' => $e->getMessage()]);
}
```

### 2. Payment Status Tidak Update

**Problem:** Status pembayaran tidak berubah otomatis

**Solutions:**

```bash
# 1. Check artisan command
php artisan yualan:check-pending-transactions --limit=10

# 2. Verify webhook URL
# URL harus accessible dari internet
curl -X POST https://yourdomain.com/webhook/ipaymu/payment-notification

# 3. Check logs
tail -f storage/logs/laravel.log | grep ipaymu

# 4. Manual status check
php artisan tinker
>>> $payment = App\Models\Payment::find('payment-id');
>>> $service = new App\Services\IpaymuService($payment->tenant);
>>> $status = $service->checkTransaction($payment->transaction_id);
```

---

## Security Issues

### 1. CORS Error

**Problem:** Cross-origin request blocked

**Solutions:**

```php
// 1. Install Laravel CORS
composer require fruitcake/laravel-cors

// 2. Configure CORS
// config/cors.php:
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000', 'https://yourdomain.com'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### 2. Authentication Issues

**Problem:** User tidak bisa login atau session expired

**Solutions:**

```bash
# 1. Clear sessions
php artisan session:clear

# 2. Check session config
# .env:
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true  # hanya untuk HTTPS

# 3. Regenerate key
php artisan key:generate

# 4. Check user password
php artisan tinker
>>> $user = App\Models\User::where('email', 'user@example.com')->first();
>>> Hash::check('password', $user->password);
```

---

## Deployment Issues

### 1. Permission Denied

**Problem:** File permission error di production

**Solutions:**

```bash
# Set proper ownership
sudo chown -R www-data:www-data /var/www/yualan

# Set proper permissions
sudo chmod -R 755 /var/www/yualan
sudo chmod -R 775 /var/www/yualan/storage
sudo chmod -R 775 /var/www/yualan/bootstrap/cache

# For shared hosting
chmod -R 755 public_html/
chmod -R 777 storage/ bootstrap/cache/
```

### 2. SSL Certificate Error

**Problem:** HTTPS tidak berfungsi

**Solutions:**

```bash
# 1. Check certificate
sudo certbot certificates

# 2. Renew certificate
sudo certbot renew

# 3. Test SSL
openssl s_client -connect yourdomain.com:443

# 4. Force HTTPS
# .env:
APP_FORCE_HTTPS=true
```

### 3. Environment Variables Not Loading

**Problem:** Config tidak terbaca di production

**Solutions:**

```bash
# 1. Check .env file exists
ls -la .env

# 2. Clear config cache
php artisan config:clear

# 3. Cache config for production
php artisan config:cache

# 4. Check environment
php artisan env
```

---

## Monitoring & Debugging Tools

### Laravel Telescope (Development Only)

```bash
# Install
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate

# Access: /telescope
```

### Laravel Debugbar (Development Only)

```bash
# Install
composer require barryvdh/laravel-debugbar --dev

# Publish config
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
```

### Log Analysis

```bash
# Real-time log monitoring
tail -f storage/logs/laravel.log

# Search for specific errors
grep "ERROR" storage/logs/laravel-*.log

# Count error occurrences
grep -c "Error" storage/logs/laravel.log

# Filter by date
grep "2024-01-01" storage/logs/laravel.log
```

### Database Query Analysis

```php
// Enable query logging
DB::enableQueryLog();

// Your database operations
User::with('tenant')->get();

// Get queries
$queries = DB::getQueryLog();
foreach ($queries as $query) {
    echo $query['query'] . "\n";
    echo "Time: " . $query['time'] . "ms\n";
}
```

---

## Emergency Procedures

### 1. Site Down - Quick Recovery

```bash
# 1. Put site in maintenance mode
php artisan down --message="Site under maintenance"

# 2. Check critical services
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql

# 3. Check disk space
df -h

# 4. Check error logs
tail -50 storage/logs/laravel.log
tail -50 /var/log/nginx/error.log

# 5. Bring site back up
php artisan up
```

### 2. Database Recovery

```bash
# 1. Stop application
php artisan down

# 2. Backup current database
mysqldump -u root -p yualan_pos > backup_before_recovery.sql

# 3. Restore from backup
mysql -u root -p yualan_pos < backup_clean.sql

# 4. Run migrations if needed
php artisan migrate

# 5. Bring site back up
php artisan up
```

### 3. Cache Clear All

```bash
# Clear all Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Clear OPcache (if using PHP OPcache)
sudo systemctl restart php8.2-fpm

# Clear Redis (if using Redis)
redis-cli FLUSHALL
```

## Getting Help

### Resources

1. **Laravel Documentation**: https://laravel.com/docs
2. **Vue.js Documentation**: https://vuejs.org/guide/
3. **GitHub Issues**: https://github.com/Abdurozzaq/Yualan/issues
4. **Laravel Community**: https://laracasts.com/discuss

### Creating Bug Reports

Saat membuat bug report, sertakan:

1. **Environment info**: PHP version, OS, web server
2. **Steps to reproduce**: Langkah detail untuk reproduce error
3. **Expected vs actual behavior**: Apa yang diharapkan vs yang terjadi
4. **Error logs**: Log dari Laravel dan web server
5. **Screenshots**: Jika applicable

### Performance Profiling

```bash
# Use Xdebug profiling (development)
php -d xdebug.profiler_enable=1 artisan command:name

# Use Laravel Telescope
# Install dan akses /telescope untuk melihat queries, requests, dll

# Monitor resource usage
htop
iotop
nethogs
```

Panduan troubleshooting ini akan terus diupdate seiring berkembangnya project. Jika menemui masalah yang tidak tercakup di sini, silakan buat issue di GitHub repository.
