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