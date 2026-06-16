<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\CartService;
use Livewire\Component;

class AddToCartButton extends Component
{
    public Product $product;
    public int $quantity = 1;
    public bool $added = false;

    public function addToCart(CartService $cartService): void
    {
        if (! $this->product->is_in_stock) {
            return;
        }

        $cartService->addItem($this->product->id, $this->quantity);
        $this->added = true;
        $this->dispatch('cart-updated');

        $this->js('setTimeout(() => $wire.added = false, 2000)');
    }

    public function render()
    {
        return view('livewire.add-to-cart-button');
    }
}
