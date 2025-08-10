# Database Schema Documentation

## Overview

Yualan POS menggunakan database schema yang dirancang untuk mendukung arsitektur multi-tenant dengan UUID sebagai primary key. Semua tabel utama memiliki relasi dengan `tenants` table untuk memastikan isolasi data antar tenant.

## Database Tables

### Core Tables

#### 1. tenants
Tabel utama untuk menyimpan informasi tenant/penyewa sistem.

```sql
CREATE TABLE tenants (
    id UUID PRIMARY KEY,
    name VARCHAR NOT NULL,
    invitation_code VARCHAR UNIQUE NOT NULL,
    slug VARCHAR UNIQUE NOT NULL,
    email VARCHAR UNIQUE NOT NULL,
    phone VARCHAR NULL,
    address TEXT NULL,
    city VARCHAR NULL,
    state VARCHAR NULL,
    zip_code VARCHAR NULL,
    country VARCHAR NULL,
    business_type VARCHAR NOT NULL COMMENT 'store, restaurant, minimarket',
    is_active BOOLEAN DEFAULT true,
    pricing_plan_id UUID NULL,
    subscription_ends_at TIMESTAMP NULL,
    last_transaction_id VARCHAR NULL,
    is_subscribed BOOLEAN DEFAULT false,
    ipaymu_api_key VARCHAR NULL,
    ipaymu_secret_key VARCHAR NULL,
    ipaymu_mode VARCHAR DEFAULT 'sandbox',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

**Relationships:**
- Has many: `users`, `categories`, `products`, `customers`, `sales`, `payments`, `inventories`, `suppliers`
- Belongs to: `pricing_plans`

#### 2. users
Tabel untuk menyimpan informasi user (admin, kasir) yang terhubung dengan tenant.

```sql
CREATE TABLE users (
    id UUID PRIMARY KEY,
    tenant_id UUID NULL,
    name VARCHAR NOT NULL,
    email VARCHAR UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR NOT NULL,
    role VARCHAR DEFAULT 'cashier' COMMENT 'admin, cashier, manager',
    remember_token VARCHAR NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Relationships:**
- Belongs to: `tenant`
- Has many: `sales`

### Product Management

#### 3. categories
Tabel untuk kategori produk.

```sql
CREATE TABLE categories (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    name VARCHAR NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE (tenant_id, name)
);
```

**Relationships:**
- Belongs to: `tenant`
- Has many: `products`

#### 4. products
Tabel untuk produk/item yang dijual.

```sql
CREATE TABLE products (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    category_id UUID NULL,
    name VARCHAR NOT NULL,
    sku VARCHAR UNIQUE NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INTEGER DEFAULT 0,
    unit VARCHAR NULL COMMENT 'pcs, kg, liter',
    image VARCHAR NULL,
    cost_price DECIMAL(10,2) DEFAULT 0.00,
    low_stock_threshold INTEGER DEFAULT 10,
    is_active BOOLEAN DEFAULT true,
    is_food_item BOOLEAN DEFAULT false,
    ingredients TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);
```

**Relationships:**
- Belongs to: `tenant`, `category`
- Has many: `sale_items`, `inventories`

#### 5. suppliers
Tabel untuk informasi supplier/pemasok.

```sql
CREATE TABLE suppliers (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    name VARCHAR NOT NULL,
    contact_person VARCHAR NULL,
    phone VARCHAR NULL,
    email VARCHAR NULL,
    address TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Relationships:**
- Belongs to: `tenant`

### Customer Management

#### 6. customers
Tabel untuk data pelanggan.

```sql
CREATE TABLE customers (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    name VARCHAR NOT NULL,
    email VARCHAR NULL,
    phone VARCHAR NULL,
    address TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Relationships:**
- Belongs to: `tenant`
- Has many: `sales`

### Sales & Transactions

#### 7. sales
Tabel untuk transaksi penjualan.

```sql
CREATE TABLE sales (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    user_id UUID NOT NULL,
    customer_id UUID NULL,
    invoice_number VARCHAR UNIQUE NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    paid_amount DECIMAL(12,2) DEFAULT 0,
    change_amount DECIMAL(10,2) DEFAULT 0,
    payment_method VARCHAR NOT NULL COMMENT 'cash, card, ipaymu',
    status VARCHAR DEFAULT 'completed' COMMENT 'completed, pending, cancelled, refunded',
    notes TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);
```

**Relationships:**
- Belongs to: `tenant`, `user`, `customer`
- Has many: `sale_items`, `payments`

#### 8. sale_items
Tabel untuk detail item dalam transaksi penjualan.

```sql
CREATE TABLE sale_items (
    id UUID PRIMARY KEY,
    sale_id UUID NOT NULL,
    product_id UUID NOT NULL,
    quantity INTEGER NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

**Relationships:**
- Belongs to: `sale`, `product`

#### 9. payments
Tabel untuk detail pembayaran.

```sql
CREATE TABLE payments (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    sale_id UUID NULL,
    payment_method VARCHAR NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR DEFAULT 'IDR',
    status VARCHAR DEFAULT 'pending',
    transaction_id VARCHAR UNIQUE NULL,
    reference_id VARCHAR NULL,
    gateway_response JSONB NULL,
    notes TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
);
```

**Relationships:**
- Belongs to: `tenant`, `sale`

### Inventory Management

#### 10. inventories
Tabel untuk tracking pergerakan stok/inventaris.

```sql
CREATE TABLE inventories (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    product_id UUID NOT NULL,
    quantity_change INTEGER NOT NULL COMMENT 'Positive for in, negative for out',
    type VARCHAR NOT NULL COMMENT 'in, out, adjustment, return',
    reason TEXT NULL,
    source_id UUID NULL COMMENT 'Link to sale_item_id, etc',
    source_type VARCHAR NULL COMMENT 'App\\Models\\SaleItem, etc',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

**Relationships:**
- Belongs to: `tenant`, `product`
- Polymorphic relationship: `source`

### SaaS & Subscription Management

#### 11. pricing_plans
Tabel untuk paket berlangganan SaaS.

```sql
CREATE TABLE pricing_plans (
    id UUID PRIMARY KEY,
    plan_name VARCHAR NOT NULL,
    plan_description TEXT NULL,
    period_type VARCHAR NOT NULL COMMENT 'monthly, yearly',
    price DECIMAL(12,2) NOT NULL,
    discount_percentage DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Relationships:**
- Has many: `tenants`

#### 12. saas_invoices
Tabel untuk invoice berlangganan SaaS.

```sql
CREATE TABLE saas_invoices (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id UUID NOT NULL,
    plan_name VARCHAR NOT NULL,
    expired_at DATE NOT NULL,
    transaction_id VARCHAR NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Relationships:**
- Belongs to: `tenant`

#### 13. saas_settings
Tabel untuk pengaturan global SaaS.

```sql
CREATE TABLE saas_settings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR UNIQUE NOT NULL,
    value TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### System Tables

#### 14. jobs
Tabel untuk queue jobs Laravel.

```sql
CREATE TABLE jobs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    queue VARCHAR NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INTEGER UNSIGNED NULL,
    available_at INTEGER UNSIGNED NOT NULL,
    created_at INTEGER UNSIGNED NOT NULL
);
```

#### 15. cache & cache_locks
Tabel untuk caching system.

```sql
CREATE TABLE cache (
    key VARCHAR PRIMARY KEY,
    value MEDIUMTEXT NOT NULL,
    expiration INTEGER NOT NULL
);

CREATE TABLE cache_locks (
    key VARCHAR PRIMARY KEY,
    owner VARCHAR NOT NULL,
    expiration INTEGER NOT NULL
);
```

## Relationships Overview

```
tenants (1) ─┬─ (many) users
            ├─ (many) categories ─── (many) products
            ├─ (many) customers
            ├─ (many) sales ─┬─ (many) sale_items
            │                └─ (many) payments
            ├─ (many) inventories
            └─ (many) suppliers

pricing_plans (1) ──── (many) tenants
```

## Indexes & Performance

### Recommended Indexes

```sql
-- Performance indexes
CREATE INDEX idx_tenants_slug ON tenants(slug);
CREATE INDEX idx_tenants_email ON tenants(email);
CREATE INDEX idx_users_tenant_role ON users(tenant_id, role);
CREATE INDEX idx_products_tenant_active ON products(tenant_id, is_active);
CREATE INDEX idx_sales_tenant_status ON sales(tenant_id, status);
CREATE INDEX idx_sales_created_at ON sales(created_at);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_inventories_tenant_product ON inventories(tenant_id, product_id);
```

## Data Integrity Rules

1. **Tenant Isolation**: Semua data harus terisolasi per tenant melalui `tenant_id`
2. **Soft Deletes**: Gunakan soft deletes untuk data penting (users, products, categories, customers)
3. **UUID Primary Keys**: Semua tabel utama menggunakan UUID untuk keamanan
4. **Foreign Key Constraints**: Pastikan integritas referensial dengan proper cascade rules
5. **JSON Validation**: Field JSON seperti `gateway_response` harus divalidasi di application level

## Migration Order

Urutan migration yang benar untuk fresh installation:

1. `create_tenants_table`
2. `create_users_table`
3. `create_categories_table`
4. `create_products_table`
5. `create_customers_table`
6. `create_sales_table`
7. `create_sale_items_table`
8. `create_payments_table`
9. `create_inventories_table`
10. `create_suppliers_table`
11. `create_pricing_plans_table`
12. `create_saas_invoices_table`
13. `create_saas_settings_table`

## Backup & Maintenance

### Regular Backup

```bash
# SQLite backup
cp database/database.sqlite database/backup/database_$(date +%Y%m%d).sqlite

# MySQL backup
mysqldump -u username -p yualan_pos > backup_$(date +%Y%m%d).sql
```

### Database Maintenance

```bash
# Optimize database
php artisan db:optimize

# Clear old logs
php artisan log:clear

# Clean up soft deleted records (careful!)
php artisan model:prune --model=Product
```
