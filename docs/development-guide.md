# Development Guide

## Overview

Panduan ini menjelaskan cara berkontribusi dan mengembangkan fitur pada Yualan POS. Project ini menggunakan tech stack modern dengan Laravel, Vue 3, TypeScript, dan arsitektur multi-tenant.

## Development Environment Setup

### Prerequisites

Pastikan Anda sudah mengikuti [Installation Guide](installation.md) sebelum melanjutkan.

### Development Tools

#### Recommended VSCode Extensions

```json
{
  "recommendations": [
    "bmewburn.vscode-intelephense-client",
    "vue.volar",
    "bradlc.vscode-tailwindcss",
    "ms-vscode.vscode-typescript-next",
    "esbenp.prettier-vscode",
    "dbaeumer.vscode-eslint"
  ]
}
```

#### Code Quality Tools

Project sudah dikonfigurasi dengan:

- **ESLint** - JavaScript/TypeScript linting
- **Prettier** - Code formatting
- **Laravel Pint** - PHP code style
- **Vue TSC** - TypeScript checking untuk Vue

```bash
# Menjalankan code quality checks
npm run lint          # ESLint check & fix
npm run format        # Prettier formatting
./vendor/bin/pint     # Laravel Pint (PHP)
npm run type-check    # TypeScript checking
```

## Project Architecture

### Multi-Tenant Architecture

Setiap request di-route berdasarkan tenant slug:

```
/tenant/myshop/dashboard
/tenant/myshop/products
/tenant/myshop/sales
```

#### Tenant Resolution

```php
// Middleware untuk resolve tenant
Route::group(['prefix' => 'tenant/{tenantSlug}', 'middleware' => 'tenant'], function () {
    // Tenant routes
});
```

#### Data Isolation

```php
// Semua query harus include tenant_id
Product::where('tenant_id', $tenant->id)->get();

// Atau gunakan scoped queries
class Product extends Model 
{
    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }
}
```

### Backend Structure

#### Models

Semua models menggunakan UUID dan mengextend BaseModel:

```php
// app/Models/Product.php
class Product extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $fillable = [
        'tenant_id',
        'category_id', 
        'name',
        'price',
        'stock'
    ];
    
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}
```

#### Controllers

Gunakan resource controllers dan follow RESTful conventions:

```php
// app/Http/Controllers/ProductController.php
class ProductController extends Controller
{
    public function index(Request $request, $tenantSlug)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        
        $products = Product::where('tenant_id', $tenant->id)
            ->with('category')
            ->paginate(10);
            
        return Inertia::render('Products/Index', [
            'products' => $products,
            'tenantSlug' => $tenantSlug
        ]);
    }
}
```

#### Services

Business logic harus dipisahkan ke service classes:

```php
// app/Services/SalesService.php
class SalesService
{
    public function createSale(array $data, Tenant $tenant): Sale
    {
        return DB::transaction(function () use ($data, $tenant) {
            $sale = Sale::create([
                'tenant_id' => $tenant->id,
                'invoice_number' => $this->generateInvoiceNumber($tenant),
                // ... other fields
            ]);
            
            foreach ($data['items'] as $item) {
                $sale->saleItems()->create($item);
                $this->updateInventory($item, $tenant);
            }
            
            return $sale;
        });
    }
}
```

### Frontend Structure

#### Vue Components

Gunakan Composition API dan TypeScript:

```vue
<!-- resources/js/pages/Products/Index.vue -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

interface Product {
  id: string
  name: string
  price: number
  stock: number
  category?: {
    name: string
  }
}

interface Props {
  products: {
    data: Product[]
    meta: any
  }
  tenantSlug: string
}

const props = defineProps<Props>()

const searchQuery = ref('')
const filteredProducts = computed(() => {
  // filtering logic
})
</script>

<template>
  <Head title="Products" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Component content -->
    </div>
  </AppLayout>
</template>
```

#### State Management

Gunakan Pinia untuk complex state:

```typescript
// resources/js/stores/cartStore.ts
export const useCartStore = defineStore('cart', () => {
  const items = ref<CartItem[]>([])
  
  const addItem = (product: Product, quantity: number) => {
    // Add to cart logic
  }
  
  const total = computed(() => {
    return items.value.reduce((sum, item) => sum + item.subtotal, 0)
  })
  
  return { items, addItem, total }
})
```

#### UI Components

Gunakan component library yang sudah ada (Radix Vue):

```vue
<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Product Form</CardTitle>
    </CardHeader>
    <CardContent>
      <div class="space-y-4">
        <Input v-model="form.name" placeholder="Product name" />
        <Button @click="submit">Save Product</Button>
      </div>
    </CardContent>
  </Card>
</template>
```

## Development Workflow

### 1. Feature Development

Saat mengembangkan fitur baru:

```bash
# 1. Buat branch baru
git checkout -b feature/new-feature

# 2. Install dependencies jika ada perubahan
composer install
npm install

# 3. Jalankan migrations jika ada
php artisan migrate

# 4. Start development servers
npm run dev      # Terminal 1 - Vite
php artisan serve  # Terminal 2 - Laravel

# 5. Development work...

# 6. Test perubahan
php artisan test
npm run type-check

# 7. Format code
npm run format
./vendor/bin/pint
```

### 2. Database Changes

#### Membuat Migration

```bash
# Buat migration baru
php artisan make:migration create_new_table --create=new_table

# Edit migration file
# database/migrations/xxxx_create_new_table.php

# Jalankan migration
php artisan migrate

# Rollback jika diperlukan
php artisan migrate:rollback
```

#### Membuat Model

```bash
# Generate model dengan factory dan migration
php artisan make:model NewModel -mf

# Atau lengkap dengan controller dan resource
php artisan make:model NewModel -mcr
```

### 3. API Development

#### Controller

```php
class ApiController extends Controller
{
    public function index(Request $request, $tenantSlug)
    {
        $tenant = $this->resolveTenant($tenantSlug);
        
        $data = SomeModel::where('tenant_id', $tenant->id)
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%");
            })
            ->paginate($request->per_page ?? 15);
            
        return response()->json($data);
    }
}
```

#### Form Requests

```php
// app/Http/Requests/StoreProductRequest.php
class StoreProductRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
        ];
    }
    
    public function authorize()
    {
        return auth()->check();
    }
}
```

### 4. Frontend Development

#### Adding New Pages

```bash
# 1. Buat Vue component
mkdir -p resources/js/pages/NewFeature
touch resources/js/pages/NewFeature/Index.vue

# 2. Tambah route di Laravel
# routes/web.php atau routes/tenant.php

# 3. Tambah navigation link
# resources/js/components/AppSidebar.vue
```

#### Component Development

```vue
<!-- resources/js/components/ProductCard.vue -->
<script setup lang="ts">
interface Props {
  product: {
    id: string
    name: string
    price: number
    image?: string
  }
  showActions?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showActions: true
})

const emit = defineEmits<{
  edit: [id: string]
  delete: [id: string]
}>()
</script>

<template>
  <Card class="overflow-hidden">
    <div v-if="product.image" class="aspect-square">
      <img :src="product.image" :alt="product.name" class="w-full h-full object-cover">
    </div>
    
    <CardContent class="p-4">
      <h3 class="font-semibold">{{ product.name }}</h3>
      <p class="text-lg font-bold text-primary">{{ formatCurrency(product.price) }}</p>
      
      <div v-if="showActions" class="flex gap-2 mt-4">
        <Button size="sm" @click="emit('edit', product.id)">Edit</Button>
        <Button size="sm" variant="destructive" @click="emit('delete', product.id)">Delete</Button>
      </div>
    </CardContent>
  </Card>
</template>
```

## Testing

### Backend Testing

```php
// tests/Feature/ProductTest.php
class ProductTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_create_product()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        
        $response = $this->actingAs($user)
            ->post("/tenant/{$tenant->slug}/products", [
                'name' => 'Test Product',
                'price' => 100000,
            ]);
            
        $response->assertStatus(201);
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'tenant_id' => $tenant->id
        ]);
    }
}
```

```bash
# Jalankan tests
php artisan test
php artisan test --filter ProductTest
```

### Frontend Testing

```bash
# Install testing dependencies
npm install -D @vue/test-utils vitest jsdom

# Run tests  
npm run test
```

## Artisan Commands

### Custom Commands

Project memiliki beberapa custom commands:

```bash
# Check pending iPaymu transactions
php artisan yualan:check-pending-transactions

# Update tenant subscription status
php artisan tenant:update-subscription-status

# Generate transaction status report
php artisan yualan:transaction-status-report
```

### Creating New Commands

```bash
# Generate command
php artisan make:command CustomCommand

# Implement command
class CustomCommand extends Command
{
    protected $signature = 'yualan:custom {--option=}';
    protected $description = 'Custom command description';
    
    public function handle()
    {
        $this->info('Command executed successfully!');
    }
}
```

## Queue Jobs

### Background Processing

```php
// app/Jobs/ProcessPayment.php
class ProcessPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        public Sale $sale,
        public array $paymentData
    ) {}
    
    public function handle()
    {
        // Process payment logic
    }
}
```

```bash
# Dispatch job
ProcessPayment::dispatch($sale, $paymentData);

# Run queue worker
php artisan queue:work
```

## Performance Optimization

### Database

```php
// Eager loading
Product::with(['category', 'inventories'])->get();

// Chunking for large datasets
Product::chunk(100, function ($products) {
    foreach ($products as $product) {
        // Process product
    }
});

// Database indexes
Schema::table('products', function (Blueprint $table) {
    $table->index(['tenant_id', 'is_active']);
});
```

### Frontend

```vue
<script setup lang="ts">
// Lazy loading components
const LazyComponent = defineAsyncComponent(() => import('./LazyComponent.vue'))

// Computed properties untuk data yang kompleks
const expensiveData = computed(() => {
  return heavyCalculation(props.data)
})

// Debounced search
const debouncedSearch = debounce((query: string) => {
  // Search logic
}, 300)
</script>
```

## Debugging

### Backend Debugging

```php
// Logging
Log::info('Debug info', ['data' => $data]);
Log::error('Error occurred', ['error' => $exception->getMessage()]);

// Dump and die
dd($variable);
dump($variable);

// Database query debugging
DB::enableQueryLog();
// ... execute queries
dd(DB::getQueryLog());
```

### Frontend Debugging

```typescript
// Console logging
console.log('Debug:', data)
console.error('Error:', error)

// Vue DevTools
// Install browser extension

// Network debugging
// Browser DevTools Network tab
```

## Code Style Guidelines

### PHP (Laravel)

Follow PSR-12 and Laravel conventions:

```php
// Good
class ProductController extends Controller
{
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->validated());
        
        return redirect()
            ->route('products.index')
            ->with('success', 'Product created successfully.');
    }
}
```

### TypeScript/Vue

Follow Vue 3 Composition API conventions:

```typescript
// Good
const { data, loading, error } = useAsyncData('products', () => 
  $fetch<Product[]>('/api/products')
)

const handleSubmit = async () => {
  try {
    await saveProduct(form.value)
    router.push('/products')
  } catch (error) {
    console.error('Save failed:', error)
  }
}
```

## Deployment

Lihat [Deployment Guide](deployment.md) untuk panduan lengkap deployment ke production.

## Contributing

1. Fork repository
2. Buat feature branch (`git checkout -b feature/amazing-feature`)
3. Commit perubahan (`git commit -m 'Add amazing feature'`)
4. Push ke branch (`git push origin feature/amazing-feature`)
5. Buat Pull Request

## Support & Documentation

- [API Documentation](api-documentation.md)
- [Database Schema](database-schema.md) 
- [Troubleshooting](troubleshooting.md)
- GitHub Issues untuk bug reports
- Discord/Telegram untuk diskusi
