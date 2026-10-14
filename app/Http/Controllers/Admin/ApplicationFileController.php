<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Unduh berkas pelamar dari folder privat (hanya petugas dengan akses Pelamar). */
class ApplicationFileController extends Controller
{
    public function __invoke(JobApplication $application, string $type)
    {
        $path = $type === 'cv' ? $application->cv_path : $application->doc_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        $name = ($type === 'cv' ? 'CV' : 'Dokumen').' - '.Str::slug($application->name).' - '.$application->code.'.pdf';

        return Storage::disk('local')->response($path, $name, ['Content-Type' => 'application/pdf']);
    }
}
