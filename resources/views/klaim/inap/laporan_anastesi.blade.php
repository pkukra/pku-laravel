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
    </style>
</head>

<body> <!-- HEADER -->
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
            <td width="35%">: {{ $nama }}</td>
            <td width="15%">Tanggal</td>
            <td width="35%">: {{ $tanggal }}</td>
        </tr>
        <tr>
            <td>Tgl Lahir</td>
            <td>: {{ $tgl_lahir }}</td>
            <td>Ruang</td>
            <td>: {{ $ruang }}</td>
        </tr>
        <tr>
            <td>No RM</td>
            <td>: {{ $norm }}</td>
            <td></td>
            <td></td>
        </tr>
    </table> <br> <!-- DATA OPERASI -->
    <table>
        <tr>
            <td width="20%">Dokter Operator</td>
            <td width="30%">{{ $operator }}</td>
            <td width="20%">Posisi Pasien</td>
            <td width="30%">{{ $posisi }}</td>
        </tr>
        <tr>
            <td>Dokter Anestesi</td>
            <td>{{ $anestesi }}</td>
            <td>BB</td>
            <td>{{ $bb }} Kg</td>
        </tr>
        <tr>
            <td>Perawat Anestesi</td>
            <td>{{ $perawat }}</td>
            <td>TB</td>
            <td>{{ $tb }} Cm</td>
        </tr>
        <tr>
            <td>Diagnosa Pre OP</td>
            <td>{{ $diagnosa_pre }}</td>
            <td>Hb</td>
            <td>{{ $hb }}</td>
        </tr>
        <tr>
            <td>Nama Operasi</td>
            <td>{{ $operasi }}</td>
            <td>Gol Darah</td>
            <td>{{ $goldarah }}</td>
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
                {{ $jenis_anestesi }}
            </td>

            <td width="15%">
                1. Premedikasi
            </td>

            <td width="20%">
                {{ $premedikasi }}
            </td>

            <td width="15%">
                Nama Obat
            </td>

            <td colspan="3">
                {{ $nama_obat }}
            </td>

        </tr>

        <tr>

            <td>2. Analgesik</td>
            <td>{{ $analgesik }}</td>

            <td>Adjuvan</td>
            <td colspan="3">
                {{ $adjuvan }}
            </td>

        </tr>

        <tr>

            <td>3. Induksi</td>
            <td>{{ $induksi }}</td>

            <td>Spinocan No</td>
            <td>{{ $spinocan }}</td>

            <td>Lokasi</td>
            <td>{{ $lokasi }}</td>

        </tr>

        <tr>

            <td>4. Msc Relaxan</td>
            <td>{{ $msc_relaxan }}</td>

            <th colspan="4">
                Oksigenasi
            </th>

        </tr>

        <tr>

            <td>5. Agent Anest.</td>
            <td>{{ $agent_anest }}</td>

            <td colspan="2" rowspan="3">
                {{ $oksigenasi }}
            </td>

            <td colspan="2" rowspan="3"
                class="text-center">

                Level O₂
                <br>

                {{ $level_o2 }} lpm

            </td>

        </tr>

        <tr>

            <td>6. Reversal</td>
            <td>{{ $reversal }}</td>

        </tr>

        <tr>

            <td>7. Antidote</td>
            <td>{{ $antidote }}</td>

        </tr>

        <tr>

            <td colspan="2">
                Catatan :
                <br>
                {!! nl2br($catatan) !!}
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

                {{ $catatan }}

            </td>

            <td colspan="2"
                height="70"
                class="text-center">

                @if(!empty($ttd_perawat))
                <img
                    src="{{ $ttd_perawat }}"
                    width="120">
                @endif

            </td>

            <td colspan="2"
                class="text-center">

                @if(!empty($ttd_dokter))
                <img
                    src="{{ $ttd_dokter }}"
                    width="120">
                @endif

            </td>

        </tr>

        <tr>

            <td colspan="2"
                class="text-center">

                {{ $nama_perawat }}

            </td>

            <td colspan="2"
                class="text-center">

                {{ $nama_dokter }}

            </td>

        </tr>

    </table>

    <table>
        <tr>
            <th width="15%">TTV Akhir</th>
            <td> TD : {{ $td_sistole_akhir }}/{{ $td_diastole_akhir }} mmHg &nbsp;&nbsp;&nbsp; Nadi : {{ $nadi_akhir }} x/menit &nbsp;&nbsp;&nbsp; RR : {{ $rr_akhir }} x/menit &nbsp;&nbsp;&nbsp; Suhu : {{ $suhu_akhir }} °C </td>
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
            <td align="center"> {{ $nama_perawat }} </td>
            <td align="center"> {{ $nama_dokter }} </td>
        </tr>
    </table>


</body>

</html>