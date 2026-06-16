<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\OtpVerification;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerAuthController extends Controller
{
    public function showLogin()
    {
        return view('storefront.auth.login');
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string|max:20']);

        $phone = $request->phone;

        // Invalidate old OTPs
        OtpVerification::where('phone', $phone)
            ->where('purpose', 'login')
            ->whereNull('verified_at')
            ->delete();

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpVerification::create([
            'phone' => $phone,
            'otp' => $otp,
            'purpose' => 'login',
            'expires_at' => now()->addMinutes(5),
        ]);

        // In production: send via SMS gateway (e.g., Twilio, BulkSMS BD)
        // For dev: log it
        \Log::info("OTP for {$phone}: {$otp}");

        // Flash OTP for dev/demo (remove in production)
        if (config('app.debug')) {
            session()->flash('dev_otp', $otp);
        }

        return back()->with('otp_sent', true)->with('phone', $phone);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
            'otp' => 'required|string|max:10',
        ]);

        $verification = OtpVerification::where('phone', $request->phone)
            ->where('purpose', 'login')
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $verification) {
            return back()->withErrors(['otp' => 'No OTP found. Please request a new one.']);
        }

        $verification->increment('attempts');

        if (! $verification->isValid($request->otp)) {
            if ($verification->attempts >= 5) {
                return back()->withErrors(['otp' => 'Too many attempts. Request a new OTP.']);
            }
            return back()->withErrors(['otp' => 'Invalid or expired OTP.']);
        }

        $verification->update(['verified_at' => now()]);

        $store = Store::current();
        $customer = Customer::firstOrCreate(
            ['phone' => $request->phone, 'store_id' => $store->id],
            ['name' => 'Customer', 'phone_verified_at' => now()]
        );

        if (! $customer->phone_verified_at) {
            $customer->update(['phone_verified_at' => now()]);
        }

        Auth::guard('customer')->login($customer, true);

        return redirect()->intended(route('account.index'))->with('success', 'Welcome back!');
    }

    public function showRegister()
    {
        return view('storefront.auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone',
            'email' => 'nullable|email|unique:customers,email',
        ]);

        $store = Store::current();

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        OtpVerification::create([
            'phone' => $request->phone,
            'otp' => $otp,
            'purpose' => 'register',
            'expires_at' => now()->addMinutes(5),
        ]);

        \Log::info("Registration OTP for {$request->phone}: {$otp}");

        session(['pending_registration' => $request->only('name', 'phone', 'email')]);

        if (config('app.debug')) {
            session()->flash('dev_otp', $otp);
        }

        return redirect()->route('customer.login')
            ->with('otp_sent', true)
            ->with('phone', $request->phone)
            ->with('registration_pending', true);
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
