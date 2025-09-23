<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // pastikan user login & flag is_admin = true
        if (! $request->user() || ! $request->user()->is_admin) {
            // boleh abort 403, atau redirect dengan pesan
            abort(403, 'Hanya admin yang boleh mengakses halaman ini.');
            // atau:
            // return redirect()->route('dashboard')->with('error', 'Akses admin diperlukan.');
        }

        return $next($request);
    }
}
