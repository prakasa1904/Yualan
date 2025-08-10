# Scheduler Guide - Automated Tasks

## Overview

Yualan POS menggunakan Laravel Scheduler untuk menjalankan tugas-tugas otomatis yang penting bagi operasional sistem. Dokumen ini menjelaskan semua scheduled tasks, cara konfigurasi, dan monitoring.

## Configured Scheduled Tasks

### 1. Tenant Subscription Status Update

**Command:** `tenant:update-subscription-status`
**Schedule:** Daily (setiap hari pada pukul 00:00)
**Purpose:** Mengupdate status langganan tenant yang sudah expire

```php
// app/Console/Kernel.php
$schedule->command('tenant:update-subscription-status')->daily();
```

**What it does:**
- Mencari tenant dengan `is_subscribed = true`
- Mengecek apakah `subscription_ends_at` sudah lewat
- Mengupdate `is_subscribed = false` untuk tenant yang expire
- Log jumlah tenant yang diupdate

**Impact:**
- Tenant yang expire tidak bisa mengakses sistem
- Admin akan mendapat notifikasi untuk perpanjang langganan

### 2. Pending Transaction Status Check

**Command:** `yualan:check-pending-transactions`
**Schedule:** Multiple intervals
- Setiap 5 menit (untuk transaksi 24 jam terakhir)
- Setiap jam (untuk transaksi 72 jam terakhir)

```php
// Setiap 5 menit - cek transaksi fresh
$schedule->command('yualan:check-pending-transactions --limit=100 --hours=24')
        ->everyFiveMinutes()
        ->withoutOverlapping(10)
        ->runInBackground();

// Setiap jam - cek transaksi lebih lama
$schedule->command('yualan:check-pending-transactions --limit=200 --hours=72')
        ->hourly()
        ->withoutOverlapping(15)
        ->runInBackground();
```

**What it does:**
- Mengecek status transaksi iPaymu yang masih pending/failed
- Mengupdate status transaksi berdasarkan response dari iPaymu
- Memproses inventory movement untuk transaksi yang berhasil
- Mencatat log perubahan status

**Parameters:**
- `--limit`: Jumlah maksimal transaksi yang dicek per run
- `--hours`: Batasan waktu transaksi yang dicek (dalam jam)

## Setup Cron Job

### Linux/Ubuntu Server

```bash
# Edit crontab untuk www-data user
sudo crontab -e -u www-data

# Tambahkan baris berikut:
* * * * * cd /var/www/yualan && php artisan schedule:run >> /dev/null 2>&1
```

### Alternative untuk Root User

```bash
# Edit root crontab
sudo crontab -e

# Tambahkan baris berikut:
* * * * * cd /var/www/yualan && sudo -u www-data php artisan schedule:run >> /dev/null 2>&1
```

### Verify Cron Job Setup

```bash
# Check if cron service running
sudo systemctl status cron

# List cron jobs untuk www-data
sudo crontab -l -u www-data

# Check cron logs
sudo tail -f /var/log/cron.log
```

## Command Details

### tenant:update-subscription-status

**File:** `app/Console/Commands/UpdateTenantSubscriptionStatus.php`

```bash
# Manual execution
php artisan tenant:update-subscription-status

# Output example:
# Updated 3 tenants' subscription status.
```

**Logic Flow:**
1. Get current timestamp
2. Query tenants where:
   - `is_subscribed = true`
   - `subscription_ends_at IS NOT NULL`
   - `subscription_ends_at < now()`
3. Update `is_subscribed = false`
4. Return count of updated records

### yualan:check-pending-transactions

**File:** `app/Console/Commands/CheckPendingTransactions.php`

```bash
# Manual execution examples:
php artisan yualan:check-pending-transactions
php artisan yualan:check-pending-transactions --limit=50
php artisan yualan:check-pending-transactions --limit=100 --hours=48
```

**Options:**
- `--limit=50`: Limit jumlah transaksi (default: 50)
- `--hours=24`: Hanya cek transaksi dalam X jam terakhir (default: 24)

**Logic Flow:**
1. Query pending/failed sales dengan payment method 'ipaymu'
2. Filter berdasarkan waktu (--hours parameter)
3. Untuk setiap transaksi:
   - Cek apakah ada transaction_id
   - Jika ada, langsung cek status ke iPaymu
   - Jika tidak ada, cari via referenceId
   - Update status berdasarkan response iPaymu
   - Log inventory movements jika status completed

**Return Values:**
- `completed`: Status diupdate ke completed
- `failed`: Status diupdate ke failed  
- `unchanged`: Status tidak berubah

## Monitoring Scheduled Tasks

### Laravel Telescope (Development)

```bash
# Install Telescope
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate

# Access: /telescope
# Go to Schedule tab untuk melihat scheduled tasks
```

### Log Monitoring

```bash
# Monitor Laravel logs
tail -f storage/logs/laravel.log | grep -i schedule

# Monitor specific command logs
tail -f storage/logs/laravel.log | grep "check-pending-transactions"

# Monitor cron logs
sudo tail -f /var/log/cron.log
```

### Create Custom Monitoring

**Create monitoring endpoint:**

```php
// routes/web.php
Route::get('/scheduler/status', function () {
    return response()->json([
        'last_schedule_run' => cache('schedule:last_run'),
        'pending_transactions_checked' => cache('pending_transactions:last_check'),
        'tenant_subscriptions_updated' => cache('tenant_subscriptions:last_update'),
    ]);
});
```

**Update commands to cache timestamp:**

```php
// Dalam command handle() method
public function handle()
{
    $result = $this->executeTask();
    
    // Cache last execution time
    cache(['command_name:last_run' => now()], 3600);
    
    return $result;
}
```

## Performance Considerations

### Command Optimization

```php
// app/Console/Commands/CheckPendingTransactions.php

// Use chunking untuk memory efficiency
Sale::where('payment_method', 'ipaymu')
    ->whereIn('status', ['pending', 'failed'])
    ->where('created_at', '>=', now()->subHours($hours))
    ->chunk(50, function ($sales) {
        foreach ($sales as $sale) {
            $this->processSale($sale);
        }
    });
```

### Prevent Overlapping

```php
// app/Console/Kernel.php
$schedule->command('yualan:check-pending-transactions')
        ->everyFiveMinutes()
        ->withoutOverlapping(10); // Prevent overlap, max 10 minutes
```

### Background Execution

```php
$schedule->command('yualan:check-pending-transactions')
        ->everyFiveMinutes()
        ->runInBackground(); // Run in background
```

## Error Handling

### Command Error Handling

```php
public function handle()
{
    try {
        $this->info('Starting command execution...');
        
        $result = $this->executeMainLogic();
        
        $this->info("Command completed successfully. Result: {$result}");
        
        return 0; // Success
        
    } catch (\Exception $e) {
        $this->error("Command failed: " . $e->getMessage());
        
        Log::error("Command error", [
            'command' => $this->signature,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return 1; // Failure
    }
}
```

### Notification on Failure

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('yualan:check-pending-transactions')
            ->everyFiveMinutes()
            ->onFailure(function () {
                // Send notification to admin
                Notification::route('mail', 'admin@example.com')
                           ->notify(new CommandFailedNotification('check-pending-transactions'));
            });
}
```

## Debugging Scheduled Tasks

### Test Schedule Manually

```bash
# Run scheduler once to see what would be executed
php artisan schedule:list

# Run scheduler in foreground to see output
php artisan schedule:run

# Test specific time (useful for testing daily/weekly commands)
php artisan schedule:test
```

### Enable Debug Mode

```bash
# Edit .env temporarily for debugging
APP_DEBUG=true
LOG_LEVEL=debug

# Run specific command with verbose output
php artisan yualan:check-pending-transactions --limit=5 -v
```

### Schedule Output Logging

```php
// app/Console/Kernel.php
$schedule->command('tenant:update-subscription-status')
        ->daily()
        ->sendOutputTo(storage_path('logs/tenant-subscription.log'))
        ->emailOutputOnFailure('admin@example.com');
```

## Best Practices

### 1. Idempotent Commands

Pastikan commands dapat dijalankan multiple times tanpa efek samping:

```php
// Bad - could create duplicates
$this->createInventoryMovement($saleItem);

// Good - check if already exists
if (!$this->inventoryMovementExists($saleItem)) {
    $this->createInventoryMovement($saleItem);
}
```

### 2. Batch Processing

Process data dalam batches untuk menghindari memory issues:

```php
$totalProcessed = 0;
$batchSize = 100;

do {
    $sales = Sale::where('status', 'pending')
                ->limit($batchSize)
                ->get();
    
    foreach ($sales as $sale) {
        $this->processSale($sale);
        $totalProcessed++;
    }
    
    // Clear memory
    unset($sales);
    
} while ($sales->count() === $batchSize);
```

### 3. Progress Reporting

```php
use Symfony\Component\Console\Helper\ProgressBar;

public function handle()
{
    $sales = $this->getPendingSales();
    $progressBar = $this->output->createProgressBar($sales->count());
    
    foreach ($sales as $sale) {
        $this->processSale($sale);
        $progressBar->advance();
    }
    
    $progressBar->finish();
}
```

## Custom Scheduled Tasks

### Creating New Scheduled Task

```bash
# Generate command
php artisan make:command SendWeeklyReports --command=reports:weekly
```

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendWeeklyReports extends Command
{
    protected $signature = 'reports:weekly {--tenant=* : Specific tenant IDs}';
    protected $description = 'Send weekly sales reports to tenants';

    public function handle()
    {
        $tenantIds = $this->option('tenant');
        
        if (empty($tenantIds)) {
            $tenants = Tenant::where('is_active', true)->get();
        } else {
            $tenants = Tenant::whereIn('id', $tenantIds)->get();
        }
        
        foreach ($tenants as $tenant) {
            $this->generateWeeklyReport($tenant);
        }
        
        $this->info("Weekly reports sent to {$tenants->count()} tenants");
    }
    
    private function generateWeeklyReport($tenant)
    {
        // Generate and send report logic
    }
}
```

### Register in Kernel

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Existing schedules...
    
    // New weekly reports
    $schedule->command('reports:weekly')
            ->weekly()
            ->sundays()
            ->at('08:00')
            ->timezone('Asia/Jakarta');
}
```

## Production Deployment

### Supervisor Configuration

Untuk production yang menggunakan supervisor untuk queue workers, tambahkan monitoring untuk scheduler:

```ini
# /etc/supervisor/conf.d/yualan-scheduler.conf
[program:yualan-scheduler]
process_name=%(program_name)s
command=/bin/bash -c 'while [ true ]; do php /var/www/yualan/artisan schedule:run; sleep 60; done'
directory=/var/www/yualan
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/yualan/storage/logs/scheduler.log
```

**Note:** Approach ini tidak recommended. Lebih baik gunakan cron job standard.

### Health Check Endpoint

```php
// routes/web.php
Route::get('/health/scheduler', function () {
    $lastRun = cache('schedule:last_run');
    $isHealthy = $lastRun && $lastRun->diffInMinutes(now()) < 5;
    
    return response()->json([
        'status' => $isHealthy ? 'healthy' : 'unhealthy',
        'last_run' => $lastRun?->format('Y-m-d H:i:s'),
        'minutes_since_last_run' => $lastRun?->diffInMinutes(now()),
    ], $isHealthy ? 200 : 503);
});
```

## Troubleshooting Common Issues

### 1. Cron Job Not Running

```bash
# Check if cron service is running
sudo systemctl status cron

# Check cron logs for errors
sudo tail -f /var/log/cron.log

# Check if user has permission
ls -la /var/www/yualan/artisan
```

### 2. Commands Failing Silently

```bash
# Run manually to see errors
cd /var/www/yualan
sudo -u www-data php artisan schedule:run

# Check Laravel logs
tail -f storage/logs/laravel.log
```

### 3. Memory Limit Issues

```bash
# Increase memory limit for command
php -d memory_limit=512M artisan command:name

# Or set in command itself
ini_set('memory_limit', '512M');
```

### 4. Database Connection Issues

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check database service
sudo systemctl status mysql
```

Scheduler adalah komponen critical untuk operasional Yualan POS. Pastikan untuk monitor dan maintain dengan baik untuk menjamin kelancaran operasional sistem.
