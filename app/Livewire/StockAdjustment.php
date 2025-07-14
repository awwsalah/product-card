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