<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 3px;
        }

        .header-title {
            background: #000;
            color: #fff;
            text-align: center;
            font-weight: bold;
            padding: 6px;
        }

        .text-center {
            text-align: center;
        }

        .no-border {
            border: none !important;
        }

        .monitoring {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 9px;
        }

        .monitoring th,
        .monitoring td {
            border: 1px solid #000;
            padding: 2px;
            text-align: center;
            vertical-align: middle;
        }

        .monitoring th {
            font-weight: bold;
        }

        .monitoring th[rowspan] {
            writing-mode: vertical-rl;
            text-align: center;
            vertical-align: middle;
            font-size: 7px;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <table>
        <tr>
            <td class="no-border" width="60"> <img src="{{ public_path('statics/logo.png') }}" width="60"> </td>
            <td class="no-border"> <b>RS PKU MUHAMMADIYAH KARANGANYAR</b><br> Jl. Papahan Tasikmadu Karanganyar <br> Telp (0271) 494019, 494649 Fax (0271) 495389 </td>
            <td class="no-border" align="right"> RM 6.15 Rev 1 </td>
        </tr>
    </table>
    <div class="header-title"> LAPORAN ANESTESI </div> <br> <!-- IDENTITAS -->
    <table>
        <tr>
            <td width="15%">Nama</td>
            <td width="35%"> {{ data_get($rs_pasien, 'NAMAPASIEN', '') }}</td>
            <td width="15%">Tanggal</td>
            <td width="35%"> {{ data_get($data_anastesi, 'TANGGAL', data_get($data_anastesi, 'ANEST_MULAI', data_get($rs_pasien, 'FJOKTGL_OP', ''))) }}</td>
        </tr>
        <tr>
            <td>Tgl Lahir</td>
            <td> {{ data_get($rs_pasien, 'TGL_LAHIR', '') }}</td>
            <td>Ruang</td>
            <td> {{ data_get($rs_pasien, 'FJOKNO_KAMAR', '') }}</td>
        </tr>
        <tr>
            <td>No RM</td>
            <td> {{ data_get($rs_pasien, 'KD_PASIEN', '') }}</td>
            <td></td>
            <td></td>
        </tr>
    </table> <br> <!-- DATA OPERASI -->
    <table>
        <tr>
            <td width="20%">Dokter Operator</td>
            <td width="30%">{{ data_get($op, 'DokterOp', data_get($op, 'FMDDOKTERN', '')) }}</td>
            <td width="20%">Posisi Pasien</td>
            <td width="30%">{{ data_get($data_anastesi, 'POSISI_PASIEN', '') }}</td>
        </tr>
        <tr>
            <td>Dokter Anestesi</td>
            <td>{{ data_get($op, 'Anastesis', data_get($data_anastesi, 'DOKTER_ANESTESI', data_get($data_anastesi, 'dokter_anestesi', ''))) }}</td>
            <td>BB</td>
            <td>{{ data_get($data_anastesi, 'BB', data_get($rs_pasien, 'FS_BB', '')) }} Kg</td>
        </tr>
        <tr>
            <td>Perawat Anestesi</td>
            <td>{{ data_get($ttd_parawat, 'nama_lengkap', '') }}</td>
            <td>TB</td>
            <td>{{ data_get($data_anastesi, 'TB', data_get($rs_pasien, 'FS_TB', '')) }} Cm</td>
        </tr>
        <tr>
            <td>Diagnosa Pre OP</td>
            <td>{{ data_get($op, 'FS_DIAGNOSIS', data_get($rs_pasien, 'FMOKDIAG', '')) }}</td>
            <td>Hb</td>
            <td>{{ data_get($data_anastesi, 'HB', '') }}</td>
        </tr>
        <tr>
            <td>Nama Operasi</td>
            <td>{{ data_get($op, 'NAMA_OPERASI', data_get($op, 'FS_TINDAKAN_OP', '')) }}</td>
            <td>Gol Darah</td>
            <td>{{ data_get($data_anastesi, 'GOL_DARAH', '') }}</td>
        </tr>
    </table> <br> <!-- TABEL MONITORING -->
    <table class="monitoring">
        {{-- JAM --}}
        <tr>
            <th colspan="2">Jam</th>

            @foreach($chart as $row)
            <td>{{ date('H:i', strtotime($row['jam'])) }}</td>
            @endforeach
        </tr>

        {{-- GAS --}}
        <tr>
            <th colspan="2">O₂</th>

            @foreach($chart as $row)
            <td>{{ $row['o2'] }}</td>
            @endforeach
        </tr>

        <tr>
            <th colspan="2">N₂O</th>

            @foreach($chart as $row)
            <td>{{ $row['n2o'] }}</td>
            @endforeach
        </tr>

        <tr>
            <th colspan="2">Udara</th>

            @foreach($chart as $row)
            <td>{{ $row['udara'] }}</td>
            @endforeach
        </tr>

        {{-- INPUT --}}
        <tr>
            <th rowspan="2" width="25">
                I<br>N<br>P<br>U<br>T
            </th>

            <th width="70">
                Infus
            </th>

            @foreach($chart as $row)
            <td>{{ $row['infus'] }}</td>
            @endforeach
        </tr>

        <tr>
            <th>Transfusi</th>

            @foreach($chart as $row)
            <td>{{ $row['transfusi'] }}</td>
            @endforeach
        </tr>

        {{-- OUTPUT --}}
        <tr>
            <th rowspan="2" width="25">
                O<br>U<br>T<br>P<br>U<br>T
            </th>

            <th>Urine</th>

            @foreach($chart as $row)
            <td>{{ $row['urine'] }}</td>
            @endforeach
        </tr>

        <tr>
            <th>Bleeding</th>

            @foreach($chart as $row)
            <td>{{ $row['bleeding'] }}</td>
            @endforeach
        </tr>

        {{-- AREA GRAFIK --}}
        <tr>
            <td colspan="{{ count($chart) + 2 }}"
                style="
            height:220px;
            padding:0;
            text-align:center;
            vertical-align:middle;
        ">

                {{-- SVG Grafik nanti di sini --}}

            </td>
        </tr>

        {{-- MONITORING LANJUTAN --}}
        <tr>
            <th colspan="2">SpO₂</th>

            @foreach($chart as $row)
            <td>{{ $row['spo2'] }}</td>
            @endforeach
        </tr>

        <tr>
            <th colspan="2">ETCO₂</th>

            @foreach($chart as $row)
            <td>{{ $row['etco2'] }}</td>
            @endforeach
        </tr>
    </table>

    <table>

        <tr>
            <th width="20%">
                Teknik Anestesi
            </th>

            <th colspan="2">
                Obat Sedasi & Anestesi
            </th>

            <th colspan="4">
                Obat Anestesi (SAB)
            </th>
        </tr>

        <tr>

            <td rowspan="8">
                {{ data_get($data_anastesi, 'JENIS_ANESTESI', '') }}
            </td>

            <td width="15%">
                1. Premedikasi
            </td>

            <td width="20%">
                {{ data_get($data_anastesi, 'PRE_MEDIKASI', '') }}
            </td>

            <td width="15%">
                Nama Obat
            </td>

            <td colspan="3">
                {{ data_get($data_anastesi, 'NAMA_OBAT', '') }}
            </td>

        </tr>

        <tr>

            <td>2. Analgesik</td>
            <td>{{ data_get($data_anastesi, 'ANALGESI', '') }}</td>

            <td>Adjuvan</td>
            <td colspan="3">
                {{ data_get($data_anastesi, 'ADJUVAN', '') }}
            </td>

        </tr>

        <tr>

            <td>3. Induksi</td>
            <td>{{ data_get($data_anastesi, 'INDUKSI', '') }}</td>

            <td>Spinocan No</td>
            <td>{{ data_get($data_anastesi, 'SPINOCAN', '') }}</td>

            <td>Lokasi</td>
            <td>{{ data_get($data_anastesi, 'LOKASI', '') }}</td>

        </tr>

        <tr>

            <td>4. Msc Relaxan</td>
            <td>{{ data_get($data_anastesi, 'MSC_RELAXAN', '') }}</td>

            <th colspan="4">
                Oksigenasi
            </th>

        </tr>

        <tr>

            <td>5. Agent Anest.</td>
            <td>{{ data_get($data_anastesi, 'AGEN_ANEST', '') }}</td>

            <td colspan="2" rowspan="3">
                {{ data_get($data_anastesi, 'OKSIGENASI', '') }}
            </td>

            <td colspan="2" rowspan="3"
                class="text-center">

                Level O₂
                <br>

                {{ data_get($data_anastesi, 'LEVEL_O2', '') }} lpm

            </td>

        </tr>

        <tr>

            <td>6. Reversal</td>
            <td>{{ data_get($data_anastesi, 'REVERSAL', '') }}</td>

        </tr>

        <tr>

            <td>7. Antidote</td>
            <td>{{ data_get($data_anastesi, 'ANTIDOTE', '') }}</td>

        </tr>

        <tr>

            <td colspan="2">
                Catatan :
                <br>
                {!! nl2br(data_get($data_anastesi, 'CATATAN', '')) !!}
            </td>

            <th colspan="2">
                Perawat Anestesi
            </th>

            <th colspan="2">
                Dokter Anestesi
            </th>

        </tr>

        <tr>

            <td colspan="3" rowspan="2">

                {{ data_get($data_anastesi, 'CATATAN', '') }}

            </td>

            <td colspan="2"
                height="70"
                class="text-center">

                @if(!empty(data_get($ttd_parawat, 'NM_FILE')))
                <img
                    src="{{ data_get($ttd_parawat, 'NM_FILE') }}"
                    width="120">
                @endif

            </td>

            <td colspan="2"
                class="text-center">

                @if(!empty(data_get($ttd, 'NM_FILE')))
                <img
                    src="{{ data_get($ttd, 'NM_FILE') }}"
                    width="120">
                @endif

            </td>

        </tr>

        <tr>

            <td colspan="2"
                class="text-center">

                {{ data_get($ttd_parawat, 'nama_lengkap', '') }}

            </td>

            <td colspan="2"
                class="text-center">

                {{ data_get($ttd, 'FMDDOKTERN', '') }}

            </td>

        </tr>

    </table>

    <table>
        <tr>
            <th width="15%">TTV Akhir</th>
            <td> TD : {{ data_get($data_anastesi, 'TD_SISTOLE_AKHIR', '') }}/{{ data_get($data_anastesi, 'TD_DIASTOLE_AKHIR', '') }} mmHg &nbsp;&nbsp;&nbsp; Nadi : {{ data_get($data_anastesi, 'NADI2', data_get($data_anastesi, 'NADI', '')) }} x/menit &nbsp;&nbsp;&nbsp; RR : {{ data_get($data_anastesi, 'RR2', data_get($data_anastesi, 'RR', '')) }} x/menit &nbsp;&nbsp;&nbsp; Suhu : {{ data_get($data_anastesi, 'SUHU2', data_get($data_anastesi, 'SUHU', '')) }} °C </td>
        </tr>
    </table> <br>
    <table>
        <tr>
            <th>Perawat Anestesi</th>
            <th>Dokter Anestesi</th>
        </tr>
        <tr>
            <td height="80" align="center"> TTD </td>
            <td align="center"> TTD </td>
        </tr>
        <tr>
            <td align="center"> {{ data_get($ttd_parawat, 'nama_lengkap', '') }} </td>
            <td align="center"> {{ data_get($ttd, 'FMDDOKTERN', '') }} </td>
        </tr>
    </table>


</body>

</html>