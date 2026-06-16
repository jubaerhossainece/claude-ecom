<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $store->name ?? config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', $store?->getSetting('meta_description', ''))">

    @php $primaryColor = $store?->getSetting('primary_color', '#16a34a'); @endphp
    <style>
        :root { --color-primary: {{ $primaryColor }}; }
        .bg-primary { background-color: var(--color-primary); }
        .text-primary { color: var(--color-primary); }
        .border-primary { border-color: var(--color-primary); }
        .hover\:bg-primary:hover { background-color: var(--color-primary); }
        .btn-primary { background-color: var(--color-primary); color: white; padding: .5rem 1.25rem; border-radius: .375rem; font-weight: 600; transition: opacity .15s; }
        .btn-primary:hover { opacity: .9; }
    </style>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 font-sans antialiased" style="font-family: 'Inter', 'Hind Siliguri', sans-serif;">

{{-- Top bar --}}
<div class="bg-gray-800 text-white text-xs py-1.5 text-center">
    @if($store?->getSetting('support_phone'))
        📞 {{ $store->getSetting('support_phone') }}
        &nbsp;|&nbsp;
    @endif
    বাংলাদেশে দ্রুত ডেলিভারি | Fast delivery across Bangladesh
</div>

{{-- Header --}}
<header class="bg-white shadow-sm sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-16">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                @if($store && $store->getFirstMedia('logo'))
                    <img src="{{ $store->getFirstMedia('logo')->getUrl() }}" alt="{{ $store->name }}" class="h-10">
                @else
                    <span class="text-xl font-bold text-primary">{{ $store->name ?? config('app.name') }}</span>
                @endif
            </a>

            {{-- Search --}}
            <form action="{{ route('products.search') }}" method="GET" class="hidden md:flex flex-1 max-w-lg mx-6">
                <div class="relative w-full">
                    <input type="search" name="q" value="{{ request('q') }}"
                           placeholder="Search products..."
                           class="w-full border border-gray-300 rounded-full px-5 py-2 pr-12 text-sm focus:outline-none focus:border-primary">
                    <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-primary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                </div>
            </form>

            {{-- Nav icons --}}
            <div class="flex items-center gap-4">
                @auth('customer')
                    <a href="{{ route('account.index') }}" class="text-gray-600 hover:text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </a>
                    <a href="{{ route('account.wishlist') }}" class="text-gray-600 hover:text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    </a>
                @else
                    <a href="{{ route('customer.login') }}" class="text-gray-600 hover:text-primary text-sm font-medium">Login</a>
                @endauth

                {{-- Cart --}}
                <a href="{{ route('cart.index') }}" class="relative text-gray-600 hover:text-primary flex items-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    @livewire('cart-badge')
                </a>
            </div>
        </div>

        {{-- Mobile search --}}
        <div class="md:hidden pb-3">
            <form action="{{ route('products.search') }}" method="GET">
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="Search products..."
                       class="w-full border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none">
            </form>
        </div>
    </div>
</header>

{{-- Category Nav --}}
<nav class="bg-white border-b overflow-x-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex gap-6 py-2 whitespace-nowrap">
            @foreach(\App\Models\Category::where('store_id', $store->id ?? 0)->whereNull('parent_id')->active()->orderBy('sort_order')->limit(10)->get() as $navCat)
                <a href="{{ route('products.category', $navCat) }}"
                   class="text-sm text-gray-600 hover:text-primary font-medium py-1 {{ request()->route('category')?->is($navCat) ? 'text-primary border-b-2 border-primary' : '' }}">
                    {{ $navCat->name }}
                </a>
            @endforeach
        </div>
    </div>
</nav>

{{-- Flash Messages --}}
@if(session('success'))
    <div class="max-w-7xl mx-auto px-4 mt-4">
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
    </div>
@endif
@if(session('error'))
    <div class="max-w-7xl mx-auto px-4 mt-4">
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">{{ session('error') }}</div>
    </div>
@endif

{{-- Main Content --}}
<main>
    @yield('content')
</main>

{{-- Footer --}}
<footer class="bg-gray-800 text-gray-300 mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h3 class="text-white font-bold text-lg mb-3">{{ $store->name ?? '' }}</h3>
                <p class="text-sm">{{ $store->tagline ?? '' }}</p>
                @if($store?->getSetting('support_phone'))
                    <p class="text-sm mt-3">📞 {{ $store->getSetting('support_phone') }}</p>
                @endif
            </div>
            <div>
                <h4 class="text-white font-semibold mb-3">Quick Links</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('home') }}" class="hover:text-white">Home</a></li>
                    <li><a href="{{ route('account.orders') }}" class="hover:text-white">My Orders</a></li>
                    <li><a href="{{ route('cart.index') }}" class="hover:text-white">Cart</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-3">Payment Methods</h4>
                <div class="flex gap-3 flex-wrap">
                    @if($store?->getSetting('payment_cod_enabled'))
                        <span class="bg-gray-700 text-xs px-3 py-1 rounded">Cash on Delivery</span>
                    @endif
                    @if($store?->getSetting('payment_bkash_enabled'))
                        <span class="bg-pink-800 text-xs px-3 py-1 rounded">bKash</span>
                    @endif
                    @if($store?->getSetting('payment_sslcommerz_enabled'))
                        <span class="bg-blue-800 text-xs px-3 py-1 rounded">SSLCommerz</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="border-t border-gray-700 mt-8 pt-6 text-center text-xs text-gray-500">
            &copy; {{ date('Y') }} {{ $store->name ?? '' }}. All rights reserved.
        </div>
    </div>
</footer>

@livewireScripts
</body>
</html>
