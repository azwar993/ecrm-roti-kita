<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckoutAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            $request->session()->put('rotikita.auth_context', 'checkout');
            $request->session()->put('url.intended', route('customer.checkout'));

            return redirect()->route('login');
        }

        return $next($request);
    }
}
