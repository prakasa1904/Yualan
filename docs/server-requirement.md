# Server Requirements

## Minimum System Requirements

### Operating System
- **Linux**: Ubuntu 20.04 LTS, CentOS 8+, RHEL 8+, Debian 10+
- **Windows**: Windows Server 2019+, Windows 10/11 (untuk development)
- **macOS**: macOS 11.0+ (untuk development)

### Hardware Requirements

#### Development Environment
- **RAM**: 4GB minimum (8GB recommended)
- **Storage**: 2GB free space
- **CPU**: Dual-core processor
- **Network**: Stable internet connection

#### Production Environment

**Small Scale (1-5 tenants, <100 transactions/day)**
- **RAM**: 2GB minimum (4GB recommended)
- **Storage**: 20GB SSD
- **CPU**: 2 vCPU
- **Bandwidth**: 100GB/month

**Medium Scale (5-25 tenants, 100-1000 transactions/day)**
- **RAM**: 4GB minimum (8GB recommended)  
- **Storage**: 50GB SSD
- **CPU**: 4 vCPU
- **Bandwidth**: 500GB/month

**Large Scale (25+ tenants, 1000+ transactions/day)**
- **RAM**: 8GB minimum (16GB recommended)
- **Storage**: 100GB+ SSD
- **CPU**: 8+ vCPU
- **Bandwidth**: 1TB/month
- **Load Balancer**: Recommended
- **Database**: Separate database server

---

## Software Requirements

### PHP Requirements

**PHP Version**: 8.2 atau lebih tinggi

**Required PHP Extensions:**
```bash
# Core extensions
php-cli
php-fpm
php-json
php-openssl
php-pdo
php-mbstring
php-tokenizer
php-xml
php-ctype
php-iconv
php-bcmath

# Database extensions (pilih salah satu atau lebih)
php-mysql        # untuk MySQL/MariaDB
php-pgsql        # untuk PostgreSQL  
php-sqlite3      # untuk SQLite

# Optional extensions (recommended)
php-curl         # untuk HTTP requests
php-gd           # untuk image processing
php-intl         # untuk internationalization
php-zip          # untuk file compression
php-redis        # untuk Redis cache
php-imagick      # untuk advanced image processing
```

**PHP Configuration Requirements:**
```ini
# php.ini settings
memory_limit = 512M              # minimum 256M
max_execution_time = 300         # untuk import besar
max_input_vars = 3000           # untuk form kompleks
upload_max_filesize = 20M       # untuk upload gambar
post_max_size = 20M             # harus >= upload_max_filesize
max_file_uploads = 20           # untuk multiple uploads

# Security settings
display_errors = Off            # HARUS Off di production
log_errors = On                 # untuk debugging
expose_php = Off               # security
allow_url_fopen = Off          # security
allow_url_include = Off        # security

# OPcache (recommended untuk production)
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.validate_timestamps = 0  # production only
opcache.save_comments = 1
```

### Web Server Requirements

#### Apache 2.4+

**Required Modules:**
```apache
# Enable required modules
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers
sudo a2enmod deflate
sudo a2enmod expires
```

**Virtual Host Configuration:**
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/yualan/public
    
    <Directory /var/www/yualan/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    Header always set X-Frame-Options DENY
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"
    
    # Compression
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/plain
        AddOutputFilterByType DEFLATE text/html
        AddOutputFilterByType DEFLATE text/xml
        AddOutputFilterByType DEFLATE text/css
        AddOutputFilterByType DEFLATE application/xml
        AddOutputFilterByType DEFLATE application/xhtml+xml
        AddOutputFilterByType DEFLATE application/rss+xml
        AddOutputFilterByType DEFLATE application/javascript
        AddOutputFilterByType DEFLATE application/x-javascript
    </IfModule>
    
    ErrorLog ${APACHE_LOG_DIR}/yualan_error.log
    CustomLog ${APACHE_LOG_DIR}/yualan_access.log combined
</VirtualHost>
```

#### Nginx 1.18+

**Nginx Configuration:**
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/yualan/public;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    index index.php index.html index.htm;
    charset utf-8;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/x-javascript
        application/xml+rss
        application/json;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { 
        access_log off; 
        log_not_found off; 
    }
    
    location = /robots.txt { 
        access_log off; 
        log_not_found off; 
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    # Cache static assets
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
    
    # Security
    location ~ /\. {
        deny all;
    }
    
    # Increase upload limits
    client_max_body_size 20M;
    client_body_timeout 60s;
    client_header_timeout 60s;
}
```

### Database Requirements

#### MySQL 8.0+ / MariaDB 10.4+

**Configuration (my.cnf):**
```ini
[mysqld]
# Basic settings
default-storage-engine = InnoDB
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Performance tuning
innodb_buffer_pool_size = 1G        # 70-80% dari available RAM
innodb_log_file_size = 256M
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1

# Connection limits
max_connections = 200
wait_timeout = 28800
interactive_timeout = 28800

# Binary logging (untuk replication)
server-id = 1
log-bin = mysql-bin
binlog-format = ROW
expire_logs_days = 7

# Slow query log
slow-query-log = 1
slow-query-log-file = /var/log/mysql/slow.log
long_query_time = 2
```

**Required Permissions:**
```sql
-- Create database dan user
CREATE DATABASE yualan_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'yualan'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON yualan_pos.* TO 'yualan'@'localhost';

-- Additional permissions untuk advanced features
GRANT PROCESS ON *.* TO 'yualan'@'localhost';
GRANT SHOW VIEW ON yualan_pos.* TO 'yualan'@'localhost';
FLUSH PRIVILEGES;
```

#### PostgreSQL 13+

**Configuration (postgresql.conf):**
```ini
# Memory settings
shared_buffers = 256MB              # 25% dari RAM
effective_cache_size = 1GB          # 75% dari RAM
work_mem = 4MB                      # untuk sorting
maintenance_work_mem = 64MB

# Connection settings
max_connections = 200
listen_addresses = 'localhost'

# WAL settings
wal_buffers = 16MB
checkpoint_completion_target = 0.9
wal_writer_delay = 200ms

# Logging
log_destination = 'stderr'
log_min_duration_statement = 2000   # log queries > 2s
log_line_prefix = '%t [%p]: [%l-1] user=%u,db=%d,app=%a,client=%h '
```

#### SQLite 3.35+

**Recommended untuk:**
- Development environment
- Small scale deployment (1-2 tenants)
- Demo/testing purposes

**Not recommended untuk:**
- Production dengan multiple concurrent users
- High transaction volume
- Multiple server deployment

### Node.js Requirements

**Node.js Version**: 18.x atau 20.x (LTS versions)

**Global Packages:**
```bash
npm install -g npm@latest          # Update NPM
npm install -g @vue/cli            # Vue CLI (optional)
npm install -g typescript          # TypeScript compiler
```

**System Dependencies:**
```bash
# Ubuntu/Debian
sudo apt install -y build-essential python3

# CentOS/RHEL
sudo yum groupinstall -y "Development Tools"
sudo yum install -y python3

# untuk node-gyp compilation
```

---

## Additional Services

### Redis (Recommended)

**Version**: Redis 6.0+

**Use Cases:**
- Session storage
- Cache storage  
- Queue driver
- Rate limiting

**Configuration (redis.conf):**
```ini
# Memory management
maxmemory 512mb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000

# Security
requirepass your_redis_password
bind 127.0.0.1

# Logging
loglevel notice
logfile /var/log/redis/redis-server.log
```

### Supervisor (Production)

**Version**: Supervisor 4.0+

**Use Cases:**
- Queue worker management
- Process monitoring
- Auto-restart failed processes

**Configuration:**
```ini
[program:yualan-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/yualan/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/yualan/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
```

### SSL Certificate

**Requirements:**
- SSL certificate untuk HTTPS
- TLS 1.2+ support
- Strong cipher suites

**Options:**
- Let's Encrypt (free)
- Commercial SSL certificate
- Cloudflare SSL

---

## Security Requirements

### Firewall Configuration

**UFW Example (Ubuntu):**
```bash
# Basic firewall rules
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Allow SSH (change port if custom)
sudo ufw allow 22/tcp

# Allow HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Allow specific IPs for database (if external)
sudo ufw allow from 10.0.0.0/8 to any port 3306

# Enable firewall
sudo ufw enable
```

### File Permissions

**Recommended Permissions:**
```bash
# Application files
sudo chown -R www-data:www-data /var/www/yualan
sudo chmod -R 755 /var/www/yualan

# Writable directories
sudo chmod -R 775 /var/www/yualan/storage
sudo chmod -R 775 /var/www/yualan/bootstrap/cache

# Configuration files
sudo chmod 600 /var/www/yualan/.env
sudo chmod 644 /var/www/yualan/config/*.php
```

### Security Headers

**Required Security Headers:**
```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: default-src 'self'
Referrer-Policy: strict-origin-when-cross-origin
```

---

## Performance Recommendations

### PHP-FPM Tuning

**Production Configuration:**
```ini
; /etc/php/8.2/fpm/pool.d/www.conf

; Process manager settings
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 15
pm.max_requests = 500

; Memory settings
php_admin_value[memory_limit] = 512M

; Slow log
slowlog = /var/log/php8.2-fpm.slow.log
request_slowlog_timeout = 5s

; Security
php_admin_flag[allow_url_fopen] = off
```

### Database Optimization

**MySQL/MariaDB Indexes:**
```sql
-- Performance indexes untuk tables utama
CREATE INDEX idx_products_tenant_active ON products(tenant_id, is_active);
CREATE INDEX idx_sales_tenant_created ON sales(tenant_id, created_at);
CREATE INDEX idx_sales_status ON sales(status);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_inventories_tenant_product ON inventories(tenant_id, product_id);
CREATE INDEX idx_categories_tenant ON categories(tenant_id);
```

### Caching Strategy

**Recommended Caching:**
```php
// Config cache (production only)
php artisan config:cache

// Route cache (production only)  
php artisan route:cache

// View cache
php artisan view:cache

// Event cache
php artisan event:cache

// Redis cache untuk data
Cache::remember('categories', 3600, function () {
    return Category::all();
});
```

---

## Monitoring Requirements

### Log Files

**Required Log Monitoring:**
```bash
# Laravel logs
/var/www/yualan/storage/logs/laravel.log

# Web server logs
/var/log/nginx/access.log
/var/log/nginx/error.log

# PHP-FPM logs
/var/log/php8.2-fpm.log

# Database logs
/var/log/mysql/error.log
/var/log/mysql/slow.log

# System logs
/var/log/syslog
/var/log/auth.log
```

### Health Checks

**Endpoint Monitoring:**
```bash
# Application health
curl https://yourdomain.com/health

# Database connectivity
curl https://yourdomain.com/api/health/database

# Queue status
curl https://yourdomain.com/api/health/queue
```

### Resource Monitoring

**Key Metrics to Monitor:**
- CPU usage
- Memory usage  
- Disk space
- Network I/O
- Database connections
- Response time
- Error rates
- Queue job processing

**Tools:**
- Htop
- Iotop
- Netstat
- New Relic (optional)
- DataDog (optional)

---

## Backup Requirements

### Database Backup

**Daily Backup Script:**
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/yualan"

# Create backup directory
mkdir -p $BACKUP_DIR

# MySQL backup
mysqldump -u yualan -p'password' yualan_pos | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Keep only last 30 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +30 -delete

# Send to cloud storage (optional)
# aws s3 cp $BACKUP_DIR/db_$DATE.sql.gz s3://your-backup-bucket/
```

### File Backup

**Files to Backup:**
- Application files (`/var/www/yualan`)
- Uploaded files (`storage/app/public`)
- Configuration files (`.env`)
- SSL certificates
- Database backups

---

## Cloud Platform Specifications

### AWS EC2

**Recommended Instances:**
- **t3.micro**: Testing (1 vCPU, 1GB RAM)
- **t3.small**: Small production (2 vCPU, 2GB RAM)
- **t3.medium**: Medium production (2 vCPU, 4GB RAM)
- **c5.large**: High performance (2 vCPU, 4GB RAM)

### DigitalOcean Droplets

**Recommended Sizes:**
- **Basic 2GB**: Small production ($12/month)
- **Basic 4GB**: Medium production ($24/month)
- **General Purpose 4GB**: High performance ($48/month)

### Google Cloud Platform

**Recommended Machine Types:**
- **e2-small**: Testing (2 vCPU, 2GB RAM)
- **e2-medium**: Small production (2 vCPU, 4GB RAM)
- **n2-standard-2**: Medium production (2 vCPU, 8GB RAM)

---

## Troubleshooting Common Issues

### PHP Issues

```bash
# Check PHP version
php -v

# Check loaded extensions
php -m | grep -i extension_name

# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Check PHP-FPM logs
sudo tail -f /var/log/php8.2-fpm.log
```

### Database Connection Issues

```bash
# Test MySQL connection
mysql -u yualan -p -h localhost yualan_pos

# Check MySQL status
sudo systemctl status mysql

# Check MySQL logs
sudo tail -f /var/log/mysql/error.log
```

### Web Server Issues

```bash
# Check Nginx status
sudo systemctl status nginx

# Test Nginx configuration
sudo nginx -t

# Check Apache status  
sudo systemctl status apache2

# Test Apache configuration
sudo apache2ctl configtest
```

Panduan ini mencakup semua requirement yang diperlukan untuk menjalankan Yualan POS di berbagai environment. Sesuaikan konfigurasi berdasarkan skala dan kebutuhan spesifik deployment Anda.