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