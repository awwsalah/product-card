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