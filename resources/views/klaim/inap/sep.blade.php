<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .page { width:100%; margin:0; padding:5mm; }
        .page_header { width:100%; }
        .page_header img { float:left }
        .content { width:100%; border-collapse:collapse; font-size:10px; }
        .content td { padding:1px; vertical-align:top }
        .fjpp { width:100%; border-collapse:collapse; font-size:10px; }
        .fjpp th, .fjpp td { border:1px solid #000; padding:1px }
        .center { text-align:center }
        .right { text-align:right }
    </style>
</head>
<body>
<div class="page">
    <table class="page_header">
        <tr>
            <td style="width:33%">
                <img src="{{ public_path('statics/bpjs_logo.png') }}" width="200" height="35" alt="logo">
            </td>
            <td class="center" style="width:33%">
                <div style="font-size:12px">SURAT ELEGIBILITAS PESERTA<br><br>RS PKU Muhammadiyah Karanganyar</div>
            </td>
            <td style="width:33%">
                <p><strong>,{{ $FMPRB }}</strong></p>
            </td>
        </tr>
    </table>

    <br>
    <table class="content">
        <tr>
            <td style="width:19%">No. SEP</td>
            <td style="width:1%">:</td>
            <td style="width:40%"><strong style="font-size:15px">{{ $FMNOSEP }}</strong></td>
            <td style="width:10%"></td>
            <td style="width:1%"></td>
            <td style="width:25%"></td>
        </tr>
        <tr>
            <td>Tgl. SEP</td>
            <td>:</td>
            <td>{{ $FMTGL_SEP }}</td>
            <td>Peserta</td>
            <td>:</td>
            <td><strong style="font-size:11px">{{ $FMPESERTA }}</strong></td>
        </tr>
        <tr>
            <td>No. Kartu</td>
            <td>:</td>
            <td>{{ $FMNO_KARTU }} <span style="margin-left:70px">( MR. : <strong style="font-size:13px">{{ $FMPASIEN_ID }}</strong>)</span></td>
            <td>C O B</td>
            <td>:</td>
            <td></td>
        </tr>
        <tr>
            <td>Nama Peserta</td>
            <td>:</td>
            <td>{{ $NAMAPASIEN }}</td>
            <td>Jns Rawat</td>
            <td>:</td>
            <td>{{ $FMJENISRAWAT == 2 ? 'Rawat jalan' : 'Rawat Inap' }}</td>
        </tr>
        <tr>
            <td>Tgl. Lahir</td>
            <td>:</td>
            <td>{{ $FMTGL_LAHIR }} <span style="margin-left:50px">( Kelamin : {{ $FMJENIS_KELAMIN == 'L' ? 'Laki-Laki' : 'Perempuan' }} )</span></td>
            <td>Jns Kunjung</td>
            <td>:</td>
            <td>{{ $TUJ_KUNJUNGAN }}</td>
        </tr>
        <tr>
            <td>No. Telepon</td>
            <td>:</td>
            <td>{{ $telp ?? $FMNOTELP }}</td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Sub/Spesialis</td>
            <td>:</td>
            <td>{{ $FMPOLYN }}</td>
            <td>Poli Perujuk</td>
            <td>:</td>
            <td></td>
        </tr>
        <tr>
            <td>Dokter</td>
            <td>:</td>
            <td>{{ $dpjpn }}</td>
            <td>Hak Rawat</td>
            <td>:</td>
            <td><strong style="font-size:13px">{{ $FMNAMA_KELAS }}</strong></td>
        </tr>
        <tr>
            <td>Faskes Perujuk</td>
            <td>:</td>
            <td>{{ $FMPPK_RUJUKANN }}</td>
            <td>Kls Rawat</td>
            <td>:</td>
            <td>-</td>
        </tr>
        <tr>
            <td>Diagnosa Awal</td>
            <td>:</td>
            <td>{{ $FMDIAGNOSA }}</td>
            <td>Penjamin</td>
            <td>:</td>
            <td>-</td>
        </tr>
        <tr>
            <td>Catatan</td>
            <td>:</td>
            <td>{{ $FMCATATAN }}</td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="3">
                <small>*Saya Menyetujui BPJS menggunakan informasi Medis Pasien jika diperlukan<br>&nbsp;*SEP bukan sebagai bukti penjaminan peserta</small>
            </td>
            <td></td>
            <td></td>
            <td class="center">Pasien / Keluarga Pasien <br><br>
                <div style="font-size:10px;">QR: {{ $FMNO_KARTU }}</div>
                __________________
            </td>
        </tr>
        <tr>
            <td colspan="3">Cetakan ke &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $FMPCETAK }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $FMTGL_SEP }}</td>
            <td></td>
            <td></td>
            <td class="center">{{ $FMNAMA_PESERTA }}</td>
        </tr>
    </table>

    <br>
    <table class="fjpp">
        <tr>
            <td class="center" colspan="3"><strong>FORMULIR JAMINAN PELAYANAN PASIEN JAMINAN KESEHATAN NASIONAL</strong></td>
        </tr>
        <tr>
            <td class="center" colspan="2"><b>DIAGNOSIS</b></td>
            <td class="center">CODE ICD X</td>
        </tr>

        @if(count($penyakit_premiers) > 0)
            @php $i = 1; @endphp
            @foreach($penyakit_premiers as $penyakit_premier)
                <tr>
                    <td style="text-align:left; width:20%; vertical-align:top;"><b>{{ $i == 1 ? 'Diagnosa Primer' : '' }}</b></td>
                    <td style="text-align:left; width:60%;">{{ $penyakit_premier['PENYAKIT'] }}</td>
                    <td style="text-align:center; width:20%;">{{ $penyakit_premier['MRPKD_PENYAKIT'] }}</td>
                </tr>
                @php $i++; @endphp
            @endforeach
        @else
            <tr>
                <td style="text-align:left; width:20%; vertical-align:top;"><b>Diagnosa Primer</b></td>
                <td style="text-align:left; width:60%;"></td>
                <td style="text-align:center; width:20%;"></td>
            </tr>
        @endif

        @if(count($penyakit_sekunders) > 0)
            @php $i = 1; @endphp
            @foreach($penyakit_sekunders as $penyakit_sekunder)
                <tr>
                    <td style="text-align:left; width:20%; vertical-align:top;"><b>{{ $i == 1 ? 'Diagnosa Sekunder' : '' }}</b></td>
                    <td style="text-align:left; width:60%;">{{ $penyakit_sekunder['PENYAKIT'] }}</td>
                    <td style="text-align:center; width:20%;">{{ $penyakit_sekunder['MRPKD_PENYAKIT'] }}</td>
                </tr>
                @php $i++; @endphp
            @endforeach
        @else
            <tr>
                <td style="text-align:left; width:20%; vertical-align:top;"><b>Diagnosa Sekunder</b></td>
                <td style="text-align:left; width:60%;"></td>
                <td style="text-align:center; width:20%;"></td>
            </tr>
        @endif
    </table>

    <br>
    <table class="fjpp">
        <tr>
            <td class="center" colspan="4"><strong>TINDAKAN YANG DI LAKUKAN</strong></td>
        </tr>
        <tr>
            <td style="width:55%;" class="center"><b>TINDAKAN PRIMER</b></td>
            <td style="width:15%;" class="center"><b>CODE ICD 9 CM</b></td>
            <td style="width:15%;" class="center"><b>Paraf Petugas</b></td>
            <td style="width:15%;" class="center"><b>Paraf Pasien/Klg</b></td>
        </tr>

        @foreach($tindakans as $tindakan)
            <tr style="vertical-align:top;">
                <td style="text-align:left; width:55%; vertical-align:top;"><div style="white-space: normal; word-break: break-word;">{{ $tindakan['FMI9KETERANGAN'] }}</div></td>
                <td style="text-align:center;">{{ $tindakan['MRTKD_TINDAKAN'] }}</td>
                <td></td>
                <td></td>
            </tr>
        @endforeach

        <tr>
            <td colspan="4" style="text-align:left"><strong>Pemeriksaan penunjang yang mendukung diagnosis :</strong></td>
        </tr>
        <tr>
            <td colspan="2" rowspan="2"></td>
            <td class="center"><b>Paraf Petugas</b></td>
            <td class="center"><b>Paraf Pasien/Klg</b></td>
        </tr>
        <tr>
            <td class="center"><br><br><br></td>
            <td class="center"></td>
        </tr>

        <tr>
            <td colspan="4" style="text-align:left"><strong>Tanggal :</strong></td>
        </tr>

        <tr>
            <td colspan="2">
                <table style="border-collapse:collapse; border:none;">
                    <tr><td style="border:none;"><b>Tindak Lanjut :</b></td></tr>
                    <tr>
                        <td style="border:none; width:150px">Rawat Jalan <br><br> Pulang Paksa <br><br> Dirujuk Ke ...............</td>
                        <td style="border:none;">Rawat Inap <br><br> Meninggal <br><br> Konsul Ke ...............</td>
                    </tr>
                </table>
            </td>
            <td class="center" colspan="2">Dokter <br>
                <div style="font-size:10px;">QR: {{ $dpjpn }}, {{ $FMTGL_SEP }}</div>
                __________________ <br><br>
                {{ $dpjpn }}
            </td>
        </tr>

        <tr>
            <td colspan="4" style="text-align:left"><strong>Catatan Khusus :</strong>
                @if(count($catatans) < 1)
                    <br><br><br><br><br><br>
                @endif
                @foreach($catatans as $catatan)
                    <p>{{ $catatan['MRCATATANKHUSUS'] }}</p>
                @endforeach
            </td>
        </tr>
    </table>

</div>
</body>
</html>
