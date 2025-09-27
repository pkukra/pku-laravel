<table border="1" cellpadding="5">
    <tr>
        <th>No Urut</th>
        <th>Nomer RM</th>
        <th>Nama Pasien</th>
        <th>Alamat</th>
        <th>Umur</th>
        <th>Jenis Kelamin</th>
        <th>Kode Diagnosa Utama</th>
        <th>Deskripsi Diagnosa Utama</th>
        <th>Diagnosa Sekunder</th>
        <th>Tindakan</th>
        <th>Alert</th>
        <th>Bangsal</th>
        <th>Cara Pulang</th>
        <th>DPJP</th>
        <th>Tanggal Masuk</th>
        <th>Tanggal Keluar</th>
        <th>LOS</th>
        <th>Tarif INACBG</th>
        <th>Tarif RS</th>
        <th>Selisih</th>
        <th>Kode Grouper</th>
        <th>Penjamin</th>
    </tr>

    @php $no_urut = 1; @endphp
    @foreach ($data as $val)
        @php
        // kumpulkan diagnosa sekunder
        $diagnosaSekunder = collect($val->DIAGNOSA_LENGKAP)
            ->where('is_primary', '!=', 1)
            ->map(fn($d) => '• '.$d->code.' - '.($d->is_code_warning ? ' (Rawan Pending) ' : '') . $d->description)
            ->implode('<br>');

        // kumpulkan tindakan
        $tindakan = collect($val->TINDAKAN_LENGKAP)
            ->where('is_primary', '!=', 1)
            ->map(fn($t) => '• '.$t->code . ' - ' . $t->description)
            ->implode('<br>');

        // kumpulkan alert
        $alerts = collect($val->ALERTS)
            ->map(fn($a) => '• '.$a['icd_code'].' - '.strip_tags($a['description']))
            ->implode('<br>');

        // hitung umur
        $umur = !empty($val->TGL_LAHIR) ? \Carbon\Carbon::parse($val->TGL_LAHIR)->age : '-';

        // hitung LOS
        $tglMasuk = \Carbon\Carbon::parse($val->FTTGL_TRANSAKSI)->startOfDay();
        $tglKeluar = \Carbon\Carbon::parse($val->PRWITGL_KELUAR)->startOfDay();
        $selisihHari = $tglMasuk->diffInDays($tglKeluar) + ($tglMasuk <= $tglKeluar ? 1 : 0);

        // diagnosa utama
        $kodeUtama = $val->DIAGNOSA_LENGKAP->where('is_primary', 1)->pluck('code')->implode(', ') ?: '-';
        $descUtama = $val->DIAGNOSA_LENGKAP
            ->where('is_primary', 1)
            ->map(fn($d) => ($d->is_code_warning ? ' (rawan pending) ' : '').$d->description)
            ->implode(', ') ?: '-';
        @endphp

        <tr>
            <td>{{ $no_urut }}</td>
            <td>{{ $val->FTKD_PASIEN ?? '' }}</td>
            <td>{{ $val->NAMAPASIEN ?? '' }}</td>
            <td>{{ $val->ALAMAT ?? '' }}</td>
            <td>{{ $umur }}</td>
            <td>
                @if ($val->JENIS_KELAMIN == 1) L
                @elseif ($val->JENIS_KELAMIN == 2) P
                @else -
                @endif
            </td>
            <td>{{ $kodeUtama }}</td>
            <td>{{ $descUtama }}</td>
            <td>{!! $diagnosaSekunder !!}</td>
            <td>{!! $tindakan !!}</td>
            <td>{!! $alerts !!}</td>
            <td>{{ $val->FMKNAMA_KAMAR ?? '' }}</td>
            <td>{{ $val->CARA_PULANG }}</td>
            <td>{{ $val->DPJP ?? '' }}</td>
            <td>{{ $val->PRWITGL_MASUK ? \Carbon\Carbon::parse($val->PRWITGL_MASUK)->format('d-m-Y H:i:s') : '' }}</td>
            <td>{{ $val->PRWITGL_KELUAR ? \Carbon\Carbon::parse($val->PRWITGL_KELUAR)->format('d-m-Y H:i:s') : '' }}</td>
            <td>{{ $selisihHari }}</td>
            <td>{{ $val->FTTARIPINACBG }}</td>
            <td>{{ $val->TOTAL_BILL ?? '' }}</td>
            <td>{{ (int) $val->FTTARIPINACBG - (int) $val->TOTAL_BILL }}</td>
            <td>{{ $val->FTKODEINACBG ?? '' }}</td>
            <td>{{ $val->PENJAMIN ?? '' }}</td>
        </tr>

        @php $no_urut++; @endphp
    @endforeach
</table>
