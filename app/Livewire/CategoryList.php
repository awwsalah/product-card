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