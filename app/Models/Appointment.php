<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    public const STATUSES = ['Menunggu', 'Dikonfirmasi', 'Hadir', 'Selesai', 'Batal'];

    protected $fillable = ['code', 'doctor_id', 'date', 'time', 'name', 'nik', 'phone', 'birth_date', 'payment', 'bpjs_number', 'referral_number', 'medical_record', 'note', 'status', 'simrs_booking_code', 'queue_number', 'simrs_sent_at', 'simrs_error'];
    protected $casts = ['date' => 'date', 'birth_date' => 'date', 'simrs_sent_at' => 'datetime'];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
