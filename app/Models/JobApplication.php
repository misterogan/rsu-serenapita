<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    public const STATUSES = ['Baru', 'Diproses', 'Wawancara', 'Diterima', 'Ditolak'];

    protected $fillable = ['code', 'job_opening_id', 'name', 'email', 'phone', 'birth_date', 'education', 'major', 'institution', 'experience_years', 'city', 'cover_letter', 'cv_path', 'doc_path', 'status', 'note'];
    protected $casts = ['birth_date' => 'date'];

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class, 'job_opening_id');
    }
}
