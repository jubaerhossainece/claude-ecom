<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $customer = Auth::guard('customer')->user();

        $eligibleOrderId = Order::where('customer_id', $customer->id)
            ->where('status', 'delivered')
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->value('id');

        abort_unless($eligibleOrderId, 403, 'You can only review products you have purchased and received.');

        $existing = Review::where('customer_id', $customer->id)->where('product_id', $product->id)->exists();
        abort_if($existing, 422, 'You have already reviewed this product.');

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:2000',
            'photos.*' => 'nullable|image|max:5120',
        ]);

        $review = Review::create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'order_id' => $eligibleOrderId,
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'is_approved' => false,
        ]);

        foreach ($request->file('photos', []) as $photo) {
            $review->addMedia($photo)->toMediaCollection('photos');
        }

        return back()->with('success', 'Thanks! Your review has been submitted and is awaiting approval.');
    }

    public function update(Request $request, Review $review)
    {
        $customer = Auth::guard('customer')->user();
        abort_if($review->customer_id !== $customer->id, 403);

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:2000',
            'photos.*' => 'nullable|image|max:5120',
        ]);

        $review->update([
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'is_approved' => false,
        ]);

        foreach ($request->file('photos', []) as $photo) {
            $review->addMedia($photo)->toMediaCollection('photos');
        }

        return back()->with('success', 'Review updated and is awaiting re-approval.');
    }

    public function destroy(Review $review)
    {
        $customer = Auth::guard('customer')->user();
        abort_if($review->customer_id !== $customer->id, 403);

        $review->delete();

        return back()->with('success', 'Review deleted.');
    }
}
