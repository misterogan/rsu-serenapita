<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Support\Facades\Storage;

/** Unduh lampiran pengaduan dari folder privat. */
class FeedbackFileController extends Controller
{
    public function __invoke(Feedback $feedback)
    {
        abort_unless($feedback->attachment && Storage::disk('local')->exists($feedback->attachment), 404);

        return Storage::disk('local')->response($feedback->attachment, 'Lampiran-'.$feedback->code.'.'.pathinfo($feedback->attachment, PATHINFO_EXTENSION));
    }
}
