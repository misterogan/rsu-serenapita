<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/** Atur ulang kata sandi akun Dasbor manajemen (hanya lewat server, tidak dari backoffice). */
class ManagementPasswordCommand extends Command
{
    protected $signature = 'dashboard:reset-password {email : Email akun Komisaris, Direktur, atau Akunting}';
    protected $description = 'Membuat kata sandi sementara baru untuk akun Dasbor manajemen';

    public function handle(): int
    {
        $user = User::with('role')->where('email', $this->argument('email'))->first();
        if (! $user || ! $user->isExecutive()) {
            $this->error('Akun Dasbor manajemen dengan email itu tidak ditemukan.');

            return self::FAILURE;
        }
        $pass = Str::password(12, symbols: false);
        $user->forceFill(['password' => $pass, 'must_change_password' => true, 'is_active' => true])->save();
        $this->info('Kata sandi sementara untuk '.$user->email.': '.$pass);
        $this->line('Pengguna wajib menggantinya saat pertama kali masuk.');

        return self::SUCCESS;
    }
}
