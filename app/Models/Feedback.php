<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    public const TYPES = ['Pengaduan', 'Saran', 'Pujian', 'Pertanyaan'];
    public const CATEGORIES = ['Pelayanan medis', 'Administrasi dan BPJS', 'Sikap petugas', 'Fasilitas dan kebersihan', 'Waktu tunggu', 'Biaya', 'Website dan layanan online', 'Lainnya'];
    public const STATUSES = ['Baru', 'Diproses', 'Selesai', 'Ditolak'];

    protected $table = 'feedback';
    protected $fillable = ['code', 'type', 'category', 'unit', 'name', 'phone', 'email', 'incident_date', 'message', 'attachment', 'status', 'response', 'responded_at', 'note'];
    protected $casts = ['incident_date' => 'date', 'responded_at' => 'datetime'];
}
