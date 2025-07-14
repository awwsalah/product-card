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