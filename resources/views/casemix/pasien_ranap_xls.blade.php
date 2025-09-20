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
$no_urut = 1;
?>

<table>
    <tr>
        <td>No Urut</td>
        <td>Nomer RM</td>
        <td>Nama Pasien</td>
        <td>Alamat</td>
        <td>Umur</td>
        <td>Jenis Kelamin</td>
        <td>Kode Diagnosa Utama</td>
        <td>Deskripsi Diagnosa Utama</td>
        <td>Diagnosa Sekunder</td>
        <td>Tindakan</td>
        <td>Bangsal</td>
        <td>Cara Pulang</td>
        <td>DPJP</td>
        <td>Tanggal Masuk</td>
        <td>Tanggal Keluar</td>
        <td>LOS</td>
        <td>Tarif INACBG</td>
        <td>Tarif RS</td>
        <td>Selisih</td>
        <td>Kode Grouper</td>
        <td>Penjamin</td>
        <td>Pontensi Readmisi</td>
    </tr>
    @foreach ($data as $val)

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
    $tglMasuk = \Carbon\Carbon::parse($val->FTTGL_TRANSAKSI)->startOfDay();
    $tglKeluar = \Carbon\Carbon::parse($val->PRWITGL_KELUAR)->startOfDay();
    $selisihHari = $tglMasuk->diffInDays($tglKeluar) + ($tglMasuk <= $tglKeluar ? 1 : 0);
    $selisihTarif = (int) $val->FTTARIPINACBG - (int) $val->TOTAL_BILL;
    ?>

    <tr>
        <td>{{ $no_urut }}</td>
        <td>{{ $val->FTKD_PASIEN ?? '' }}</td>
        <td>{{ $val->NAMAPASIEN ?? '' }}</td>
        <td>{{ $val->ALAMAT ?? '' }}</td>
        <td>
            @if(!empty($val->TGL_LAHIR))
            {{ \Carbon\Carbon::parse($val->TGL_LAHIR)->age }}
            @else
            -
            @endif
        </td>
        <td>
            @if($val->JENIS_KELAMIN == 1)
            L
            @elseif($val->JENIS_KELAMIN == 2)
            P
            @else
            -
            @endif
        </td>

        {{-- Diagnosa utama (kode) --}}
        <td>
            {{ $val->DIAGNOSA_LENGKAP
            ->where('is_primary', 1)
            ->pluck('code')
            ->implode("\n") ?: '-' }}
        </td>

        {{-- Diagnosa utama (deskripsi) --}}
        <td>
            {{ $val->DIAGNOSA_LENGKAP
            ->where('is_primary', 1)
            ->pluck('description')
            ->implode("\n") }}
        </td>

        {{-- Diagnosa sekunder --}}
        <td>
            {{ $val->DIAGNOSA_LENGKAP
        ->where('is_primary', '!=', 1)
        ->map(fn($d) => $d->code . ' - ' . $d->description)
        ->implode(PHP_EOL) }}
        </td>

        {{-- Tindakan --}}
        <td>
            {{ $val->TINDAKAN_LENGKAP
        ->where('is_primary', '!=', 1)
        ->map(fn($t) => $t->code . ' - ' . $t->description)
        ->implode(PHP_EOL) }}
        </td>

        <td>{{ $val->FMKNAMA_KAMAR ?? '' }}</td>
        <td>{{ $val->CARA_PULANG }}</td>
        <td>{{ $val->DPJP ?? '' }}</td>
        <td>{{ $val->PRWITGL_MASUK ? \Carbon\Carbon::parse($val->PRWITGL_MASUK)->format('d-m-Y H:i:s') : '' }}</td>
        <td>{{ $val->PRWITGL_KELUAR ? \Carbon\Carbon::parse($val->PRWITGL_KELUAR)->format('d-m-Y H:i:s') : '' }}</td>
        <td>{{ $selisihHari }}</td>
        <td>{{ $val->FTTARIPINACBG }}</td>
        <td>{{ $val->TOTAL_BILL ?? '' }}</td>
        <td>{{ $selisihTarif }}</td>
        <td>{{ $val->FTKODEINACBG ?? '' }}</td>
        <td>{{ $val->PENJAMIN ?? '' }}</td>

        {{-- Alerts --}}
        <td>
            {{ collect($val->ALERTS)
        ->map(fn($a) => $a->icd_code . ' - ' . strip_tags($a->desc))
        ->implode(PHP_EOL) }}
        </td>
    </tr>

    <?php $no_urut++; ?>
    @endforeach
</table>