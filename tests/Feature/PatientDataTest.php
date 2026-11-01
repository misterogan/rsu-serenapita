<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Data pasien dibaca dari database Khanza tiruan (SQLite) dengan struktur tabel bawaan Khanza. */
class PatientDataTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.khanza' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'host' => 'test']]);
        DB::purge('khanza');
        $this->khanza();
        $this->user = User::where('email', 'datapasien@rsuserenapita.id')->first();
        $this->user->update(['must_change_password' => false]);
    }

    protected function khanza(): void
    {
        $s = Schema::connection('khanza');
        $tables = [
            'pasien' => ['no_rkm_medis', 'nm_pasien', 'no_ktp', 'jk', 'tmp_lahir', 'tgl_lahir', 'nm_ibu', 'alamat', 'gol_darah', 'pekerjaan', 'stts_nikah', 'agama', 'tgl_daftar', 'no_tlp', 'pnd', 'keluarga', 'namakeluarga', 'kd_pj', 'no_peserta', 'kd_kel', 'kd_kec', 'kd_kab'],
            'penjab' => ['kd_pj', 'png_jawab'], 'poliklinik' => ['kd_poli', 'nm_poli'], 'dokter' => ['kd_dokter', 'nm_dokter'],
            'kelurahan' => ['kd_kel', 'nm_kel'], 'kecamatan' => ['kd_kec', 'nm_kec'], 'kabupaten' => ['kd_kab', 'nm_kab'],
            'reg_periksa' => ['no_reg', 'no_rawat', 'tgl_registrasi', 'jam_reg', 'kd_dokter', 'no_rkm_medis', 'kd_poli', 'stts', 'status_lanjut', 'kd_pj', 'status_bayar'],
            'diagnosa_pasien' => ['no_rawat', 'kd_penyakit', 'status', 'prioritas'], 'penyakit' => ['kd_penyakit', 'nm_penyakit'],
            'pemeriksaan_ralan' => ['no_rawat', 'tgl_perawatan', 'jam_rawat', 'suhu_tubuh', 'tensi', 'nadi', 'respirasi', 'tinggi', 'berat', 'spo2', 'gcs', 'kesadaran', 'keluhan', 'pemeriksaan', 'alergi', 'rtl', 'penilaian', 'instruksi', 'nip'],
            'pegawai' => ['nik', 'nama'],
            'detail_pemberian_obat' => ['tgl_perawatan', 'jam', 'no_rawat', 'kode_brng', 'jml'], 'databarang' => ['kode_brng', 'nama_brng', 'kode_sat'],
            'detail_periksa_lab' => ['no_rawat', 'kd_jenis_prw', 'tgl_periksa', 'jam', 'id_template', 'nilai', 'nilai_rujukan', 'keterangan'],
            'template_laboratorium' => ['id_template', 'Pemeriksaan', 'satuan'], 'jns_perawatan_lab' => ['kd_jenis_prw', 'nm_perawatan'],
            'kamar_inap' => ['no_rawat', 'kd_kamar', 'tgl_masuk', 'jam_masuk', 'tgl_keluar', 'jam_keluar', 'lama', 'diagnosa_awal', 'diagnosa_akhir', 'stts_pulang'],
            'kamar' => ['kd_kamar', 'kd_bangsal'], 'bangsal' => ['kd_bangsal', 'nm_bangsal'],
        ];
        foreach ($tables as $name => $cols) {
            $s->create($name, function (Blueprint $t) use ($cols) {
                foreach ($cols as $c) {
                    $t->string($c)->nullable();
                }
            });
        }
        $db = DB::connection('khanza');
        $db->table('penjab')->insert([['kd_pj' => 'BPJ', 'png_jawab' => 'BPJS KESEHATAN'], ['kd_pj' => 'UMU', 'png_jawab' => 'UMUM']]);
        $db->table('poliklinik')->insert(['kd_poli' => 'INT', 'nm_poli' => 'Poli Penyakit Dalam']);
        $db->table('dokter')->insert(['kd_dokter' => 'D1', 'nm_dokter' => 'dr. Maria Situmorang, Sp.PD']);
        $db->table('kelurahan')->insert(['kd_kel' => '1', 'nm_kel' => 'Batang Beruh']);
        $db->table('pasien')->insert([
            ['no_rkm_medis' => '000123', 'nm_pasien' => 'BUDI SANTOSO SIREGAR', 'no_ktp' => '1211010101800001', 'jk' => 'L', 'tmp_lahir' => 'Sidikalang', 'tgl_lahir' => '1980-01-01',
                'nm_ibu' => 'RUSMINA', 'alamat' => 'Jl. Sisingamangaraja 10', 'gol_darah' => 'O', 'pekerjaan' => 'PNS', 'stts_nikah' => 'MENIKAH', 'agama' => 'KRISTEN', 'tgl_daftar' => '2019-05-02',
                'no_tlp' => '081234567890', 'pnd' => 'S1', 'keluarga' => 'ISTRI', 'namakeluarga' => 'SRI', 'kd_pj' => 'BPJ', 'no_peserta' => '0001234567891', 'kd_kel' => '1', 'kd_kec' => null, 'kd_kab' => null],
            ['no_rkm_medis' => '000124', 'nm_pasien' => 'ANI LUBIS', 'no_ktp' => null, 'jk' => 'P', 'tmp_lahir' => null, 'tgl_lahir' => '2020-06-15', 'nm_ibu' => null, 'alamat' => 'Dairi',
                'gol_darah' => null, 'pekerjaan' => null, 'stts_nikah' => null, 'agama' => null, 'tgl_daftar' => today()->toDateString(), 'no_tlp' => null, 'pnd' => null, 'keluarga' => null,
                'namakeluarga' => null, 'kd_pj' => 'UMU', 'no_peserta' => null, 'kd_kel' => null, 'kd_kec' => null, 'kd_kab' => null],
        ]);
        $db->table('reg_periksa')->insert([
            ['no_reg' => '001', 'no_rawat' => '2026/10/01/000001', 'tgl_registrasi' => '2026-10-01', 'jam_reg' => '08:10:00', 'kd_dokter' => 'D1', 'no_rkm_medis' => '000123', 'kd_poli' => 'INT', 'stts' => 'Sudah', 'status_lanjut' => 'Ralan', 'kd_pj' => 'BPJ', 'status_bayar' => 'Sudah Bayar'],
            ['no_reg' => '002', 'no_rawat' => '2026/10/20/000002', 'tgl_registrasi' => '2026-10-20', 'jam_reg' => '09:00:00', 'kd_dokter' => 'D1', 'no_rkm_medis' => '000123', 'kd_poli' => 'INT', 'stts' => 'Sudah', 'status_lanjut' => 'Ranap', 'kd_pj' => 'BPJ', 'status_bayar' => 'Belum Bayar'],
            ['no_reg' => '003', 'no_rawat' => today()->format('Y/m/d').'/000003', 'tgl_registrasi' => today()->toDateString(), 'jam_reg' => '10:00:00', 'kd_dokter' => 'D1', 'no_rkm_medis' => '000124', 'kd_poli' => 'INT', 'stts' => 'Belum', 'status_lanjut' => 'Ralan', 'kd_pj' => 'UMU', 'status_bayar' => 'Belum Bayar'],
        ]);
        $db->table('penyakit')->insert([['kd_penyakit' => 'E11', 'nm_penyakit' => 'Diabetes mellitus tipe 2'], ['kd_penyakit' => 'I10', 'nm_penyakit' => 'Hipertensi esensial']]);
        $db->table('diagnosa_pasien')->insert([
            ['no_rawat' => '2026/10/01/000001', 'kd_penyakit' => 'E11', 'status' => 'Ralan', 'prioritas' => '1'],
            ['no_rawat' => '2026/10/20/000002', 'kd_penyakit' => 'E11', 'status' => 'Ranap', 'prioritas' => '1'],
            ['no_rawat' => '2026/10/20/000002', 'kd_penyakit' => 'I10', 'status' => 'Ranap', 'prioritas' => '2'],
        ]);
        $db->table('pegawai')->insert(['nik' => 'P01', 'nama' => 'Ns. Rina']);
        $db->table('pemeriksaan_ralan')->insert(['no_rawat' => '2026/10/01/000001', 'tgl_perawatan' => '2026-10-01', 'jam_rawat' => '08:30:00', 'suhu_tubuh' => '36.8', 'tensi' => '150/90',
            'nadi' => '88', 'respirasi' => '20', 'tinggi' => '168', 'berat' => '80', 'spo2' => '98', 'gcs' => '15', 'kesadaran' => 'Compos Mentis', 'keluhan' => 'Sering haus dan lemas',
            'pemeriksaan' => 'KU baik', 'alergi' => 'Amoxicillin', 'rtl' => 'Metformin 2x500', 'penilaian' => 'DM tipe 2', 'instruksi' => 'Kontrol 2 minggu', 'nip' => 'P01']);
        $db->table('databarang')->insert(['kode_brng' => 'B1', 'nama_brng' => 'METFORMIN 500 MG', 'kode_sat' => 'TAB']);
        $db->table('detail_pemberian_obat')->insert(['tgl_perawatan' => '2026-10-01', 'jam' => '09:00:00', 'no_rawat' => '2026/10/01/000001', 'kode_brng' => 'B1', 'jml' => '30']);
        $db->table('jns_perawatan_lab')->insert(['kd_jenis_prw' => 'L1', 'nm_perawatan' => 'Gula darah']);
        $db->table('template_laboratorium')->insert(['id_template' => '1', 'Pemeriksaan' => 'GDS', 'satuan' => 'mg/dL']);
        $db->table('detail_periksa_lab')->insert(['no_rawat' => '2026/10/01/000001', 'kd_jenis_prw' => 'L1', 'tgl_periksa' => '2026-10-01', 'jam' => '08:45:00', 'id_template' => '1', 'nilai' => '245', 'nilai_rujukan' => '70-140', 'keterangan' => 'H']);
        $db->table('bangsal')->insert(['kd_bangsal' => 'B1', 'nm_bangsal' => 'Ruang Melati']);
        $db->table('kamar')->insert(['kd_kamar' => 'K1', 'kd_bangsal' => 'B1']);
        $db->table('kamar_inap')->insert(['no_rawat' => '2026/10/20/000002', 'kd_kamar' => 'K1', 'tgl_masuk' => '2026-10-20', 'jam_masuk' => '10:00:00', 'tgl_keluar' => '2026-10-23',
            'jam_keluar' => '11:00:00', 'lama' => '3', 'diagnosa_awal' => 'Hiperglikemia', 'diagnosa_akhir' => 'DM tipe 2', 'stts_pulang' => 'Sembuh']);
    }

    public function test_only_the_patient_data_role_opens_the_module(): void
    {
        $this->actingAs($this->user)->get(route('admin.home'))->assertRedirect(url('admin/patients'));
        $this->actingAs($this->user)->get(route('admin.stock'))->assertForbidden();

        $farmasi = User::create(['name' => 'Farmasi', 'email' => 'f@example.test', 'password' => 'rahasia123', 'role_id' => Role::where('name', 'Farmasi')->value('id'), 'is_active' => true]);
        $this->actingAs($farmasi)->get(route('admin.patients'))->assertForbidden();
    }

    public function test_default_page_lists_todays_registrations(): void
    {
        $this->actingAs($this->user)->get(route('admin.patients'))->assertOk()
            ->assertSee('ANI LUBIS')->assertDontSee('BUDI SANTOSO SIREGAR')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_search_by_name_rm_nik_bpjs_and_phone(): void
    {
        foreach (['budi siregar', '000123', '1211010101800001', '0001234567891', '67890'] as $term) {
            $this->actingAs($this->user)->get(route('admin.patients', ['q' => $term]))->assertOk()
                ->assertSee('BUDI SANTOSO SIREGAR')->assertDontSee('ANI LUBIS')->assertSee('2×');
        }
        $this->assertTrue(ActivityLog::where('action', 'like', 'Mencari data pasien%')->exists());
    }

    public function test_patient_profile_shows_identity_allergy_and_history(): void
    {
        $this->actingAs($this->user)->get(route('admin.patients.show', '000123'))->assertOk()
            ->assertSee('BUDI SANTOSO SIREGAR')->assertSee('1211010101800001')->assertSee('Kel. Batang Beruh')->assertSee('BPJS KESEHATAN')
            ->assertSee('Riwayat alergi')->assertSee('Amoxicillin')
            ->assertSee('150/90')->assertSee('E11 Diabetes mellitus tipe 2')->assertSee('Ruang Melati')->assertSee('+1 diagnosa lain');
        $this->assertTrue(ActivityLog::where('action', 'Membuka data pasien RM 000123')->exists());
        $this->actingAs($this->user)->get(route('admin.patients.show', '999999'))->assertNotFound();
    }

    public function test_visit_detail_shows_clinical_records_and_skips_missing_tables(): void
    {
        $this->actingAs($this->user)->get(route('admin.patients.visit', ['000123', 'rawat' => '2026/10/01/000001']))->assertOk()
            ->assertSee('Sering haus dan lemas')->assertSee('Ns. Rina')->assertSee('METFORMIN 500 MG')->assertSee('GDS')->assertSee('245')
            ->assertSee('tidak bisa dibaca dari database Khanza');   // tabel radiologi, tindakan, dll. sengaja tidak dibuat

        $this->actingAs($this->user)->get(route('admin.patients.visit', ['000123', 'rawat' => '2026/10/20/000002']))->assertOk()
            ->assertSee('Ruang Melati')->assertSee('Sembuh')->assertSee('I10');

        // Kunjungan milik pasien lain tidak bisa dibuka lewat RM pasien ini
        $this->actingAs($this->user)->get(route('admin.patients.visit', ['000123', 'rawat' => today()->format('Y/m/d').'/000003']))->assertNotFound();
    }

    public function test_shows_notice_when_khanza_is_not_configured(): void
    {
        config(['database.connections.khanza.host' => '', 'database.connections.khanza.database' => '']);
        $this->actingAs($this->user)->get(route('admin.patients'))->assertOk()->assertSee('belum dihubungkan');
    }
}
