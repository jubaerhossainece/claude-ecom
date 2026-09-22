<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\SearchQuery;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            if ($value = $request->get('attr_'.$attr->slug)) {
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

        $reviews = $product->reviews()->where('is_approved', true)->with(['customer', 'media'])->latest()->limit(10)->get();
        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->active()
            ->with('media')
            ->limit(6)
            ->get();

        $myReview = null;
        $canReview = false;
        if ($customer = Auth::guard('customer')->user()) {
            $myReview = Review::where('customer_id', $customer->id)->where('product_id', $product->id)->first();
            $canReview = ! $myReview && Order::where('customer_id', $customer->id)
                ->where('status', 'delivered')
                ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
                ->exists();
        }

        return view('storefront.products.show', compact('product', 'reviews', 'related', 'myReview', 'canReview'));
    }

    public function search(Request $request)
    {
        $q = $request->get('q', '');
        $store = Store::current();

        $products = collect();
        if (strlen($q) >= 2) {
            $ids = Product::search($q)
                ->where('store_id', $store->id)
                ->where('status', 'active')
                ->keys();

            $query = Product::whereIn('id', $ids)->with('category', 'media');

            if ($categoryId = $request->get('category_id')) {
                $query->where('category_id', $categoryId);
            }
            if ($min = $request->get('price_min')) {
                $query->where('base_price', '>=', $min);
            }
            if ($max = $request->get('price_max')) {
                $query->where('base_price', '<=', $max);
            }

            $sort = $request->get('sort', 'relevance');
            match ($sort) {
                'price_asc' => $query->orderBy('base_price'),
                'price_desc' => $query->orderByDesc('base_price'),
                'newest' => $query->latest(),
                default => $query->orderByRaw('FIELD(id, '.($ids->isNotEmpty() ? $ids->implode(',') : '0').')'),
            };

            $products = $query->paginate(24)->withQueryString();

            SearchQuery::create([
                'store_id' => $store->id,
                'customer_id' => auth('customer')->id(),
                'session_id' => $request->session()->getId(),
                'query' => $q,
                'results_count' => $products->total(),
            ]);
        }

        $categories = Category::where('store_id', $store->id)->active()->orderBy('name')->get();
        $sort = $request->get('sort', 'relevance');

        $popularSearches = SearchQuery::where('store_id', $store->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('query, COUNT(*) as searches')
            ->groupBy('query')
            ->orderByDesc('searches')
            ->limit(8)
            ->pluck('query');

        return view('storefront.products.search', compact('q', 'products', 'categories', 'sort', 'popularSearches'));
    }

    public function autocomplete(Request $request)
    {
        $q = $request->get('q', '');
        $store = Store::current();

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $products = Product::search($q)
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->take(6)
            ->get();

        return response()->json($products->map(fn ($p) => [
            'name' => $p->name,
            'url' => route('products.show', $p->slug),
            'thumbnail' => $p->thumbnail_url,
            'price' => $p->effective_price,
        ]));
    }
}
