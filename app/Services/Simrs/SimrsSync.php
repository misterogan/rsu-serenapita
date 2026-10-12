<?php

namespace App\Services\Simrs;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorLeave;
use App\Models\Poli;
use App\Models\SyncLog;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Menarik poli, dokter, jadwal, dan cuti dari SIMRS ke database situs. */
class SimrsSync
{
    public function __construct(protected SimrsClient $simrs) {}

    public function run(string $source = 'berkala'): array
    {
        try {
            $polis = $this->simrs->polis();
            $doctors = $this->simrs->doctors();
            $leaves = $this->simrs->leaves();

            DB::transaction(function () use ($polis, $doctors, $leaves) {
                $sort = Poli::max('sort') ?? 0;
                foreach ($polis as $p) {
                    $poli = Poli::firstOrNew(['simrs_code' => $p['code']]);
                    if (! $poli->exists) {
                        $poli->sort = ++$sort;
                    }
                    $poli->name = $p['name'];
                    $poli->save();
                }
                $poliIds = Poli::pluck('id', 'simrs_code');

                $palette = ['#1F6FB2', '#2E8B6A', '#C8332E', '#7A4FA8', '#B05C8E', '#1C3B70', '#35A3D8', '#3D7F8F', '#D08A2E', '#4E9A5A', '#5B6878', '#8A6D3B'];
                $seen = [];
                foreach ($doctors as $i => $d) {
                    $doc = Doctor::firstOrNew(['simrs_code' => $d['code']]);
                    if (! $doc->exists) {
                        $doc->color = $palette[$i % count($palette)];
                    }
                    $doc->fill(['name' => $d['name'], 'poli_id' => $poliIds[$d['poli_code']] ?? null, 'status' => $d['status'], 'schedule' => $d['schedule']])->save();
                    $seen[] = $d['code'];
                }
                Doctor::whereNotIn('simrs_code', $seen)->update(['status' => 'Nonaktif']);

                $docIds = Doctor::pluck('id', 'simrs_code');
                $leaveCodes = [];
                foreach ($leaves as $l) {
                    if (! isset($docIds[$l['doctor_code']])) {
                        continue;
                    }
                    DoctorLeave::updateOrCreate(['simrs_code' => $l['code']], [
                        'doctor_id' => $docIds[$l['doctor_code']], 'date_from' => $l['from'], 'date_to' => $l['to'], 'reason' => $l['reason'],
                    ]);
                    $leaveCodes[] = $l['code'];
                }
                DoctorLeave::whereNotIn('simrs_code', $leaveCodes)->delete();
            });

            $this->refreshAppointmentStatuses();
            $schedules = collect($doctors)->sum(fn ($d) => count($d['schedule']));
            $msg = sprintf('Sinkron %s: %d poli, %d dokter, %d jadwal, %d cuti', $source, count($polis), count($doctors), $schedules, count($leaves));
            $this->mark(true, $msg);

            return ['ok' => true, 'message' => $msg];
        } catch (Throwable $e) {
            report($e);
            $this->mark(false, 'Sinkron '.$source.' gagal: '.mb_substr($e->getMessage(), 0, 180));

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    protected function refreshAppointmentStatuses(): void
    {
        Appointment::query()->whereNotNull('simrs_booking_code')->whereDate('date', '>=', today()->subDay())
            ->whereNotIn('status', ['Selesai', 'Batal'])->limit(200)->get()
            ->each(function (Appointment $a) {
                $status = $this->simrs->bookingStatus($a->simrs_booking_code);
                if ($status && in_array($status, Appointment::STATUSES, true) && $status !== $a->status) {
                    $a->update(['status' => $status]);
                }
            });
    }

    protected function mark(bool $ok, string $message): void
    {
        SyncLog::create(['ok' => $ok, 'message' => $message]);
        $s = SiteSettings::get('simrs');
        $s['status'] = $ok ? 'Terhubung' : 'Gagal';
        if ($ok) {
            $s['last_sync'] = now()->toIso8601String();
        }
        SiteSettings::put('simrs', $s);
    }
}
