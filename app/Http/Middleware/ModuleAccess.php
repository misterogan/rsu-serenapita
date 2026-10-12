<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Memastikan peran pengguna boleh membuka menu backoffice tertentu. */
class ModuleAccess
{
    public function handle(Request $request, Closure $next, ?string $module = null): Response
    {
        $module ??= $request->route('resource');
        abort_unless($module && $request->user()?->canModule($module), 403, 'Peran Anda tidak memiliki akses ke menu ini.');

        return $next($request);
    }
}
