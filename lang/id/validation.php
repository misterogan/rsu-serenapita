<?php

// Pesan validasi bahasa Indonesia untuk aturan yang dipakai aplikasi ini.
return [
    'accepted' => ':attribute wajib disetujui.',
    'after' => ':attribute harus setelah :date.',
    'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'array' => ':attribute harus berupa daftar.',
    'before' => ':attribute harus sebelum :date.',
    'before_or_equal' => ':attribute paling lambat :date.',
    'boolean' => ':attribute harus bernilai ya atau tidak.',
    'date' => ':attribute bukan tanggal yang valid.',
    'digits' => ':attribute harus :digits digit angka.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'file' => ':attribute harus berupa berkas.',
    'image' => ':attribute harus berupa gambar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'required_if' => ':attribute wajib diisi.',
    'after_or_equal' => ':attribute harus sama dengan atau setelah :date.',
    'mimes' => ':attribute harus berupa berkas dengan format: :values.',
    'url' => ':attribute harus berupa tautan yang valid.',
    'uploaded' => ':attribute gagal diunggah. Periksa ukuran berkas.',
    'max' => [
        'array' => ':attribute maksimal :max item.',
        'file' => ':attribute maksimal :max kilobita.',
        'numeric' => ':attribute maksimal :max.',
        'string' => ':attribute maksimal :max karakter.',
    ],
    'min' => [
        'array' => ':attribute minimal :min item.',
        'file' => ':attribute minimal :min kilobita.',
        'numeric' => ':attribute minimal :min.',
        'string' => ':attribute minimal :min karakter.',
    ],
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':attribute wajib diisi.',
    'size' => [
        'array' => ':attribute harus berisi :size item.',
        'string' => ':attribute harus :size karakter.',
    ],
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah digunakan.',

    'custom' => [
        'image' => ['required_if' => 'Foto wajib diunggah untuk jenis Foto.'],
        'video_url' => ['required_if' => 'Tautan video wajib diisi untuk jenis Video.'],
    ],

    'attributes' => [
        'email' => 'email',
        'password' => 'kata sandi',
        'name' => 'nama',
        'phone' => 'nomor HP',
        'message' => 'pesan',
        'street' => 'jalan',
        'city' => 'kabupaten/kota',
        'maps' => 'tautan Google Maps',
        'logo' => 'logo',
        'logo_dark' => 'logo gelap',
        'consent' => 'persetujuan',
    ],
];
