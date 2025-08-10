# Contributing Guide

## Overview

Terima kasih atas minat Anda untuk berkontribusi pada Yualan POS! Panduan ini menjelaskan cara berkontribusi, dari melaporkan bug hingga mengembangkan fitur baru. Project ini menggunakan lisensi GPL v2.0 dan terbuka untuk kontribusi dari semua orang.

## Code of Conduct

### Komitmen

Kami berkomitmen untuk menjadikan partisipasi dalam project ini sebagai pengalaman yang bebas dari pelecehan untuk semua orang, tanpa memandang usia, ukuran tubuh, disabilitas, etnis, identitas dan ekspresi gender, tingkat pengalaman, kebangsaan, penampilan pribadi, ras, agama, atau identitas dan orientasi seksual.

### Standar Perilaku

**Perilaku yang mendorong lingkungan yang positif meliputi:**
- Menggunakan bahasa yang ramah dan inklusif
- Menghormati sudut pandang dan pengalaman yang berbeda
- Menerima kritik konstruktif dengan baik
- Fokus pada hal yang terbaik untuk komunitas
- Menunjukkan empati terhadap anggota komunitas lainnya

**Perilaku yang tidak dapat diterima meliputi:**
- Penggunaan bahasa atau gambar yang bersifat seksual
- Trolling, komentar yang menghina/merendahkan, dan serangan pribadi atau politik
- Pelecehan publik atau pribadi
- Mempublikasikan informasi pribadi orang lain tanpa izin eksplisit
- Perilaku lain yang secara wajar dapat dianggap tidak pantas dalam lingkungan profesional

---

## Ways to Contribute

### 1. 🐛 Bug Reports

Jika menemukan bug, silakan buat issue dengan informasi berikut:

**Template Bug Report:**

```markdown
## Bug Description
[Deskripsi singkat tentang bug]

## Steps to Reproduce
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

## Expected Behavior
[Apa yang seharusnya terjadi]

## Actual Behavior
[Apa yang sebenarnya terjadi]

## Environment
- OS: [e.g., Ubuntu 20.04, Windows 11]
- Browser: [e.g., Chrome 120, Firefox 121]
- PHP Version: [e.g., 8.2.0]
- Laravel Version: [e.g., 12.0]
- Node.js Version: [e.g., 20.10.0]

## Screenshots
[Jika applicable, tambahkan screenshots]

## Additional Context
[Tambahan informasi tentang masalah]

## Error Logs
```
[Paste relevant log entries here]
```
```

### 2. 💡 Feature Requests

Untuk request fitur baru, gunakan template berikut:

```markdown
## Feature Description
[Deskripsi detail fitur yang diinginkan]

## Problem/Use Case
[Masalah apa yang akan dipecahkan fitur ini]

## Proposed Solution
[Bagaimana Anda membayangkan fitur ini bekerja]

## Alternative Solutions
[Alternatif solusi yang sudah dipertimbangkan]

## Benefits
- [Benefit 1]
- [Benefit 2]
- [Benefit 3]

## Technical Considerations
[Pertimbangan teknis, jika ada]

## Mockups/Examples
[Jika ada mockup atau contoh dari aplikasi lain]
```

### 3. 📚 Documentation

Dokumentasi adalah aspek penting. Anda dapat berkontribusi dengan:
- Memperbaiki typo atau kesalahan
- Menambahkan contoh kode
- Menerjemahkan dokumentasi ke bahasa lain
- Menambahkan tutorial atau panduan

### 4. 🧪 Testing

- Menulis unit tests
- Menulis integration tests
- Manual testing untuk fitur baru
- Performance testing

### 5. 💻 Code Contributions

Kontribusi kode adalah welcome! Ikuti proses di bawah ini.

---

## Development Process

### 1. Fork & Clone

```bash
# Fork repository di GitHub
# Kemudian clone fork Anda
git clone https://github.com/your-username/Yualan.git
cd Yualan

# Add upstream remote
git remote add upstream https://github.com/Abdurozzaq/Yualan.git
```

### 2. Local Development Setup

```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database
touch database/database.sqlite
php artisan migrate
php artisan db:seed

# Start development servers
npm run dev  # Terminal 1
php artisan serve  # Terminal 2
```

### 3. Create Feature Branch

```bash
# Sync dengan upstream
git fetch upstream
git checkout main
git merge upstream/main

# Buat branch baru
git checkout -b feature/amazing-feature
# atau
git checkout -b bugfix/fix-login-issue
```

### 4. Development

#### Code Style Guidelines

**PHP (Laravel):**
- Follow PSR-12 coding standards
- Use meaningful variable and method names
- Add docblocks untuk methods yang complex
- Use type hints dan return types

```php
<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    /**
     * Get products by category with pagination
     * 
     * @param string $categoryId
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getProductsByCategory(string $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return Product::where('category_id', $categoryId)
            ->where('is_active', true)
            ->with('category')
            ->paginate($perPage);
    }
}
```

**Vue.js/TypeScript:**
- Use Composition API
- Define proper TypeScript interfaces
- Use consistent naming conventions
- Add comments untuk complex logic

```vue
<script setup lang="ts">
interface Product {
  id: string
  name: string
  price: number
  category?: Category
}

interface Props {
  products: Product[]
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<{
  refresh: []
  select: [product: Product]
}>()

/**
 * Handle product selection
 */
const handleProductSelect = (product: Product) => {
  if (!props.loading) {
    emit('select', product)
  }
}
</script>
```

#### Database Guidelines

**Migrations:**
- Use descriptive migration names
- Include rollback functionality
- Add indexes untuk performance

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('product_id');
            $table->uuid('customer_id');
            $table->integer('rating')->comment('1-5 stars');
            $table->text('comment')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');

            // Indexes for performance
            $table->index(['tenant_id', 'product_id']);
            $table->index(['rating', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
```

### 5. Testing

#### Write Tests

```php
<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin'
        ]);

        $response = $this->actingAs($admin)
            ->post("/tenant/{$tenant->slug}/products", [
                'name' => 'Test Product',
                'price' => 150000,
                'stock' => 10
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'tenant_id' => $tenant->id
        ]);
    }

    public function test_cashier_cannot_create_product(): void
    {
        $tenant = Tenant::factory()->create();
        $cashier = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'cashier'
        ]);

        $response = $this->actingAs($cashier)
            ->post("/tenant/{$tenant->slug}/products", [
                'name' => 'Test Product',
                'price' => 150000
            ]);

        $response->assertStatus(403);
    }
}
```

#### Run Tests

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter ProductManagementTest

# Run with coverage
php artisan test --coverage

# Frontend tests
npm run test
```

### 6. Commit Guidelines

#### Commit Message Format

```
type(scope): subject

body

footer
```

**Types:**
- `feat`: Fitur baru
- `fix`: Bug fix
- `docs`: Dokumentasi
- `style`: Formatting, tidak mengubah kode
- `refactor`: Refactoring kode
- `test`: Menambah atau memperbaiki tests
- `chore`: Maintenance tasks

**Examples:**

```bash
# Good commit messages
git commit -m "feat(products): add bulk import functionality"
git commit -m "fix(auth): resolve login redirect issue"
git commit -m "docs(api): update payment gateway documentation"
git commit -m "test(sales): add integration tests for checkout process"

# Bad commit messages
git commit -m "fix bug"
git commit -m "update"
git commit -m "changes"
```

#### Commit Best Practices

```bash
# Make atomic commits
git add specific-file.php
git commit -m "fix(auth): resolve session timeout issue"

# Separate concerns
git add new-feature.php
git commit -m "feat(inventory): add stock alert notifications"

git add tests/
git commit -m "test(inventory): add tests for stock alerts"

# Use conventional commits
# See: https://www.conventionalcommits.org/
```

### 7. Pull Request Process

#### Before Creating PR

```bash
# Sync dengan upstream
git fetch upstream
git checkout main
git merge upstream/main

# Rebase feature branch
git checkout feature/amazing-feature
git rebase main

# Run tests
php artisan test
npm run test

# Check code style
./vendor/bin/pint
npm run lint

# Build assets
npm run build
```

#### PR Template

```markdown
## Description
[Deskripsi singkat tentang perubahan]

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] This change requires a documentation update

## Related Issues
Fixes #[issue-number]
Closes #[issue-number]

## Screenshots (if applicable)
[Add screenshots here]

## Testing
- [ ] I have performed a self-review of my own code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally with my changes

## Checklist
- [ ] My code follows the style guidelines of this project
- [ ] I have performed a self-review of my own code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally with my changes

## Additional Notes
[Any additional information]
```

#### PR Review Process

1. **Automated Checks**: GitHub Actions akan run tests otomatis
2. **Code Review**: Maintainer akan review kode Anda
3. **Feedback**: Address feedback yang diberikan
4. **Approval**: Setelah approved, PR akan di-merge

---

## Contribution Areas

### Priority Areas for Contribution

#### 🔧 Backend Development
- API improvements
- Performance optimization
- Security enhancements
- New payment gateway integrations
- Advanced reporting features

#### 🎨 Frontend Development
- UI/UX improvements
- Mobile responsiveness
- Accessibility improvements
- New component library
- Progressive Web App features

#### 📊 Analytics & Reporting
- Advanced sales analytics
- Inventory forecasting
- Business intelligence features
- Export functionality improvements
- Custom report builder

#### 🔐 Security & Performance
- Security auditing
- Performance optimization
- Database query optimization
- Caching improvements
- Load testing

#### 🌐 Localization
- Translation ke bahasa lain
- Currency support
- Regional specific features
- Date/time formatting

#### 📱 Integrations
- Third-party service integrations
- API improvements
- Webhook implementations
- Import/export features

### Beginner-Friendly Issues

Look for issues with labels:
- `good first issue`
- `beginner-friendly`
- `documentation`
- `help wanted`

---

## Development Environment

### Required Tools

- **Git**: Version control
- **PHP 8.2+**: Backend runtime
- **Composer**: PHP dependency manager
- **Node.js 20.x**: Frontend runtime
- **NPM/Yarn**: Node dependency manager
- **Code Editor**: VSCode recommended

### Recommended VSCode Extensions

```json
{
  "recommendations": [
    "bmewburn.vscode-intelephense-client",
    "vue.volar",
    "bradlc.vscode-tailwindcss",
    "ms-vscode.vscode-typescript-next",
    "esbenp.prettier-vscode",
    "dbaeumer.vscode-eslint",
    "ms-vscode.vscode-json",
    "formulahendry.auto-rename-tag",
    "christian-kohler.path-intellisense"
  ]
}
```

### Development Commands

```bash
# Code quality
./vendor/bin/pint              # PHP code style
npm run lint                   # ESLint check
npm run format                 # Prettier format
npm run type-check             # TypeScript check

# Testing
php artisan test               # Backend tests
npm run test                   # Frontend tests

# Development servers
php artisan serve              # Laravel server
npm run dev                    # Vite dev server
php artisan queue:work         # Queue worker

# Database
php artisan migrate:fresh      # Fresh migration
php artisan db:seed            # Seed database
php artisan tinker             # Laravel REPL
```

---

## Community Guidelines

### Communication Channels

- **GitHub Issues**: Bug reports, feature requests
- **GitHub Discussions**: General discussions, questions
- **Discord**: Real-time chat (coming soon)
- **Email**: security@yualan.dev (security issues only)

### Getting Help

1. **Documentation**: Check existing documentation first
2. **Search Issues**: Look for existing issues/discussions  
3. **Ask Questions**: Create a GitHub discussion
4. **Join Community**: Follow project updates

### Recognition

Contributors akan diakui dengan:
- Nama di CONTRIBUTORS.md file
- Mention di release notes
- Special badge di GitHub profile
- LinkedIn recommendation (untuk significant contributions)

---

## Release Process

### Semantic Versioning

Project menggunakan [SemVer](https://semver.org/):
- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

### Release Schedule

- **Major releases**: 6-12 bulan
- **Minor releases**: 1-3 bulan  
- **Patch releases**: Sesuai kebutuhan

### Changelog

Setiap release akan include:
- New features
- Bug fixes
- Breaking changes
- Deprecations
- Security updates

---

## Security

### Reporting Security Vulnerabilities

**DO NOT** create public GitHub issues untuk security vulnerabilities.

Instead, email security@yualan.dev dengan informasi:
- Description of vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

### Security Review Process

1. **Acknowledgment**: Dalam 48 jam
2. **Investigation**: 5-10 hari kerja
3. **Fix Development**: Sesuai severity
4. **Disclosure**: Setelah fix dirilis

---

## Legal

### License

Dengan berkontribusi, Anda setuju bahwa kontribusi Anda akan dilisensikan di bawah GPL v2.0 License yang sama dengan project ini.

### Contributor License Agreement

Dengan submit pull request, Anda menyatakan bahwa:
- Anda memiliki hak untuk submit kode tersebut
- Kontribusi Anda adalah original work atau Anda memiliki permission yang diperlukan
- Anda memberikan permission kepada project untuk menggunakan kontribusi Anda

---

## Recognition

### Hall of Fame

Contributors yang memberikan significant impact akan dimasukkan ke Hall of Fame:

#### Core Contributors
- [Abdurozzaq](https://github.com/Abdurozzaq) - Project Creator & Lead Developer

#### Major Contributors
- [Your Name Here] - Add significant feature or improvements

#### Contributors
- [Your Name Here] - Bug fixes, documentation, testing

### Contribution Levels

- 🥉 **Bronze**: 1-5 merged PRs
- 🥈 **Silver**: 6-15 merged PRs
- 🥇 **Gold**: 16+ merged PRs
- 💎 **Diamond**: Exceptional contributions to the project

---

## Thank You!

Thank you untuk interest dalam berkontribusi ke Yualan POS! Every contribution, no matter how small, makes a difference. Bersama-sama kita bisa membuat aplikasi POS yang powerful dan accessible untuk semua.

**Happy Coding! 🚀**

---

## Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Guide](https://vuejs.org/guide/)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [Semantic Versioning](https://semver.org/)

## Questions?

Jika ada pertanyaan tentang contribution process, silakan:
1. Check dokumentasi yang ada
2. Search GitHub issues/discussions
3. Create new GitHub discussion
4. Contact maintainers

We're here to help! 😊
