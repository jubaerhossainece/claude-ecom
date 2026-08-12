<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        $customer = Auth::guard('customer')->user();

        return view('storefront.account.profile', compact('customer'));
    }

    public function update(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'email', Rule::unique('customers', 'email')->ignore($customer->id)],
        ]);

        $customer->update($validated);

        return back()->with('success', 'Profile updated.');
    }
}
