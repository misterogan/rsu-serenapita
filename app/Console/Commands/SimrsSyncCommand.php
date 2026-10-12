<?php

namespace App\Console\Commands;

use App\Services\Simrs\SimrsSync;
use App\Support\SiteSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SimrsSyncCommand extends Command
{
    protected $signature = 'simrs:sync {--force : Jalankan tanpa memeriksa interval}';
    protected $description = 'Sinkronkan poli, dokter, jadwal, dan cuti dari SIMRS';

    public function handle(SimrsSync $sync): int
    {
        $s = SiteSettings::get('simrs');
        if (! $this->option('force') && $s['last_sync']) {
            $next = Carbon::parse($s['last_sync'])->addMinutes(max(1, (int) $s['interval']));
            if (now()->lt($next)) {
                $this->line('Belum waktunya sinkron (berikutnya '.$next->format('H:i').').');

                return self::SUCCESS;
            }
        }
        $r = $sync->run($this->option('force') ? 'manual' : 'berkala');
        $r['ok'] ? $this->info($r['message']) : $this->error($r['message']);

        return $r['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
