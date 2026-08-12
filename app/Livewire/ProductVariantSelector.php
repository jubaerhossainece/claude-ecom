<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Livewire\Component;

class ProductVariantSelector extends Component
{
    public Product $product;
    public array $selectedAttributes = [];
    public ?int $selectedVariantId = null;
    public int $quantity = 1;
    public bool $added = false;

    public function mount(): void
    {
        // Build the attribute axes from variants
        $this->selectedAttributes = [];
    }

    public function getVariantAttributesProperty(): array
    {
        $axes = [];
        foreach ($this->product->variants as $variant) {
            foreach ($variant->attribute_values as $slug => $value) {
                $axes[$slug][$value] = $value;
            }
        }
        return $axes;
    }

    public function updatedSelectedAttributes(): void
    {
        $this->selectedVariantId = null;
        foreach ($this->product->variants as $variant) {
            if ($variant->attribute_values === $this->selectedAttributes && $variant->is_active) {
                $this->selectedVariantId = $variant->id;
                break;
            }
        }
    }

    public function getSelectedVariantProperty(): ?ProductVariant
    {
        return $this->selectedVariantId
            ? $this->product->variants->find($this->selectedVariantId)
            : null;
    }

    public function increment(): void
    {
        $this->quantity++;
    }

    public function decrement(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function addToCart(CartService $cartService): void
    {
        $variantId = $this->selectedVariantId;

        if ($this->product->variants->isNotEmpty() && ! $variantId) {
            $this->addError('variant', 'Please select all options.');
            return;
        }

        $cartService->addItem($this->product->id, $this->quantity, $variantId);
        $this->added = true;
        $this->dispatch('cart-updated');

        $this->js('setTimeout(() => $wire.added = false, 2000)');
    }

    public function render()
    {
        return view('livewire.product-variant-selector');
    }
}
