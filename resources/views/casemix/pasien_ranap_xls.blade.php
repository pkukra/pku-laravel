<table>
    <tr>
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
        <td>
            Pemeriksaan Penunjang
        </td>
        <td>
            Hasil Penunjang Abnormal
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
            Konfirmasi Koder
        </td>
        <td>
            Konfirmasi Dokter Bangsal
        </td>
        <td>
            Follow Up SPV Bangsal
        </td>
        <td>
            Follow Up MPP
        </td>
    </tr>
    @foreach ($data as $val)

    <tr>
        <td>{{ $val->FTNO_TRANSAKSI ?? '-' }}</td>
        <td>{{ $val->NAMAPASIEN ?? '-' }}</td>
        <td>{{ $val->FTKD_PASIEN ?? '-' }}</td>
        <td>{{ $val->DPJP ?? '-' }}</td>
        <td>{{ $val->FTTGL_TRANSAKSI ? \Carbon\Carbon::parse($val->FTTGL_TRANSAKSI)->format('d-m-Y') : '-' }}</td>
        @php
        $tglMasuk = \Carbon\Carbon::parse($val->FTTGL_TRANSAKSI)->startOfDay();
        $hariIni = now()->startOfDay();
        $selisihHari = $tglMasuk->diffInDays($hariIni) + ($tglMasuk <= $hariIni ? 1 : 0);
        @endphp

        <td>{{ $selisihHari }}</td>
        <td>{{ $val->PEMERIKSAAN_PENUNJANG ?? '-' }}</td>
        <td>{{ $val->HASIL_PENUNJANG_ABNORMAL ?? '-' }}</td>
        <td>{{ $val->KELAS_RAWAT ?? '-' }}</td>
        <td>{{ $val->RAWAT_NAIK ?? '-' }}</td>
        <td></td>
        <td></td>
        <td></td>
        <td>{{ $val->TOTAL_BILL ?? '-' }}</td>
        <td>{{ $val->KONFIRMASI_KODER ?? '-' }}</td>
        <td>{{ $val->KONFIRMASI_DR_BANGSAL ?? '-' }}</td>  
        <td>{{ $val->KONFIRMASI_SPV_BANGSAL ?? '-' }}</td>
        <td>{{ $val->KONFIRMASI_MPP ?? '-' }}</td>
    </tr>
    @endforeach
</table>