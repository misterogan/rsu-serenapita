<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->guest(route('admin.login'));
        }
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('admin.login')->withErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi administrator.']);
        }

        if ($user->must_change_password && ! $request->routeIs('admin.password', 'admin.password.save', 'admin.logout')) {
            return redirect()->route('admin.password')->with('err', 'Silakan ganti kata sandi Anda sebelum melanjutkan.');
        }

        return $next($request);
    }
}
