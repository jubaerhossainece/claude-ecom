@extends('storefront.layouts.app')

@section('title', $product->meta_title ?? $product->name . ' - ' . ($store->name ?? ''))
@section('meta_description', $product->meta_description ?? $product->short_description)

@section('content')
@php $symbol = $store?->getSetting('currency_symbol', '৳'); @endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-500 mb-6 flex gap-2 items-center">
        <a href="{{ route('home') }}" class="hover:text-primary">Home</a>
        @if($product->category)
            <span>/</span>
            <a href="{{ route('products.category', $product->category) }}" class="hover:text-primary">{{ $product->category->name }}</a>
        @endif
        <span>/</span>
        <span class="text-gray-800">{{ $product->name }}</span>
    </nav>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
        {{-- Images --}}
        <div>
            @php
                $images = $product->getMedia('images');
                $thumb = $product->getFirstMedia('thumbnail');
                $allImages = $thumb ? collect([$thumb])->merge($images) : $images;
            @endphp
            <div class="aspect-square rounded-xl overflow-hidden bg-gray-100 mb-4">
                <img id="main-image"
                     src="{{ $allImages->first()?->getUrl('medium') ?? $product->thumbnail_url }}"
                     alt="{{ $product->name }}"
                     class="w-full h-full object-cover">
            </div>
            @if($allImages->count() > 1)
                <div class="grid grid-cols-5 gap-2">
                    @foreach($allImages as $media)
                        <button onclick="document.getElementById('main-image').src='{{ $media->getUrl('medium') }}'"
                                class="aspect-square rounded-lg overflow-hidden border-2 border-transparent hover:border-primary focus:border-primary">
                            <img src="{{ $media->getUrl('thumb') }}" alt="" class="w-full h-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Details --}}
        <div>
            @if($product->category)
                <a href="{{ route('products.category', $product->category) }}" class="text-sm text-primary font-medium">{{ $product->category->name }}</a>
            @endif
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mt-1 mb-4">{{ $product->name }}</h1>

            {{-- Reviews summary --}}
            @if($reviews->isNotEmpty())
                <div class="flex items-center gap-2 mb-4">
                    @php $avg = round($reviews->avg('rating'), 1); @endphp
                    <div class="flex text-yellow-400">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="w-4 h-4 {{ $i <= $avg ? 'fill-current' : 'text-gray-200 fill-current' }}" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                    <span class="text-sm text-gray-600">{{ $avg }}/5 ({{ $reviews->count() }} reviews)</span>
                </div>
            @endif

            {{-- Price --}}
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-6">
                <div class="flex items-center gap-3">
                    <span class="text-3xl font-bold text-primary">
                        {{ $symbol }}{{ number_format($product->effective_price, 0) }}
                    </span>
                    @if($product->is_on_sale)
                        <span class="text-lg text-gray-400 line-through">{{ $symbol }}{{ number_format($product->base_price, 0) }}</span>
                        <span class="bg-red-100 text-red-600 px-2 py-1 rounded text-sm font-bold">
                            {{ round((1 - $product->sale_price / $product->base_price) * 100) }}% OFF
                        </span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 mt-1">Per {{ $product->unit_of_sale }}</p>
            </div>

            {{-- Variants via Livewire --}}
            @if($product->variants->isNotEmpty())
                @livewire('product-variant-selector', ['product' => $product])
            @else
                @livewire('add-to-cart-button', ['product' => $product])
            @endif

            {{-- Stock info --}}
            @if(!$product->is_in_stock)
                <div class="mt-3 text-sm text-red-600 font-medium">❌ Out of stock</div>
            @elseif($product->is_low_stock)
                <div class="mt-3 text-sm text-amber-600 font-medium">⚠️ Only {{ $product->stock_quantity }} left — order soon!</div>
            @endif

            {{-- Delivery info --}}
            <div class="mt-6 border border-gray-200 rounded-xl p-4 space-y-2 text-sm text-gray-600">
                <div class="flex gap-3 items-start">
                    <span class="text-lg">🚚</span>
                    <div>
                        <p class="font-medium text-gray-800">Delivery Info</p>
                        <p>Inside Dhaka: {{ $symbol }}{{ $store?->getSetting('delivery_inside_dhaka', 60) }} (1-2 days)</p>
                        <p>Outside Dhaka: {{ $symbol }}{{ $store?->getSetting('delivery_outside_dhaka', 120) }} (3-5 days)</p>
                    </div>
                </div>
                <div class="flex gap-3 items-start">
                    <span class="text-lg">💵</span>
                    <div>
                        <p class="font-medium text-gray-800">Cash on Delivery</p>
                        <p>Pay when you receive your order</p>
                    </div>
                </div>
            </div>

            {{-- Wishlist --}}
            @auth('customer')
                <form action="{{ route('account.wishlist.toggle', $product) }}" method="POST" class="mt-4">
                    @csrf
                    <button type="submit" class="text-sm text-gray-500 hover:text-primary flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        Add to Wishlist
                    </button>
                </form>
            @endauth
        </div>
    </div>

    {{-- Tabs: Description / Attributes / Reviews --}}
    <div class="mt-12" x-data="{ tab: 'description' }">
        <div class="border-b border-gray-200 flex gap-6">
            @foreach(['description' => 'Description', 'attributes' => 'Specifications', 'reviews' => 'Reviews (' . $reviews->count() . ')'] as $key => $label)
                <button @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'border-b-2 border-primary text-primary' : 'text-gray-500'"
                        class="pb-3 text-sm font-medium transition-colors">{{ $label }}</button>
            @endforeach
        </div>

        <div x-show="tab === 'description'" class="py-6 prose max-w-none text-gray-700">
            {!! $product->description ?? '<p>' . $product->short_description . '</p>' !!}
        </div>

        <div x-show="tab === 'attributes'" class="py-6">
            @if($product->attributeValues->isNotEmpty())
                <table class="w-full text-sm">
                    @foreach($product->attributeValues as $attrVal)
                        <tr class="border-b border-gray-100">
                            <td class="py-3 pr-6 font-medium text-gray-600 w-1/3">{{ $attrVal->attribute->name }}</td>
                            <td class="py-3 text-gray-800">{{ $attrVal->display_value }}</td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p class="text-gray-500">No specifications available.</p>
            @endif
        </div>

        <div x-show="tab === 'reviews'" class="py-6">
            @if($myReview)
                <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 mb-6">
                    <p class="text-sm font-medium text-gray-700 mb-2">
                        Your review {{ $myReview->is_approved ? '' : '(awaiting approval)' }}
                    </p>
                    <form action="{{ route('reviews.update', $myReview) }}" method="POST" enctype="multipart/form-data" class="space-y-2">
                        @csrf @method('PUT')
                        <div class="flex gap-1 text-lg" x-data="{ rating: {{ $myReview->rating }} }">
                            <template x-for="i in 5" :key="i">
                                <button type="button" @click="rating = i" :class="i <= rating ? 'text-yellow-400' : 'text-gray-300'">★</button>
                            </template>
                            <input type="hidden" name="rating" x-model="rating">
                        </div>
                        <input type="text" name="title" value="{{ $myReview->title }}" placeholder="Title (optional)" class="w-full rounded-lg border-gray-300 text-sm">
                        <textarea name="body" rows="2" placeholder="Your review" class="w-full rounded-lg border-gray-300 text-sm">{{ $myReview->body }}</textarea>
                        <input type="file" name="photos[]" multiple accept="image/*" class="text-xs">
                        <div class="flex gap-3">
                            <button type="submit" class="text-xs font-medium bg-primary text-white rounded-lg px-3 py-1.5">Update Review</button>
                        </div>
                    </form>
                    <form action="{{ route('reviews.destroy', $myReview) }}" method="POST" class="mt-2">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:underline">Delete review</button>
                    </form>
                </div>
            @elseif($canReview)
                <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 mb-6" x-data="{ rating: 5 }">
                    <p class="text-sm font-medium text-gray-700 mb-2">Write a review</p>
                    <form action="{{ route('reviews.store', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-2">
                        @csrf
                        <div class="flex gap-1 text-lg">
                            <template x-for="i in 5" :key="i">
                                <button type="button" @click="rating = i" :class="i <= rating ? 'text-yellow-400' : 'text-gray-300'">★</button>
                            </template>
                            <input type="hidden" name="rating" x-model="rating">
                        </div>
                        <input type="text" name="title" placeholder="Title (optional)" class="w-full rounded-lg border-gray-300 text-sm">
                        <textarea name="body" rows="2" placeholder="Your review" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                        <input type="file" name="photos[]" multiple accept="image/*" class="text-xs">
                        <button type="submit" class="text-xs font-medium bg-primary text-white rounded-lg px-3 py-1.5">Submit Review</button>
                    </form>
                </div>
            @elseif(auth('customer')->check())
                <p class="text-xs text-gray-400 mb-6">You can review this product after it's delivered to you.</p>
            @endif

            @forelse($reviews as $review)
                <div class="border-b border-gray-100 pb-4 mb-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="flex text-yellow-400">
                            @for($i = 1; $i <= 5; $i++)
                                <span>{{ $i <= $review->rating ? '★' : '☆' }}</span>
                            @endfor
                        </div>
                        <span class="font-medium text-sm">{{ $review->customer?->name ?? 'Anonymous' }}</span>
                        <span class="text-xs text-gray-400">{{ $review->created_at->diffForHumans() }}</span>
                    </div>
                    @if($review->title) <p class="font-medium text-gray-800 text-sm">{{ $review->title }}</p> @endif
                    @if($review->body) <p class="text-gray-600 text-sm mt-1">{{ $review->body }}</p> @endif
                    @if($review->getMedia('photos')->isNotEmpty())
                        <div class="flex gap-2 mt-2">
                            @foreach($review->getMedia('photos') as $photo)
                                <a href="{{ $photo->getUrl() }}" target="_blank">
                                    <img src="{{ $photo->getUrl() }}" class="w-14 h-14 object-cover rounded-lg border border-gray-200">
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-gray-500 text-sm">No reviews yet. Be the first!</p>
            @endforelse
        </div>
    </div>

    {{-- Related products --}}
    @if($related->isNotEmpty())
        <div class="mt-10">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Related Products</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($related as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
@endpush
