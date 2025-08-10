# Setup Cron Job untuk Laravel Scheduler

## 1. Di Server Linux/Unix

Buka crontab:
```bash
crontab -e
```

Tambahkan baris ini:
```bash
* * * * * cd /path/to/yualan && php artisan schedule:run >> /dev/null 2>&1
```

Ganti `/path/to/yualan` dengan path absolut ke project Anda.

## 2. Di Server Windows

### Menggunakan Task Scheduler:

1. Buka "Task Scheduler" dari Start Menu
2. Klik "Create Basic Task"
3. Nama: "Laravel Yualan Scheduler"
4. Trigger: "Daily" 
5. Start time: 00:00
6. Repeat task every: 1 minute
7. Action: "Start a program"
8. Program: `C:\php\php.exe` (path ke PHP)
9. Arguments: `artisan schedule:run`
10. Start in: `Q:\PROJECTS\yualan` (path ke project)

### Atau menggunakan PowerShell Script:

Buat file `run-scheduler.ps1`:
```powershell
Set-Location "Q:\PROJECTS\yualan"
php artisan schedule:run
```

Lalu setup Task Scheduler untuk menjalankan PowerShell script ini setiap menit.

## 3. Testing Cron Job

Test manual:
```bash
cd Q:\PROJECTS\yualan
php artisan schedule:run
```

Lihat log:
```bash
php artisan schedule:list
```

## 4. Monitoring

Cek log Laravel:
```bash
tail -f storage/logs/laravel.log
```

Cek apakah command berjalan:
```bash
php artisan yualan:check-pending-transactions --dry-run
```

## 5. Production Tips

- Pastikan PHP CLI tersedia di PATH
- Set proper file permissions
- Monitor disk space untuk log files
- Setup log rotation
- Test cron job setelah deploy
