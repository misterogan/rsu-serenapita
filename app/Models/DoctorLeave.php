<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorLeave extends Model
{
    protected $fillable = ['simrs_code', 'doctor_id', 'date_from', 'date_to', 'reason'];
    protected $casts = ['date_from' => 'date', 'date_to' => 'date'];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
