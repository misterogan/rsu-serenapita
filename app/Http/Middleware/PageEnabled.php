<?php

namespace App\Http\Middleware;

use App\Support\Links;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Menolak halaman situs yang menunya disembunyikan di backoffice. */
class PageEnabled
{
    public function handle(Request $request, Closure $next, string $key): Response
    {
        abort_unless(Links::enabled($key), 404);

        return $next($request);
    }
}
