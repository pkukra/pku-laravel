<?php
function naikKelasSanitize($naik_kelas)
{
    if (!is_string($naik_kelas)) return null;

    $naik_kelas = trim($naik_kelas);
    if ($naik_kelas === '' || $naik_kelas === '-- Pilih --') return null;

    if (preg_match('/^(\d+)\.\s*(.+)$/', $naik_kelas, $matches)) {
        $angka = (int) $matches[1];

        $mapping = [
            1 => '1',
            2 => 'vip',
            3 => '1',
            4 => '2',
        ];

        return $mapping[$angka] ?? null;
    }

    return null;
}
?>

<table>
    <tr>
        <td>Kamar</td>
        <td>
            No Transakasi
        </td>
        <td>
            Nama Pasien
        </td>
        <td>Nomer RM</td>
        <td>DPJP</td>
        <td>Tanggal Masuk</td>
        <td>Total Hari Rawat</td>
        <!-- <td>
            Pemeriksaan Penunjang
        </td>
        <td>
            Hasil Penunjang Abnormal
        </td> -->
        <td>
            No SEP
        </td>
        <td>
            Hak Kelas
        </td>
        <td>Naik Kelas</td>
        <td>
            Kemungkinan Kode Diagnosa
        </td>
        <td>
            Kemungkinan Kode Prosedur
        </td>
        <td>
            Perkiraan Klaim (Rp)
        </td>
        <td>
            Billing Sementara (Rp)
        </td>
        <td>
            selisih (Rp)
        </td>
        <!-- <td>
            Konfirmasi Koder
        </td>
        <td>
            Rekomendasi Dokter Bangsal
        </td>
        <td>
            Follow Up SPV Bangsal
        </td>
        <td>
            Follow Up MPP
        </td> -->
    </tr>
    @foreach ($data as $val)

    <tr>
        <td>{{ $val->FMKNAMA_KAMAR ?? '' }}</td>
        <td>{{ $val->FTNO_TRANSAKSI ?? '' }}</td>
        <td>{{ $val->NAMAPASIEN ?? '' }}</td>
        <td>{{ $val->FTKD_PASIEN ?? '' }}</td>
        <td>{{ $val->DPJP ?? '' }}</td>
        <td>{{ $val->FTTGL_TRANSAKSI ? \Carbon\Carbon::parse($val->FTTGL_TRANSAKSI)->format('d-m-Y') : '' }}</td>
        <?php
        $tglMasuk = \Carbon\Carbon::parse($val->FTTGL_TRANSAKSI)->startOfDay();
        $hariIni = now()->startOfDay();
        $selisihHari = $tglMasuk->diffInDays($hariIni) + ($tglMasuk <= $hariIni ? 1 : 0);
        ?>

        <?php
        $perkiraanKlaim = null;
        $naikKelas = null;
        if (isset($val->RAWAT_NAIK)) {
            $naikKelas = naikKelasSanitize($val->RAWAT_NAIK);
            if (!$naikKelas) {
                $perkiraanKlaim = $val->FTTARIPINACBG;
            } elseif ($naikKelas == 1) {
                $perkiraanKlaim = $val->FTTARIPINACBG1;
            } elseif ($naikKelas == 2) {
                $perkiraanKlaim = $val->FTTARIPINACBG2;
            } elseif ($naikKelas == "vip") {
                $perkiraanKlaim = $val->FTTARIPINACBG1;
            } else {
                $perkiraanKlaim = null;
            }
        }
        ?>

        <?php
        $selisih = null;
        if (!empty($val->NO_SEP)) {
            $selisih = $perkiraanKlaim - ($val->TOTAL_BILL ?? 0);
        }
        ?>

        <td>{{ $selisihHari }}</td>
        <!-- <td>{{ $val->PEMERIKSAAN_PENUNJANG ?? '' }}</td>
        <td>{{ $val->HASIL_PENUNJANG_ABNORMAL ?? '' }}</td> -->
        <td>{{ $val->NO_SEP ?? '' }}</td>
        <td>{{ $val->KELAS_RAWAT ?? '' }}</td>
        <td>{{ $naikKelas }}</td>
        <td>
            {{ get_diagnosa_by_transaksi($val->FTNO_TRANSAKSI)->pluck('MRPKD_PENYAKIT')->implode(', ') }}
        </td>
        <td>
            {{ get_procedure_by_transaksi($val->FTNO_TRANSAKSI)->pluck('MRTKD_TINDAKAN')->implode(', ') }}
        </td>
        <td>{{ $perkiraanKlaim }}</td>
        <td>{{ $val->TOTAL_BILL ?? '' }}</td>
        <td>{{ $selisih }}</td>
        <!-- <td>{{ isset($val->KONFIRMASI_KODER)?strip_tags($val->KONFIRMASI_KODER):"" }}</td>
        <td>{{ isset($val->REKOMENDASI_DOKTER_BANGSAL)?strip_tags($val->REKOMENDASI_DOKTER_BANGSAL):"" }}</td>
        <td>{{ isset($val->FOLLOW_UP_SPV_BANGSAL)?strip_tags($val->FOLLOW_UP_SPV_BANGSAL):"" }}</td>
        <td>{{ isset($val->FOLLOW_UP_MPP)?strip_tags($val->FOLLOW_UP_MPP):"" }}</td> -->
    </tr>
    @endforeach
</table>