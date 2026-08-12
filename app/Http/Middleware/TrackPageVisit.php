<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackPageVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $store = Store::current();

        PageVisit::create([
            'store_id' => $store->id,
            'path' => $request->path(),
            'customer_id' => Auth::guard('customer')->id(),
            'session_id' => $request->session()->getId(),
        ]);

        return $next($request);
    }
}
