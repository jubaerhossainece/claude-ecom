@extends('storefront.layouts.app')
@section('title', 'Login')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white rounded-2xl shadow-sm border p-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-2 text-center">Login to Your Account</h1>
        <p class="text-gray-500 text-sm text-center mb-6">Enter your phone number to receive an OTP</p>

        @if(config('app.debug') && session('dev_otp'))
            <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-3 mb-4 text-sm text-yellow-800">
                🔑 Dev OTP: <strong>{{ session('dev_otp') }}</strong>
            </div>
        @endif

        @if(! session('otp_sent'))
            {{-- Step 1: Enter Phone --}}
            <form action="{{ route('customer.send-otp') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" required
                           placeholder="01XXXXXXXXX"
                           class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary @error('phone') border-red-400 @enderror">
                    @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="w-full btn-primary py-3">Send OTP →</button>
            </form>
        @else
            {{-- Step 2: Enter OTP --}}
            <form action="{{ route('customer.verify-otp') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="phone" value="{{ session('phone') }}">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800">
                    OTP sent to <strong>{{ session('phone') }}</strong>. Valid for 5 minutes.
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Enter OTP</label>
                    <input type="text" name="otp" required maxlength="6" pattern="[0-9]{6}"
                           placeholder="6-digit OTP"
                           class="w-full border border-gray-300 rounded-xl px-4 py-3 text-center text-2xl font-bold tracking-widest focus:outline-none focus:border-primary @error('otp') border-red-400 @enderror">
                    @error('otp') <p class="text-red-500 text-xs mt-1 text-center">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="w-full btn-primary py-3">Verify OTP & Login</button>
                <a href="{{ route('customer.login') }}" class="block text-center text-sm text-gray-500 hover:text-primary">Use a different number</a>
            </form>
        @endif

        <div class="mt-6 text-center text-sm text-gray-500">
            New customer? <a href="{{ route('customer.register') }}" class="text-primary font-medium hover:underline">Register here</a>
        </div>
    </div>
</div>
@endsection
