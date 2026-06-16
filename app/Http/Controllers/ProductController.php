<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function category(Request $request, Category $category)
    {
        $store = Store::current();

        $query = Product::where('store_id', $store->id)
            ->where('category_id', $category->id)
            ->active()
            ->with('category', 'media');

        // Attribute filters
        $filterableAttributes = $category->attributes()->where('is_filterable', true)->get();
        foreach ($filterableAttributes as $attr) {
            if ($value = $request->get('attr_' . $attr->slug)) {
                $query->whereHas('attributeValues', function ($q) use ($attr, $value) {
                    $q->where('attribute_id', $attr->id)->where('value', $value);
                });
            }
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        match ($sort) {
            'price_asc' => $query->orderBy('base_price'),
            'price_desc' => $query->orderByDesc('base_price'),
            'popular' => $query->orderByDesc('sort_order'),
            default => $query->latest(),
        };

        $products = $query->paginate(24)->withQueryString();
        $subcategories = $category->children()->active()->withCount('products')->get();

        return view('storefront.products.category', compact('category', 'products', 'subcategories', 'filterableAttributes', 'sort'));
    }

    public function show(Product $product)
    {
        abort_if($product->status !== 'active', 404);

        $product->load([
            'category.attributes',
            'attributeValues.attribute',
            'variants' => fn ($q) => $q->where('is_active', true),
            'media',
        ]);

        $reviews = $product->reviews()->where('is_approved', true)->with('customer')->latest()->limit(10)->get();
        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->active()
            ->with('media')
            ->limit(6)
            ->get();

        return view('storefront.products.show', compact('product', 'reviews', 'related'));
    }

    public function search(Request $request)
    {
        $q = $request->get('q', '');
        $store = Store::current();

        $products = collect();
        if (strlen($q) >= 2) {
            $products = Product::search($q)
                ->where('store_id', $store->id)
                ->where('status', 'active')
                ->paginate(24);
        }

        return view('storefront.products.search', compact('q', 'products'));
    }
}
