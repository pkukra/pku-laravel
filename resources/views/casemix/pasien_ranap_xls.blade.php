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
    ->map(fn($d) => ' - ' . $d->code.($d->is_code_warning ? ' (Rawan Pending) ' : '') . $d->description)
    ->values();

    // kumpulkan tindakan
    $tindakan = collect($val->TINDAKAN_LENGKAP)
    ->where('is_primary', '!=', 1)
    ->map(fn($t) => $t->code . ' - ' . $t->description)
    ->values();

    // kumpulkan alert
    $alerts = collect($val->ALERTS)
    ->map(fn($a) => $a['icd_code'].' - '.strip_tags($a['description']))
    ->values();

    // ambil jumlah baris terbanyak
    $rowCount = max($diagnosaSekunder->count(), $tindakan->count(), $alerts->count(), 1);
    @endphp

    @for ($i = 0; $i < $rowCount; $i++)
        <tr>
        @if ($i === 0)
        {{-- kolom utama dengan rowspan --}}
        <td rowspan="{{ $rowCount }}">{{ $no_urut }}</td>
        <td rowspan="{{ $rowCount }}">{{ $val->FTKD_PASIEN ?? '' }}</td>
        <td rowspan="{{ $rowCount }}">{{ $val->NAMAPASIEN ?? '' }}</td>
        <td rowspan="{{ $rowCount }}">{{ $val->ALAMAT ?? '' }}</td>
        <td rowspan="{{ $rowCount }}">
            {{ !empty($val->TGL_LAHIR) ? \Carbon\Carbon::parse($val->TGL_LAHIR)->age : '-' }}
        </td>
        <td rowspan="{{ $rowCount }}">
            @if ($val->JENIS_KELAMIN == 1) L
            @elseif ($val->JENIS_KELAMIN == 2) P
            @else -
            @endif
        </td>
        <td rowspan="{{ $rowCount }}">
            {{ $val->DIAGNOSA_LENGKAP->where('is_primary', 1)->pluck('code')->implode(', ') ?: '-' }}
        </td>
        <td rowspan="{{ $rowCount }}">
            {{
                $val->DIAGNOSA_LENGKAP
                    ->where('is_primary', 1)
                    ->map(function($d) {
                        return ($d->is_code_warning ? ' (rawan pending) ' : '').$d->description ;
                    })
                    ->implode(', ') ?: '-'
            }}
        </td>
        @endif

        {{-- kolom dinamis (berbeda tiap baris) --}}
        <td>{{ $diagnosaSekunder[$i] ?? '' }}</td>
        <td>{{ $tindakan[$i] ?? '' }}</td>
        <td>{{ $alerts[$i] ?? '' }}</td>

        @if ($i === 0)
        {{-- kolom tetap --}}
        <td rowspan="{{ $rowCount }}">{{ $val->FMKNAMA_KAMAR ?? '' }}</td>
        <td rowspan="{{ $rowCount }}">{{ $val->CARA_PULANG }}</td>
        <td rowspan="{{ $rowCount }}">{{ $val->DPJP ?? '' }}</td>
        <td rowspan="{{ $rowCount }}">{{ $val->PRWITGL_MASUK ? \Carbon\Carbon::parse($val->PRWITGL_MASUK)->format('d-m-Y H:i:s') : '' }}</td>
        <td rowspan="{{ $rowCount }}">{{ $val->PRWITGL_KELUAR ? \Carbon\Carbon::parse($val->PRWITGL_KELUAR)->format('d-m-Y H:i:s') : '' }}</td>
        <td rowspan="{{ $rowCount }}">
            @php
            $tglMasuk = \Carbon\Carbon::parse($val->FTTGL_TRANSAKSI)->startOfDay();
            $tglKeluar = \Carbon\Carbon::parse($val->PRWITGL_KELUAR)->startOfDay();
            $selisihHari = $tglMasuk->diffInDays($tglKeluar) + ($tglMasuk <= $tglKeluar ? 1 : 0);
                @endphp
                {{ $selisihHari }}
                </td>
        <td rowspan="{{ $rowCount }}">{{ $val->FTTARIPINACBG }}</td>
        <td rowspan="{{ $rowCount }}">{{ $val->TOTAL_BILL ?? '' }}</td>
        <td rowspan="{{ $rowCount }}">{{ (int) $val->FTTARIPINACBG - (int) $val->TOTAL_BILL }}</td>
        <td rowspan="{{ $rowCount }}">{{ $val->FTKODEINACBG ?? '' }}</td>
        <td rowspan="{{ $rowCount }}">{{ $val->PENJAMIN ?? '' }}</td>
        @endif
        </tr>
        @endfor

        @php $no_urut++; @endphp
        @endforeach
</table>