# Stock Management System - Part 1: Setup & Database

## 1. Initial Project Setup

### Step 1: Create Laravel Project
```bash
composer create-project laravel/laravel stock-management
cd stock-management
```

### Step 2: Install Required Packages
```bash
# Install Livewire
composer require livewire/livewire

# Install Breeze with Livewire
composer require laravel/breeze --dev
php artisan breeze:install livewire
npm install
npm run build
```

### Step 3: Configure Database
Edit `.env` file:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stock_management
DB_USERNAME=root
DB_PASSWORD=
```

## 2. Database Schema & Migrations

### Migration 1: Add role to users table
**File**: `database/migrations/2024_add_role_to_users_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'manager', 'stock_worker'])
                  ->default('stock_worker')
                  ->after('email');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
```

### Migration 2: Create categories table
**File**: `database/migrations/2024_create_categories_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('categories');
    }
};
```

### Migration 3: Create products table
**File**: `database/migrations/2024_create_products_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->integer('quantity')->default(0);
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};
```

### Migration 4: Create stock_movements table
**File**: `database/migrations/2024_create_stock_movements_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['in', 'out']);
            $table->integer('quantity');
            $table->enum('reason', ['received', 'sold', 'damaged', 'adjustment'])
                  ->default('adjustment');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_movements');
    }
};
```

## 3. Models

### Model 1: Update User Model
**File**: `app/Models/User.php`
Add this to the existing User model:
```php
// Add to fillable array
protected $fillable = [
    'name',
    'email',
    'password',
    'role', // Add this line
];

// Add these methods
public function isAdmin()
{
    return $this->role === 'admin';
}

public function isManager()
{
    return $this->role === 'manager';
}

public function isStockWorker()
{
    return $this->role === 'stock_worker';
}

public function stockMovements()
{
    return $this->hasMany(StockMovement::class);
}
```

### Model 2: Category Model
**File**: `app/Models/Category.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = ['name', 'slug'];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($category) {
            $category->slug = Str::slug($category->name);
        });
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
```

### Model 3: Product Model
**File**: `app/Models/Product.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'sku', 'quantity', 'category_id'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isLowStock()
    {
        return $this->quantity < 10;
    }
}
```

### Model 4: StockMovement Model
**File**: `app/Models/StockMovement.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'quantity',
        'reason'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

## 4. Run Migrations
```bash
php artisan migrate
```

## Next Steps
After completing Part 1:
1. Run the migrations to create your database tables
2. Test that the project runs with `php artisan serve`
3. Verify you can access the Breeze login page
4. Move to Part 2 for Seeders and Gates setup

# Stock Management System - Part 2: Seeders & Authorization

## 1. Database Seeders

### Seeder 1: Category Seeder
**File**: `database/seeders/CategorySeeder.php`
```php
<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            'Electronics',
            'Clothing',
            'Books',
            'Food & Beverages',
            'Home & Garden'
        ];

        foreach ($categories as $category) {
            Category::create(['name' => $category]);
        }
    }
}
```

### Seeder 2: User Seeder
**File**: `database/seeders/UserSeeder.php`
```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        // Manager user
        User::create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'role' => 'manager'
        ]);

        // Stock Worker user
        User::create([
            'name' => 'Stock Worker',
            'email' => 'worker@example.com',
            'password' => Hash::make('password'),
            'role' => 'stock_worker'
        ]);
    }
}
```

### Seeder 3: Product Seeder
**File**: `database/seeders/ProductSeeder.php`
```php
<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $products = [
            ['name' => 'Laptop Dell XPS 13', 'sku' => 'DELL-XPS-001', 'category' => 'Electronics', 'quantity' => 15],
            ['name' => 'iPhone 14 Pro', 'sku' => 'APPL-IP14-001', 'category' => 'Electronics', 'quantity' => 8],
            ['name' => 'Samsung TV 55"', 'sku' => 'SAMS-TV55-001', 'category' => 'Electronics', 'quantity' => 5],
            ['name' => 'T-Shirt Blue L', 'sku' => 'TSH-BLU-L-001', 'category' => 'Clothing', 'quantity' => 50],
            ['name' => 'Jeans Black 32', 'sku' => 'JNS-BLK-32-001', 'category' => 'Clothing', 'quantity' => 30],
            ['name' => 'Laravel Book', 'sku' => 'BOOK-LAR-001', 'category' => 'Books', 'quantity' => 20],
            ['name' => 'PHP Cookbook', 'sku' => 'BOOK-PHP-001', 'category' => 'Books', 'quantity' => 12],
            ['name' => 'Coffee Beans 1kg', 'sku' => 'COFF-BEAN-001', 'category' => 'Food & Beverages', 'quantity' => 25],
            ['name' => 'Green Tea Box', 'sku' => 'TEA-GRN-001', 'category' => 'Food & Beverages', 'quantity' => 40],
            ['name' => 'Garden Chair', 'sku' => 'GARD-CHR-001', 'category' => 'Home & Garden', 'quantity' => 18],
        ];

        foreach ($products as $product) {
            $category = Category::where('name', $product['category'])->first();
            
            Product::create([
                'name' => $product['name'],
                'sku' => $product['sku'],
                'category_id' => $category->id,
                'quantity' => $product['quantity']
            ]);
        }
    }
}
```

### Update DatabaseSeeder
**File**: `database/seeders/DatabaseSeeder.php`
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            CategorySeeder::class,
            UserSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
```

### Run Seeders
```bash
php artisan db:seed
```

## 2. Authorization with Gates

### Create AuthServiceProvider Gates
**File**: `app/Providers/AuthServiceProvider.php`
```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 
    ];

    public function boot()
    {
        // Admin gates
        Gate::define('manage-users', function (User $user) {
            return $user->isAdmin();
        });

        // Manager gates
        Gate::define('manage-products', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });

        Gate::define('manage-categories', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });

        // Stock worker gates
        Gate::define('adjust-stock', function (User $user) {
            return in_array($user->role, ['admin', 'manager', 'stock_worker']);
        });

        Gate::define('view-products', function (User $user) {
            return true; // All authenticated users can view
        });

        Gate::define('view-reports', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });
    }
}
```

## 3. Middleware for Role Checking

### Create Role Middleware
**File**: `app/Http/Middleware/CheckRole.php`
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user() || !in_array($request->user()->role, $roles)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
```

### Register Middleware
**File**: `app/Http/Kernel.php`
Add to `$routeMiddleware` array:
```php
protected $routeMiddleware = [
    // ... other middleware
    'role' => \App\Http\Middleware\CheckRole::class,
];
```

## 4. Update Navigation Menu

### Update Navigation Component
**File**: `resources/views/livewire/layout/navigation.blade.php`
Add these navigation items after the Dashboard link:
```blade
<!-- Products -->
@can('view-products')
<x-nav-link :href="route('products.index')" :active="request()->routeIs('products.*')" wire:navigate>
    {{ __('Products') }}
</x-nav-link>
@endcan

<!-- Stock Movements -->
@can('adjust-stock')
<x-nav-link :href="route('stock-movements.index')" :active="request()->routeIs('stock-movements.*')" wire:navigate>
    {{ __('Stock Movements') }}
</x-nav-link>
@endcan

<!-- Categories -->
@can('manage-categories')
<x-nav-link :href="route('categories.index')" :active="request()->routeIs('categories.*')" wire:navigate>
    {{ __('Categories') }}
</x-nav-link>
@endcan

<!-- Users -->
@can('manage-users')
<x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" wire:navigate>
    {{ __('Users') }}
</x-nav-link>
@endcan
```

## Test Users
After running seeders, you can login with:
- **Admin**: admin@example.com / password
- **Manager**: manager@example.com / password  
- **Stock Worker**: worker@example.com / password

## Next Steps
1. Run `php artisan db:seed` to populate the database
2. Test login with different user roles
3. Verify that navigation shows different items based on role
4. Move to Part 3 for Routes and Controllers setup

# Stock Management System - Part 3: Routes & Controllers

## 1. Routes Configuration

### Update Routes File
**File**: `routes/web.php`
```php
<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Products
    Route::resource('products', ProductController::class);
    Route::get('/products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])
        ->name('products.adjust-stock')
        ->middleware('can:adjust-stock');
    
    // Stock Movements
    Route::get('/stock-movements', [StockMovementController::class, 'index'])
        ->name('stock-movements.index')
        ->middleware('can:adjust-stock');
    
    // Categories
    Route::resource('categories', CategoryController::class)
        ->middleware('can:manage-categories');
    
    // Users (Admin only)
    Route::resource('users', UserController::class)
        ->middleware('can:manage-users');
    
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
```

## 2. Controllers

### Controller 1: Dashboard Controller
**File**: `app/Http/Controllers/DashboardController.php`
```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $lowStockProducts = Product::where('quantity', '<', 10)->count();
        $totalProducts = Product::count();
        $totalCategories = Category::count();
        $recentMovements = StockMovement::with(['product', 'user'])
            ->latest()
            ->take(5)
            ->get();
        
        $categoriesWithCount = Category::withCount('products')->get();
        
        return view('dashboard', compact(
            'lowStockProducts',
            'totalProducts',
            'totalCategories',
            'recentMovements',
            'categoriesWithCount'
        ));
    }
}
```

### Controller 2: Product Controller
**File**: `app/Http/Controllers/ProductController.php`
```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function index()
    {
        Gate::authorize('view-products');
        
        return view('products.index');
    }

    public function create()
    {
        Gate::authorize('manage-products');
        
        $categories = Category::all();
        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-products');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku',
            'quantity' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id'
        ]);

        Product::create($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        Gate::authorize('manage-products');
        
        $categories = Category::all();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        Gate::authorize('manage-products');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku,' . $product->id,
            'quantity' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id'
        ]);

        $product->update($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        Gate::authorize('manage-products');
        
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function adjustStock(Product $product)
    {
        Gate::authorize('adjust-stock');
        
        return view('products.adjust-stock', compact('product'));
    }
}
```

### Controller 3: Category Controller
**File**: `app/Http/Controllers/CategoryController.php`
```php
<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return view('categories.index');
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name'
        ]);

        Category::create($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id
        ]);

        $category->update($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->count() > 0) {
            return redirect()->route('categories.index')
                ->with('error', 'Cannot delete category with products.');
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
```

### Controller 4: Stock Movement Controller
**File**: `app/Http/Controllers/StockMovementController.php`
```php
<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index()
    {
        return view('stock-movements.index');
    }
}
```

### Controller 5: User Controller
**File**: `app/Http/Controllers/UserController.php`
```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::paginate(10);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,manager,stock_worker'
        ]);

        $validated['password'] = Hash::make($validated['password']);
        User::create($validated);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,manager,stock_worker'
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|string|min:8|confirmed'
            ]);
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'Cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
```

## Next Steps
1. Create all controller files in the specified locations
2. Make sure to run `php artisan route:clear` after adding routes
3. Test that all routes are registered with `php artisan route:list`
4. Move to Part 4 for Livewire Components

# Stock Management System - Part 4: Livewire Components

## 1. Product List Component

### Component Class
**File**: `app/Livewire/ProductList.php`
```php
<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;

class ProductList extends Component
{
    use WithPagination;

    public $search = '';
    public $categoryFilter = '';
    
    protected $queryString = ['search', 'categoryFilter'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    public function deleteProduct($productId)
    {
        $product = Product::find($productId);
        
        if ($product && auth()->user()->can('manage-products')) {
            $product->delete();
            session()->flash('message', 'Product deleted successfully.');
        }
    }

    public function render()
    {
        $query = Product::with('category')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('sku', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->categoryFilter, function ($query) {
                $query->where('category_id', $this->categoryFilter);
            });

        $products = $query->paginate(10);
        $categories = Category::all();

        return view('livewire.product-list', compact('products', 'categories'));
    }
}
```

### Component View
**File**: `resources/views/livewire/product-list.blade.php`
```blade
<div>
    @if (session()->has('message'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('message') }}
        </div>
    @endif

    <div class="mb-4 flex gap-4">
        <input type="text" 
               wire:model.live="search" 
               placeholder="Search products..." 
               class="flex-1 rounded-md border-gray-300 shadow-sm">
        
        <select wire:model.live="categoryFilter" 
                class="rounded-md border-gray-300 shadow-sm">
            <option value="">All Categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        SKU
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Category
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Quantity
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($products as $product)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $product->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $product->sku }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $product->category->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="{{ $product->quantity < 10 ? 'text-red-600 font-bold' : '' }}">
                                {{ $product->quantity }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            @can('adjust-stock')
                                <a href="{{ route('products.adjust-stock', $product) }}" 
                                   class="text-blue-600 hover:text-blue-900 mr-3">
                                    Adjust Stock
                                </a>
                            @endcan
                            
                            @can('manage-products')
                                <a href="{{ route('products.edit', $product) }}" 
                                   class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    Edit
                                </a>
                                
                                <button wire:click="deleteProduct({{ $product->id }})"
                                        wire:confirm="Are you sure you want to delete this product?"
                                        class="text-red-600 hover:text-red-900">
                                    Delete
                                </button>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
</div>
```

## 2. Stock Adjustment Component

### Component Class
**File**: `app/Livewire/StockAdjustment.php`
```php
<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\StockMovement;
use Livewire\Component;

class StockAdjustment extends Component
{
    public Product $product;
    public $type = 'in';
    public $quantity = 1;
    public $reason = 'adjustment';
    
    protected $rules = [
        'type' => 'required|in:in,out',
        'quantity' => 'required|integer|min:1',
        'reason' => 'required|in:received,sold,damaged,adjustment'
    ];

    public function mount(Product $product)
    {
        $this->product = $product;
    }

    public function adjustStock()
    {
        $this->validate();

        // Check if we have enough stock for 'out' type
        if ($this->type === 'out' && $this->quantity > $this->product->quantity) {
            $this->addError('quantity', 'Not enough stock available.');
            return;
        }

        // Update product quantity
        if ($this->type === 'in') {
            $this->product->increment('quantity', $this->quantity);
        } else {
            $this->product->decrement('quantity', $this->quantity);
        }

        // Create stock movement record
        StockMovement::create([
            'product_id' => $this->product->id,
            'user_id' => auth()->id(),
            'type' => $this->type,
            'quantity' => $this->quantity,
            'reason' => $this->reason
        ]);

        session()->flash('success', 'Stock adjusted successfully.');
        
        return redirect()->route('products.index');
    }

    public function render()
    {
        return view('livewire.stock-adjustment');
    }
}
```

### Component View
**File**: `resources/views/livewire/stock-adjustment.blade.php`
```blade
<div>
    <form wire:submit="adjustStock">
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">
                Product
            </label>
            <p class="text-gray-900">{{ $product->name }} (SKU: {{ $product->sku }})</p>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">
                Current Quantity
            </label>
            <p class="text-gray-900 text-lg font-semibold">{{ $product->quantity }}</p>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">
                Adjustment Type
            </label>
            <div class="mt-2">
                <label class="inline-flex items-center mr-4">
                    <input type="radio" wire:model="type" value="in" class="form-radio">
                    <span class="ml-2">Stock In</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" wire:model="type" value="out" class="form-radio">
                    <span class="ml-2">Stock Out</span>
                </label>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">
                Quantity
            </label>
            <input type="number" 
                   wire:model="quantity" 
                   min="1" 
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            @error('quantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">
                Reason
            </label>
            <select wire:model="reason" 
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="adjustment">Adjustment</option>
                <option value="received">Received</option>
                <option value="sold">Sold</option>
                <option value="damaged">Damaged</option>
            </select>
        </div>

        <div class="flex items-center justify-between">
            <button type="submit" 
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Adjust Stock
            </button>
            <a href="{{ route('products.index') }}" 
               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Cancel
            </a>
        </div>
    </form>
</div>
```

## 3. Category List Component

### Component Class
**File**: `app/Livewire/CategoryList.php`
```php
<?php

namespace App\Livewire;

use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;

class CategoryList extends Component
{
    use WithPagination;

    public function deleteCategory($categoryId)
    {
        $category = Category::find($categoryId);
        
        if ($category) {
            if ($category->products()->count() > 0) {
                session()->flash('error', 'Cannot delete category with products.');
                return;
            }
            
            $category->delete();
            session()->flash('message', 'Category deleted successfully.');
        }
    }

    public function render()
    {
        $categories = Category::withCount('products')->paginate(10);
        
        return view('livewire.category-list', compact('categories'));
    }
}
```

### Component View
**File**: `resources/views/livewire/category-list.blade.php`
```blade
<div>
    @if (session()->has('message'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('message') }}
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Products Count
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($categories as $category)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $category->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $category->products_count }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('categories.edit', $category) }}" 
                               class="text-indigo-600 hover:text-indigo-900 mr-3">
                                Edit
                            </a>
                            
                            <button wire:click="deleteCategory({{ $category->id }})"
                                    wire:confirm="Are you sure you want to delete this category?"
                                    class="text-red-600 hover:text-red-900">
                                Delete
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $categories->links() }}
    </div>
</div>
```

## 4. Stock Movement List Component

### Component Class
**File**: `app/Livewire/StockMovementList.php`
```php
<?php

namespace App\Livewire;

use App\Models\StockMovement;
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class StockMovementList extends Component
{
    use WithPagination;

    public $productFilter = '';
    public $typeFilter = '';
    public $dateFrom = '';
    public $dateTo = '';

    protected $queryString = ['productFilter', 'typeFilter', 'dateFrom', 'dateTo'];

    public function updatingProductFilter()
    {
        $this->resetPage();
    }

    public function updatingTypeFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = StockMovement::with(['product', 'user'])
            ->when($this->productFilter, function ($query) {
                $query->where('product_id', $this->productFilter);
            })
            ->when($this->typeFilter, function ($query) {
                $query->where('type', $this->typeFilter);
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            })
            ->latest();

        $movements = $query->paginate(15);
        $products = Product::all();

        return view('livewire.stock-movement-list', compact('movements', 'products'));
    }
}
```

### Component View
**File**: `resources/views/livewire/stock-movement-list.blade.php`
```blade
<div>
    <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <select wire:model.live="productFilter" 
                class="rounded-md border-gray-300 shadow-sm">
            <option value="">All Products</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}">{{ $product->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="typeFilter" 
                class="rounded-md border-gray-300 shadow-sm">
            <option value="">All Types</option>
            <option value="in">Stock In</option>
            <option value="out">Stock Out</option>
        </select>

        <input type="date" 
               wire:model.live="dateFrom" 
               placeholder="From Date"
               class="rounded-md border-gray-300 shadow-sm">

        <input type="date" 
               wire:model.live="dateTo" 
               placeholder="To Date"
               class="rounded-md border-gray-300 shadow-sm">
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Date
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Product
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        SKU
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Type
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Quantity
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Reason
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        User
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($movements as $movement)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            {{ $movement->created_at->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $movement->product->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $movement->product->sku }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $movement->type === 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($movement->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $movement->quantity }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ ucfirst($movement->reason) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            {{ $movement->user->name }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $movements->links() }}
    </div>
</div>
```

## 5. Dashboard Stats Component

### Component Class
**File**: `app/Livewire/DashboardStats.php`
```php
<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use Livewire\Component;

class DashboardStats extends Component
{
    public $lowStockCount = 0;
    public $totalProducts = 0;
    public $todayMovements = 0;
    public $categoriesCount = 0;

    public function mount()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        $this->lowStockCount = Product::where('quantity', '<', 10)->count();
        $this->totalProducts = Product::count();
        $this->todayMovements = StockMovement::whereDate('created_at', today())->count();
        $this->categoriesCount = Category::count();
    }

    public function render()
    {
        return view('livewire.dashboard-stats');
    }
}
```

### Component View
**File**: `resources/views/livewire/dashboard-stats.blade.php`
```blade
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <!-- Total Products Card -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            Total Products
                        </dt>
                        <dd class="text-lg font-medium text-gray-900">
                            {{ $totalProducts }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Card -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            Low Stock Items
                        </dt>
                        <dd class="text-lg font-medium text-red-600">
                            {{ $lowStockCount }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Card -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            Categories
                        </dt>
                        <dd class="text-lg font-medium text-gray-900">
                            {{ $categoriesCount }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Movements Card -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            Today's Movements
                        </dt>
                        <dd class="text-lg font-medium text-gray-900">
                            {{ $todayMovements }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
```

## How to Create Livewire Components

Run these commands to create the Livewire components:
```bash
php artisan make:livewire ProductList
php artisan make:livewire StockAdjustment
php artisan make:livewire CategoryList
php artisan make:livewire StockMovementList
php artisan make:livewire DashboardStats
```

Then replace the generated files with the code provided above.

## Next Steps
1. Run the commands to create Livewire components
2. Replace the generated files with the provided code
3. Clear cache: `php artisan view:clear`
4. Move to Part 5 for Views implementation

# Stock Management System - Part 5: Views - Dashboard and Products

## 1. Dashboard View

**File**: `resources/views/dashboard.blade.php`
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Stats Cards -->
            <livewire:dashboard-stats />

            <!-- Categories Overview -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Products by Category</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($categoriesWithCount as $category)
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <h4 class="text-sm font-medium text-gray-500 truncate">
                                    {{ $category->name }}
                                </h4>
                                <p class="mt-1 text-3xl font-semibold text-gray-900">
                                    {{ $category->products_count }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Stock Movements -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Stock Movements</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Time
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Product
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Quantity
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        User
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($recentMovements as $movement)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $movement->created_at->diffForHumans() }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $movement->product->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $movement->type === 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ ucfirst($movement->type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $movement->quantity }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $movement->user->name }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            No stock movements yet
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

## 2. Products Index View

**File**: `resources/views/products/index.blade.php`
```blade
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Products') }}
            </h2>
            @can('manage-products')
                <a href="{{ route('products.create') }}" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Add New Product
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <livewire:product-list />
        </div>
    </div>
</x-app-layout>
```

## 3. Create Product View

**File**: `resources/views/products/create.blade.php`
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add New Product') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('products.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">
                                Product Name
                            </label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   value="{{ old('name') }}"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror"
                                   required>
                            @error('name')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="sku" class="block text-gray-700 text-sm font-bold mb-2">
                                SKU
                            </label>
                            <input type="text" 
                                   name="sku" 
                                   id="sku" 
                                   value="{{ old('sku') }}"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('sku') border-red-500 @enderror"
                                   required>
                            @error('sku')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="quantity" class="block text-gray-700 text-sm font-bold mb-2">
                                Initial Quantity
                            </label>
                            <input type="number" 
                                   name="quantity" 
                                   id="quantity" 
                                   value="{{ old('quantity', 0) }}"
                                   min="0"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('quantity') border-red-500 @enderror"
                                   required>
                            @error('quantity')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="category_id" class="block text-gray-700 text-sm font-bold mb-2">
                                Category
                            </label>
                            <select name="category_id" 
                                    id="category_id" 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('category_id') border-red-500 @enderror"
                                    required>
                                <option value="">Select a category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Save Product
                            </button>
                            <a href="{{ route('products.index') }}" 
                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

## 4. Edit Product View

**File**: `resources/views/products/edit.blade.php`
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Product') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('products.update', $product) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">
                                Product Name
                            </label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   value="{{ old('name', $product->name) }}"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror"
                                   required>
                            @error('name')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="sku" class="block text-gray-700 text-sm font-bold mb-2">
                                SKU
                            </label>
                            <input type="text" 
                                   name="sku" 
                                   id="sku" 
                                   value="{{ old('sku', $product->sku) }}"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('sku') border-red-500 @enderror"
                                   required>
                            @error('sku')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="quantity" class="block text-gray-700 text-sm font-bold mb-2">
                                Current Quantity
                            </label>
                            <input type="number" 
                                   name="quantity" 
                                   id="quantity" 
                                   value="{{ old('quantity', $product->quantity) }}"
                                   min="0"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('quantity') border-red-500 @enderror"
                                   required>
                            @error('quantity')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="category_id" class="block text-gray-700 text-sm font-bold mb-2">
                                Category
                            </label>
                            <select name="category_id" 
                                    id="category_id" 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('category_id') border-red-500 @enderror"
                                    required>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Update Product
                            </button>
                            <a href="{{ route('products.index') }}" 
                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

## 5. Adjust Stock View

**File**: `resources/views/products/adjust-stock.blade.php`
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Adjust Stock') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if (session('success'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    <livewire:stock-adjustment :product="$product" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

## Next Steps
1. Create all view files in the specified locations
2. Ensure the folder structure exists: `resources/views/products/`
3. Clear view cache: `php artisan view:clear`
4. Move to Part 6 for remaining views

# Stock Management System - Part 6: Views - Categories, Stock Movements, and Users

## 1. Categories Index View

**File**: `resources/views/categories/index.blade.php`
```blade
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Categories') }}
            </h2>
            <a href="{{ route('categories.create') }}" 
               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add New Category
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <livewire:category-list />
        </div>
    </div>
</x-app-layout>
```

## 2. Create Category View

**File**: `resources/views/categories/create.blade.php`
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add New Category') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('categories.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">
                                Category Name
                            </label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   value="{{ old('name') }}"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror"
                                   required>
                            @error('name')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Save Category
                            </button>
                            <a href="{{ route('categories.index') }}" 
                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

## 4. Stock Movements Index View

**File**: `resources/views/stock-movements/index.blade.php`
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Stock Movements') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <livewire:stock-movement-list />
        </div>
    </div>
</x-app-layout>
```

## 5. Users Index View

**File**: `resources/views/users/index.blade.php`
```blade
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Users') }}
            </h2>
            <a href="{{ route('users.create') }}" 
               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add New User
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Role
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($users as $user)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ $user->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ $user->email }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        @if($user->role === 'admin') bg-purple-100 text-purple-800
                                        @elseif($user->role === 'manager') bg-blue-100 text-blue-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('users.edit', $user) }}" 
                                       class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        Edit
                                    </a>
                                    
                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('users.destroy', $user) }}" 
                                              method="POST" 
                                              class="inline"
                                              onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="text-red-600 hover:text-red-900">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
```

## 6. Create User View

**File**: `resources/views/users/create.blade.php`
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add New User') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">
                                Name
                            </label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   value="{{ old('name') }}"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror"
                                   required>
                            @error('name')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">
                                Email
                            </label>
                            <input type="email" 
                                   name="email" 
                                   id="email" 
                                   value="{{ old('email') }}"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('email') border-red-500 @enderror"
                                   required>
                            @error('email')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password" class="block text-gray-700 text-sm font-bold mb-2">
                                Password
                            </label>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('password') border-red-500 @enderror"
                                   required>
                            @error('password')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="block text-gray-700 text-sm font-bold mb-2">
                                Confirm Password
                            </label>
                            <input type="password" 
                                   name="password_confirmation" 
                                   id="password_confirmation" 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                   required>
                        </div>

                        <div class="mb-4">
                            <label for="role" class="block text-gray-700 text-sm font-bold mb-2">
                                Role
                            </label>
                            <select name="role" 
                                    id="role" 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('role') border-red-500 @enderror"
                                    required>
                                <option value="">Select a role</option>
                                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                                <option value="manager" {{ old('role') === 'manager' ? 'selected' : '' }}>Manager</option>
                                <option value="stock_worker" {{ old('role') === 'stock_worker' ? 'selected' : '' }}>Stock Worker</option>
                            </select>
                            @error('role')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Create User
                            </button>
                            <a href="{{ route('users.index') }}" 
                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

## 7. Edit User View

**File**: `resources/views/users/edit.blade.php`
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit User') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('users.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">
                                Name
                            </label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   value="{{ old('name', $user->name) }}"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror"
                                   required>
                            @error('name')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">
                                Email
                            </label>
                            <input type="email" 
                                   name="email" 
                                   id="email" 
                                   value="{{ old('email', $user->email) }}"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('email') border-red-500 @enderror"
                                   required>
                            @error('email')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password" class="block text-gray-700 text-sm font-bold mb-2">
                                Password <span class="text-sm font-normal">(leave blank to keep current)</span>
                            </label>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('password') border-red-500 @enderror">
                            @error('password')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="block text-gray-700 text-sm font-bold mb-2">
                                Confirm Password
                            </label>
                            <input type="password" 
                                   name="password_confirmation" 
                                   id="password_confirmation" 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div class="mb-4">
                            <label for="role" class="block text-gray-700 text-sm font-bold mb-2">
                                Role
                            </label>
                            <select name="role" 
                                    id="role" 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('role') border-red-500 @enderror"
                                    required>
                                <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                                <option value="manager" {{ old('role', $user->role) === 'manager' ? 'selected' : '' }}>Manager</option>
                                <option value="stock_worker" {{ old('role', $user->role) === 'stock_worker' ? 'selected' : '' }}>Stock Worker</option>
                            </select>
                            @error('role')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Update User
                            </button>
                            <a href="{{ route('users.index') }}" 
                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

## Folder Structure
Create these folders in your `resources/views/` directory:
- `categories/`
- `products/`
- `stock-movements/`
- `users/`

## Next Steps
1. Create all the folders mentioned above
2. Create all view files in their respective folders
3. Clear view cache: `php artisan view:clear`
4. Move to Part 7 for final setup and testing

## 3. Edit Category View

**File**: `resources/views/categories/edit.blade.php`
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Category') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('categories.update', $category) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">
                                Category Name
                            </label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   value="{{ old('name', $category->name) }}"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror"
                                   required>
                            @error('name')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Update Category
                            </button>
                            <a href="{{ route('categories.index') }}" 
                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Cancel
                            </a>
                        </div>

                        # Stock Management System - Part 7: Final Setup & Testing Guide

## 1. Complete Installation Steps

### Step 1: Clear all caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Step 2: Run migrations and seeders
```bash
php artisan migrate:fresh --seed
```

### Step 3: Compile assets
```bash
npm install
npm run build
```

### Step 4: Start the development server
```bash
php artisan serve
```

## 2. Middleware Registration Fix

**File**: `bootstrap/app.php`
Update the file to register the middleware:
```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

## 3. Testing Guide

### Test User Logins
1. **Admin Login**
   - Email: admin@example.com
   - Password: password
   - Should see: All menu items (Dashboard, Products, Stock Movements, Categories, Users)

2. **Manager Login**
   - Email: manager@example.com
   - Password: password
   - Should see: Dashboard, Products, Stock Movements, Categories (No Users menu)

3. **Stock Worker Login**
   - Email: worker@example.com
   - Password: password
   - Should see: Dashboard, Products, Stock Movements (No Categories or Users)

### Feature Testing Checklist

#### Dashboard
- [ ] Stats cards show correct numbers
- [ ] Low stock alert shows products with quantity < 10
- [ ] Recent movements display correctly
- [ ] Category product counts are accurate

#### Products Management
- [ ] Product list displays with search and filter
- [ ] Admin/Manager can add new products
- [ ] Admin/Manager can edit products
- [ ] Admin/Manager can delete products
- [ ] Stock Worker can only view and adjust stock
- [ ] Low stock items show in red

#### Stock Adjustment
- [ ] Stock In increases quantity
- [ ] Stock Out decreases quantity
- [ ] Cannot reduce stock below 0
- [ ] Movement history is recorded
- [ ] Correct user is logged for each movement

#### Categories
- [ ] Only Admin/Manager can access
- [ ] Can create new categories
- [ ] Can edit existing categories
- [ ] Cannot delete category with products
- [ ] Product count shows correctly

#### Stock Movements
- [ ] Shows all movements with filters
- [ ] Date filter works
- [ ] Product filter works
- [ ] Type filter (In/Out) works
- [ ] Shows correct user who made the change

#### User Management
- [ ] Only Admin can access
- [ ] Can create new users
- [ ] Can edit user roles
- [ ] Cannot delete own account
- [ ] Role badges display correctly

## 4. Common Issues & Solutions

### Issue: Livewire components not loading
**Solution:**
```bash
php artisan livewire:discover
php artisan view:clear
```

### Issue: Tailwind styles not working
**Solution:**
```bash
npm run build
# or for development
npm run dev
```

### Issue: Routes not found
**Solution:**
```bash
php artisan route:clear
php artisan route:cache
```

### Issue: Database errors
**Solution:**
```bash
php artisan migrate:fresh --seed
```

## 5. Project Structure Summary

```
stock-management/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DashboardController.php
│   │   │   ├── ProductController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── StockMovementController.php
│   │   │   └── UserController.php
│   │   └── Middleware/
│   │       └── CheckRole.php
│   ├── Livewire/
│   │   ├── ProductList.php
│   │   ├── StockAdjustment.php
│   │   ├── CategoryList.php
│   │   ├── StockMovementList.php
│   │   └── DashboardStats.php
│   ├── Models/
│   │   ├── Category.php
│   │   ├── Product.php
│   │   ├── StockMovement.php
│   │   └── User.php (updated)
│   └── Providers/
│       └── AuthServiceProvider.php (updated)
├── database/
│   ├── migrations/
│   │   ├── [timestamp]_add_role_to_users_table.php
│   │   ├── [timestamp]_create_categories_table.php
│   │   ├── [timestamp]_create_products_table.php
│   │   └── [timestamp]_create_stock_movements_table.php
│   └── seeders/
│       ├── CategorySeeder.php
│       ├── ProductSeeder.php
│       ├── UserSeeder.php
│       └── DatabaseSeeder.php
├── resources/
│   └── views/
│       ├── dashboard.blade.php
│       ├── livewire/
│       │   ├── product-list.blade.php
│       │   ├── stock-adjustment.blade.php
│       │   ├── category-list.blade.php
│       │   ├── stock-movement-list.blade.php
│       │   └── dashboard-stats.blade.php
│       ├── products/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   ├── edit.blade.php
│       │   └── adjust-stock.blade.php
│       ├── categories/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   └── edit.blade.php
│       ├── stock-movements/
│       │   └── index.blade.php
│       └── users/
│           ├── index.blade.php
│           ├── create.blade.php
│           └── edit.blade.php
└── routes/
    └── web.php (updated)
```

## 6. Learning Objectives Achieved

This project helps students learn:

1. **Laravel Basics**
   - MVC pattern
   - Routing
   - Controllers
   - Models and relationships
   - Migrations and seeders

2. **Livewire Concepts**
   - Components and real-time updates
   - Form handling
   - Wire:model binding
   - Component lifecycle
   - Pagination with Livewire

3. **Authentication & Authorization**
   - Laravel Breeze setup
   - Gates for permissions
   - Role-based access control
   - Middleware usage

4. **Database Management**
   - Eloquent ORM
   - Relationships (hasMany, belongsTo)
   - Soft deletes
   - Query optimization

5. **UI/UX with Tailwind**
   - Responsive design
   - Component styling
   - Status indicators
   - Flash messages

## 7. Next Steps for Students

1. **Add Features:**
   - Export stock reports to PDF/Excel
   - Email notifications for low stock
   - Barcode scanning support
   - Multiple warehouse support

2. **Improve UI:**
   - Add charts for visual analytics
   - Dark mode support
   - Mobile app version
   - Print-friendly views

3. **Advanced Concepts:**
   - API development
   - Unit testing
   - Job queues for reports
   - Real-time notifications

## Final Notes

- Always run seeders for demo data
- Use the test accounts to explore different roles
- Check browser console for Livewire errors
- Keep the project simple for learning
- Focus on understanding core concepts