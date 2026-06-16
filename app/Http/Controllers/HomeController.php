<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;

class HomeController extends Controller
{
    public function index()
    {
        $store = Store::current();

        $categories = Category::where('store_id', $store->id)
            ->whereNull('parent_id')
            ->active()
            ->orderBy('sort_order')
            ->withCount('products')
            ->with('children')
            ->get();

        $featured = Product::where('store_id', $store->id)
            ->active()
            ->featured()
            ->with('category', 'media')
            ->orderBy('sort_order')
            ->limit(8)
            ->get();

        $latest = Product::where('store_id', $store->id)
            ->active()
            ->with('category', 'media')
            ->latest()
            ->limit(8)
            ->get();

        return view('storefront.home', compact('store', 'categories', 'featured', 'latest'));
    }
}
