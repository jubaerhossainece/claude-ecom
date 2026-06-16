<?php

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Component;

class CartBadge extends Component
{
    public int $count = 0;

    protected $listeners = ['cart-updated' => 'refresh'];

    public function mount(CartService $cartService): void
    {
        $this->count = $cartService->getItemCount();
    }

    public function refresh(CartService $cartService): void
    {
        $this->count = $cartService->getItemCount();
    }

    public function render()
    {
        return view('livewire.cart-badge');
    }
}
