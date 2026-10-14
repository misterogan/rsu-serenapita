<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CareerController extends Controller
{
    protected function guard(): void
    {
        abort_unless(SiteSettings::get('sections')['careers'] ?? true, 404);
    }

    public function index(Request $request)
    {
        $this->guard();
        $q = JobOpening::published()->withCount('applications')
            ->orderByRaw("CASE WHEN status = 'Dibuka' THEN 0 ELSE 1 END")->orderBy('deadline')->orderByDesc('id');
        if ($d = $request->query('department')) {
            $q->where('department', $d);
        }
        if ($t = $request->query('type')) {
            $q->where('employment_type', $t);
        }
        $jobs = $q->get();

        return view('site.careers.index', [
            'jobs' => $jobs,
            'departments' => JobOpening::published()->whereNotNull('department')->distinct()->orderBy('department')->pluck('department'),
            'types' => JobOpening::TYPES,
        ]);
    }

    public function show(string $slug)
    {
        $this->guard();
        $job = JobOpening::published()->where('slug', $slug)->firstOrFail();

        return view('site.careers.show', [
            'job' => $job,
            'education' => JobOpening::EDUCATION,
            'applied' => session('applied'),
        ]);
    }

    public function apply(Request $request, string $slug)
    {
        $this->guard();
        $job = JobOpening::published()->where('slug', $slug)->firstOrFail();
        abort_unless($job->isOpen(), 403, 'Lowongan ini sudah ditutup.');

        // Perangkap robot: kolom tersembunyi ini harus kosong
        if (filled($request->input('website'))) {
            return redirect()->route('careers.show', $job->slug);
        }

        $data = $request->validate([
            'name' => 'required|string|min:3|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'required|string|max:20',
            'birth_date' => 'required|date|before:-15 years',
            'education' => 'required|in:'.implode(',', JobOpening::EDUCATION),
            'major' => 'nullable|string|max:100',
            'institution' => 'nullable|string|max:150',
            'experience_years' => 'required|integer|min:0|max:50',
            'city' => 'required|string|max:80',
            'cover_letter' => 'nullable|string|max:3000',
            'cv' => 'required|file|mimes:pdf|max:2048',
            'doc' => 'nullable|file|mimes:pdf|max:5120',
            'consent' => 'accepted',
        ], [
            'cv.mimes' => 'CV harus berupa file PDF.',
            'doc.mimes' => 'Dokumen pendukung harus berupa file PDF.',
            'birth_date.before' => 'Tanggal lahir tidak valid.',
        ], [
            'name' => 'nama lengkap', 'email' => 'email', 'phone' => 'nomor HP', 'birth_date' => 'tanggal lahir', 'education' => 'pendidikan terakhir',
            'major' => 'jurusan', 'institution' => 'asal sekolah/kampus', 'experience_years' => 'pengalaman kerja', 'city' => 'kota domisili',
            'cover_letter' => 'surat lamaran', 'cv' => 'CV', 'doc' => 'dokumen pendukung', 'consent' => 'persetujuan',
        ]);

        $phone = ChatConversation::normalizePhone($data['phone']);
        if (! preg_match('/^08[1-9]\d{6,11}$/', $phone)) {
            throw ValidationException::withMessages(['phone' => 'Nomor HP tidak valid. Contoh: 081234567890.']);
        }
        if (JobApplication::where('job_opening_id', $job->id)->where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages(['email' => 'Email ini sudah pernah melamar untuk lowongan yang sama.']);
        }

        do {
            $code = 'LMR-'.strtoupper(Str::random(6));
        } while (JobApplication::where('code', $code)->exists());

        // Berkas disimpan di folder privat, tidak bisa dibuka dari internet
        $cv = $request->file('cv')->store('applications/'.$job->id, 'local');
        $doc = $request->hasFile('doc') ? $request->file('doc')->store('applications/'.$job->id, 'local') : null;

        JobApplication::create([
            'code' => $code, 'job_opening_id' => $job->id, 'name' => $data['name'], 'email' => $data['email'], 'phone' => $phone,
            'birth_date' => $data['birth_date'], 'education' => $data['education'], 'major' => $data['major'] ?? null,
            'institution' => $data['institution'] ?? null, 'experience_years' => $data['experience_years'], 'city' => $data['city'],
            'cover_letter' => $data['cover_letter'] ?? null, 'cv_path' => $cv, 'doc_path' => $doc, 'status' => 'Baru',
        ]);

        return redirect()->route('careers.show', $job->slug)->with('applied', $code);
    }
}
