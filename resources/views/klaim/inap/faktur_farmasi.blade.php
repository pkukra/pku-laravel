<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 15px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #000;
        }

        .title-rs {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 10px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .table-obat {
            font-size: 8px;
        }

        .table-obat thead th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px;
            text-align: center;
        }

        .table-obat td {
            padding: 2px;
            vertical-align: top;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .group-header td {
            border-top: 1px solid #666;
            font-weight: bold;
            padding-top: 4px;
        }

        .subtotal td {
            border-top: 1px dashed #000;
            font-weight: bold;
        }

        .grand-total {
            margin-top: 10px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }

        .footer {
            margin-top: 15px;
            font-size: 8px;
        }

        .nowrap {
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <div class="title-rs"> RS. PKU MUHAMMADIYAH KARANGANYAR </div>
    <div class="title"> PERINCIAN BIAYA FARMASI </div>

    <table class="table-obat">
        <thead>
            <tr>
                <th width="14%">No. Resep</th>
                <th width="8%">Tgl</th>
                <th width="34%">Nama Obat</th>
                <th width="8%">Sat</th>
                <th width="8%">H Jual</th>
                <th width="5%">Qty</th>
                <th width="5%">Disc</th>
                <th width="6%">Disc Rp</th>
                <th width="6%">Adm</th>
                <th width="12%">Total</th>
            </tr>
        </thead>

        <tbody>

            @php
            $grandTotal = 0;
            $grandRetur = 0;
            $grandRacik = 0;
            $grandResep = 0;
            $grandBulat = 0;
            @endphp

            @foreach($data as $resep)

            {{-- ===================== RETUR ===================== --}}
            @if($resep->FDTJENISTRANSAKSI == 'KR')

            @php
            $grandRetur += (float)$resep->FDTKREDIT;
            @endphp

            <tr class="group-header">
                <td colspan="3">
                    {{ $resep->FDTNO_FAKTUR }}
                </td>
                <td colspan="7">
                    <b>RETUR OBAT</b>
                </td>
            </tr>

            <tr>
                <td colspan="9" class="right">
                    Pengurangan karena retur obat
                </td>
                <td class="right">
                    ({{ number_format($resep->FDTKREDIT,0,',','.') }})
                </td>
            </tr>

            @continue

            @endif


            {{-- ===================== RESEP ===================== --}}

            @php
            $subtotal = 0;
            @endphp

            <tr class="group-header">
                <td colspan="3">
                    {{ $resep->FDTNO_FAKTUR }}
                    -
                    {{ trim($resep->FHFJDOKTERN) }}
                    -
                    {{ \Carbon\Carbon::parse($resep->FDTTGL_TRANSAKSI)->translatedFormat('d/m/Y') }}
                </td>

                <td colspan="7"></td>
            </tr>

            @foreach($resep->items as $item)

            @php
            $harga = (float)$item->FDFJHJUAL;
            $qty = (float)$item->FDFJQTY;
            $disc = (float)$item->FDFJDISC1;
            $discRp = (float)$item->FDFJDISC4;
            $total = (float)$item->FDFJTOTAL;

            $subtotal += $total;
            @endphp

            <tr>
                <td></td>
                <td></td>
                <td>{{ trim($item->FDFJBRGN) }}</td>
                <td class="center">{{ trim($item->FDFJSATUAN) }}</td>
                <td class="right">{{ number_format($harga,0,',','.') }}</td>
                <td class="center">{{ rtrim(rtrim($qty,'0'),'.') }}</td>
                <td class="center">{{ number_format($disc,0,',','.') }}</td>
                <td class="right">{{ number_format($discRp,0,',','.') }}</td>
                <td class="right">{{ number_format($item->FDFJEMBALAGE,0,',','.') }}</td>
                <td class="right">{{ number_format($total,0,',','.') }}</td>
            </tr>

            @endforeach

            @php

            $racik = (float)$resep->FHFJRACIK;
            $bulat = (float)$resep->FHFJBULAT;

            $totalPerResep = $subtotal
            + $racik
            + $bulat;

            $grandTotal += $totalPerResep;

            $grandRacik += $racik;
            $grandBulat += $bulat;

            @endphp

            <tr class="subtotal">
                <td colspan="9" class="right">
                    Sub Total Obat :
                </td>
                <td class="right">
                    {{ number_format($subtotal,0,',','.') }}
                </td>
            </tr>

            @if($racik > 0)
            <tr>
                <td colspan="9" class="right">
                    Biaya Racik
                </td>
                <td class="right">
                    {{ number_format($racik,0,',','.') }}
                </td>
            </tr>
            @endif

            @if($bulat != 0)
            <tr>
                <td colspan="9" class="right">
                    Pembulatan
                </td>
                <td class="right">
                    {{ number_format($bulat,0,',','.') }}
                </td>
            </tr>
            @endif

            <tr class="subtotal">
                <td colspan="9" class="right">
                    <b>TOTAL Per Transaksi :</b>
                </td>
                <td class="right">
                    <b>{{ number_format($totalPerResep,0,',','.') }}</b>
                </td>
            </tr>

            @endforeach

        </tbody>

    </table>

    @php
    $totalAkhir = $grandTotal - $grandRetur;
    @endphp

    <div class="grand-total">

        <table>

            <tr>
                <td width="25%">
                    <b>DPHO :</b>
                </td>

                <td width="25%">
                    0
                </td>

                <td width="25%">
                    <b>NON DPHO :</b>
                </td>

                <td width="25%" class="right">
                    {{ number_format($grandTotal,0,',','.') }}
                </td>
            </tr>

            <tr>

                <td>
                    <b>Biaya Racik :</b>
                </td>

                <td>
                    {{ number_format($grandRacik,0,',','.') }}
                </td>

                <td>
                    <b>Biaya Resep :</b>
                </td>

                <td class="right">
                    {{ number_format($grandResep,0,',','.') }}
                </td>

            </tr>

            <tr>

                <td>
                    <b>Pembulatan :</b>
                </td>

                <td>
                    {{ number_format($grandBulat,0,',','.') }}
                </td>

                <td>
                    <b>RETUR :</b>
                </td>

                <td class="right">
                    ({{ number_format($grandRetur,0,',','.') }})
                </td>

            </tr>

            <tr>

                <td colspan="3" class="right">
                    <b>TOTAL :</b>
                </td>

                <td class="right">
                    <b>{{ number_format($totalAkhir,0,',','.') }}</b>
                </td>

            </tr>

        </table>

    </div>

    <div class="footer"> PRINTED : {{ now()->format('d/M/Y h:i:s A') }} </div>
</body>

</html>