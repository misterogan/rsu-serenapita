<?php

namespace App\Support;

/**
 * Saran akun COA baru untuk satu rekening Khanza, berdasarkan kata pada nama rekening.
 * Hanya saran: Akunting tetap memeriksa dan menyimpan pemetaannya sendiri.
 * Aturan dicek berurutan; semua pola dalam satu aturan harus cocok.
 */
class CoaSuggest
{
    protected const RULES = [
        // Kas & bank
        [['kas kecil'], '1101'],
        [['bpjs', 'bank|rekening|giro'], '1103'],
        [['yayasan|pemilik', 'bank|kas'], '1104'],
        [['^(kas )?bank|giro|bank '], '1102'],
        [['^kas\b|kas besar|kas ral|kas ran|kas kasir|kasir'], '1101'],
        // Piutang
        [['piutang', 'karyawan|pegawai'], '1113'],
        [['piutang', 'bpjs|jkn|bpjs kes'], '1111'],
        [['piutang', 'asuransi|perusahaan|jaminan|inhealth|jasa raharja|taspen|askes|kontraktor|corporate'], '1112'],
        [['piutang'], '1110'],
        // Persediaan
        [['persediaan', 'obat|farmasi|apotek|apotik'], '1120'],
        [['persediaan', 'bhp|habis pakai|alkes|alat kesehatan|medis'], '1121'],
        [['persediaan', 'makan|gizi|dapur|bahan makanan'], '1122'],
        [['persediaan|stok'], '1123'],
        [['dibayar dimuka|dibayar di muka|sewa dibayar|asuransi dibayar'], '1130'],
        [['uang muka pembelian|uang muka supplier|uang muka pemesanan'], '1140'],
        // Aset tetap
        [['akumulasi|akm', 'gedung|bangunan'], '1208'],
        [['akumulasi|akm', 'medis|kedokteran|kesehatan|laborat'], '1209'],
        [['akumulasi|akm'], '1210'],
        [['tanah'], '1201'],
        [['renovasi'], '1203'],
        [['gedung|bangunan'], '1202'],
        [['peralatan|alat', 'laborat'], '1205'],
        [['peralatan|alat', 'medis|kedokteran|kesehatan'], '1204'],
        [['kendaraan|ambulan'], '1207'],
        [['peralatan|inventaris|mebel|meubel|komputer|elektronik'], '1206'],
        [['deposito'], '1301'],
        [['investasi'], '1302'],
        [['software|perangkat lunak|tak berwujud'], '1303'],
        // Kewajiban
        [['uang muka pasien|deposit|titipan pasien|pendapatan diterima dimuka'], '2130'],
        [['hutang', 'pph ?21'], '2120'],
        [['hutang', 'pph ?23'], '2121'],
        [['hutang', 'ppn'], '2122'],
        [['hutang', 'gaji|thr|upah'], '2110'],
        [['hutang', 'dokter|jasa medis|jasa dr|jm dokter'], '2111'],
        [['hutang', 'perawat|paramedis|jasa pelayanan|jaspel|bidan'], '2112'],
        [['hutang', 'bank|kredit'], '2201'],
        [['hutang', 'leasing|sewa guna'], '2202'],
        [['hutang', 'yayasan|pemilik|pemegang saham'], '2203'],
        [['hutang', 'obat|farmasi|apotek'], '2101'],
        [['hutang', 'alkes|alat medis|alat kesehatan|bhp|medis'], '2102'],
        [['hutang'], '2103'],
        // Ekuitas
        [['tambahan modal|agio'], '3102'],
        [['modal'], '3101'],
        [['laba ditahan|saldo laba|laba tahun lalu|rugi tahun lalu'], '3201'],
        [['laba tahun berjalan|laba berjalan|ikhtisar laba|laba rugi'], '3202'],
        // Beban (dicek sebelum pendapatan karena nama sering "Beban Jasa ...")
        [['^(beban|biaya|hpp|harga pokok)', 'obat', 'inap|ranap'], '5101'],
        [['^(beban|biaya|hpp|harga pokok)', 'obat|farmasi'], '5102'],
        [['^(beban|biaya|hpp|harga pokok)', 'bhp|habis pakai|alkes'], '5103'],
        [['^(beban|biaya|hpp|harga pokok)', 'laborat'], '5104'],
        [['^(beban|biaya|hpp|harga pokok)', 'radiolog|rontgen'], '5105'],
        [['^(beban|biaya)', 'jasa dokter|jasa medis|jasa dr|jm dokter|dokter'], '5106'],
        [['^(beban|biaya)', 'perawat|paramedis|jasa pelayanan|jaspel|bidan|tenaga medis'], '5107'],
        [['^(beban|biaya)', 'ambulan|igd|ugd'], '5108'],
        [['^(beban|biaya)', 'linen|laundry|cuci'], '5201'],
        [['^(beban|biaya)', 'gizi|makan|konsumsi'], '5202'],
        [['^(beban|biaya)', 'penyusutan|depresiasi', 'gedung|bangunan'], '5403'],
        [['^(beban|biaya)', 'penyusutan|depresiasi', 'medis|kedokteran'], '5404'],
        [['^(beban|biaya)', 'penyusutan|depresiasi'], '5405'],
        [['^(beban|biaya)', 'pemeliharaan|perbaikan|perawatan gedung|servis|service|maintenance'], '5203'],
        [['^(beban|biaya)', 'listrik|pln|air|pdam|oksigen|gas'], '5205'],
        [['^(beban|biaya)', 'telepon|telpon|internet|komunikasi|pulsa|wifi'], '5206'],
        [['^(beban|biaya)', 'software|aplikasi|simrs|lisensi'], '5207'],
        [['^(beban|biaya)', 'atk|alat tulis|cetak|fotocopy|umum|kantor'], '5204'],
        [['^(beban|biaya)', 'thr|bonus|insentif'], '5303'],
        [['^(beban|biaya)', 'bpjs|tunjangan|jaminan sosial'], '5304'],
        [['gaji|upah|honor', 'perawat|bidan'], '5301'],
        [['gaji|upah|honor'], '5302'],
        [['^(beban|biaya)', 'sewa'], '5401'],
        [['^(beban|biaya)', 'asuransi'], '5402'],
        [['^(beban|biaya)', 'pajak|pbb'], '5406'],
        [['^(beban|biaya)', 'izin|ijin|akreditasi'], '5407'],
        [['piutang tak tertagih|penghapusan piutang|tidak terbayar|cadangan kerugian'], '6102'],
        [['selisih kurs'], '6101'],
        // Pendapatan
        [['pendapatan|penerimaan|penjualan|pemasukan', 'bunga|jasa giro'], '4201'],
        [['pendapatan|penerimaan|penjualan|pemasukan', 'obat|farmasi|apotek|resep'], '4107'],
        [['pendapatan|penerimaan|penjualan|pemasukan', 'laborat|lab\b'], '4105'],
        [['pendapatan|penerimaan|penjualan|pemasukan', 'radiolog|rontgen'], '4106'],
        [['pendapatan|penerimaan|penjualan|pemasukan', 'operasi|vk|tindakan medis'], '4104'],
        [['pendapatan|penerimaan|penjualan|pemasukan', 'igd|ugd|gawat'], '4103'],
        [['pendapatan|penerimaan|penjualan|pemasukan', 'registrasi|pendaftaran|administrasi|adm|materai|karcis'], '4109'],
        [['pendapatan|penerimaan|penjualan|pemasukan', 'rawat inap|ranap|kamar|inap'], '4101'],
        [['pendapatan|penerimaan|penjualan|pemasukan', 'rawat jalan|ralan|poli|jalan'], '4102'],
        [['pendapatan|penerimaan|penjualan|pemasukan', 'gizi|fisioterapi|hemodialisa|penunjang|ambulan|usg|ekg|ctg'], '4108'],
        [['pendapatan|penerimaan|penjualan|pemasukan', 'tindakan'], '4104'],
        [['pendapatan|penerimaan|penjualan|pemasukan', 'lain'], '4202'],
        [['lain-lain|lainnya'], '6201'],
    ];

    /** @param  string[]  $valid  kode akun detail yang ada di COA saat ini */
    public static function guess(string $name, array $valid): ?string
    {
        $n = strtolower(trim(preg_replace('/\s+/', ' ', $name)));
        $n = preg_replace('/\butang\b/', 'hutang', $n);
        if ($n === '') {
            return null;
        }
        foreach (self::RULES as [$patterns, $code]) {
            foreach ($patterns as $p) {
                if (! preg_match('/'.$p.'/u', $n)) {
                    continue 2;
                }
            }
            if (in_array($code, $valid, true)) {
                return $code;
            }
        }

        return null;
    }
}
