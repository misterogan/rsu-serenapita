<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/** Ganti kata sandi sendiri, untuk semua pengguna backoffice. */
class AccountController extends Controller
{
    public function edit()
    {
        return view('admin.password');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current' => 'required|string',
            'password' => 'required|string|min:10|confirmed|different:current',
        ], ['password.different' => 'Kata sandi baru harus berbeda dari kata sandi lama.', 'password.confirmed' => 'Ulangi kata sandi baru dengan sama persis.'],
            ['current' => 'kata sandi lama', 'password' => 'kata sandi baru']);
        $user = $request->user();
        if (! Hash::check($data['current'], $user->password)) {
            throw ValidationException::withMessages(['current' => 'Kata sandi lama tidak sesuai.']);
        }
        $user->forceFill(['password' => $data['password'], 'must_change_password' => false])->save();
        ActivityLog::record('Mengganti kata sandi sendiri');

        return redirect()->route('admin.home')->with('ok', 'Kata sandi berhasil diganti.');
    }
}
