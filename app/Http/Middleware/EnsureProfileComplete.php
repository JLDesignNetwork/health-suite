<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureProfileComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()
            && ! $request->user()->profile()->exists()
            && ! $request->routeIs('onboarding')
            && ! $request->hasHeader('X-Livewire')
        ) {
            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}
