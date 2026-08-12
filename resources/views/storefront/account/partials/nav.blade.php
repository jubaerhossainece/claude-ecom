@php
    $navLinks = [
        'account.profile' => 'Profile',
        'account.orders' => 'Orders',
        'account.wishlist' => 'Wishlist',
        'account.coupons' => 'Coupons',
        'account.notifications' => 'Notifications',
        'account.returns' => 'Returns',
    ];
@endphp
<div class="flex gap-4 items-center">
    @foreach($navLinks as $route => $label)
        <a href="{{ route($route) }}"
           class="text-sm font-medium {{ request()->routeIs($route) ? 'text-primary' : 'text-gray-500 hover:text-primary' }}">
            {{ $label }}
        </a>
    @endforeach
    <form action="{{ route('customer.logout') }}" method="POST">
        @csrf
        <button type="submit" class="text-sm text-red-500 hover:text-red-700">Logout</button>
    </form>
</div>
