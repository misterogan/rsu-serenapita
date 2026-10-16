<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Modules;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function show()
    {
        return Auth::check() ? redirect()->route('admin.home') : view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $cred = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::attempt($cred, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email');
        }
        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'Akun ini dinonaktifkan. Hubungi administrator.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        ActivityLog::record('Masuk ke backoffice');

        return redirect()->intended(route('admin.home'));
    }

    public function logout(Request $request)
    {
        ActivityLog::record('Keluar dari backoffice');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /** Arahkan ke menu pertama yang boleh dibuka peran ini. */
    public function home(Request $request)
    {
        if ($request->user()->isExecutive()) {
            return redirect()->route('admin.management');
        }
        foreach (array_keys(Modules::all()) as $key) {
            if ($request->user()->canModule($key)) {
                return redirect()->to(url('admin/'.$key));
            }
        }
        abort(403, 'Peran Anda belum diberi akses ke menu mana pun.');
    }
}
