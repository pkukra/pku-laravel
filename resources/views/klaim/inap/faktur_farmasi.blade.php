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

    <table width="100%" style="margin-bottom:10px;">
        <tr>
            <td width="55%" valign="top">

                <table width="100%">
                    <tr>
                        <td width="120">No. Med. Rec</td>
                        <td width="10">:</td>
                        <td>{{ $FMPASIEN_ID }}</td>
                    </tr>
                    <tr>
                        <td>Nama Pasien</td>
                        <td>:</td>
                        <td>{{ $FMNAMA_PESERTA }}</td>
                    </tr>
                    <tr>
                        <td>Alamat</td>
                        <td>:</td>
                        <td>{{ $ALAMAT }}</td>
                    </tr>
                    <tr>
                        <td>Tanggal Lahir</td>
                        <td>:</td>
                        <td>{{ \Carbon\Carbon::parse($TGL_LAHIR)->translatedFormat('d F Y') }}</td>
                    </tr>
                </table>
            </td>
            <td width="45%" valign="top">
                <table width="100%">
                    <tr>
                        <td width="120">No. Registrasi</td>
                        <td width="10">:</td>
                        <td>{{ $FMNOTRANSAKSI }}</td>
                    </tr>
                    <tr>
                        <td>Tanggal Masuk</td>
                        <td>:</td>
                        <td>{{ \Carbon\Carbon::parse($PRWITGL_MASUK)->translatedFormat('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td>Tanggal Pulang</td>
                        <td>:</td>
                        <td>{{ \Carbon\Carbon::parse($PRWITGL_KELUAR)->translatedFormat('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

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
        <tbody> @php $grandTotal = 0; $grandAdm = 0; @endphp @foreach($data as $resep) @php $subtotal = 0; $admTotal = 0; @endphp <tr class="group-header">
                <td> {{ $resep->FHFJBUKTI_ID }} </td>
                <td> {{ \Carbon\Carbon::parse($resep->FHFJDATE)->format('d-m-y') }} </td>
                <td colspan="8"> {{ $resep->FHFJDOKTERN }} </td>
            </tr> @foreach($resep->items as $item) @php $harga = (float) $item->FDFJHJUAL; $qty = (float) $item->FDFJQTY; $disc = (float) $item->FDFJDISC1; $discRp = (float) $item->FDFJDISC4; $adm = (float) $item->FDFJEMBALAGE; $total = (float) $item->FDFJTOTAL; $subtotal += $total; $admTotal += $adm; @endphp <tr>
                <td class="center"></td>
                <td></td>
                <td> {{ trim($item->FDFJBRGN) }} </td>
                <td class="center"> {{ trim($item->FDFJSATUAN) }} </td>
                <td class="right"> {{ number_format($harga,0,',','.') }} </td>
                <td class="center"> {{ rtrim(rtrim($qty,'0'),'.') }} </td>
                <td class="center"> {{ $disc }} </td>
                <td class="right"> {{ number_format($discRp,0,',','.') }} </td>
                <td class="right"> {{ number_format($adm,0,',','.') }} </td>
                <td class="right"> {{ number_format($total,0,',','.') }} </td>
            </tr> @endforeach @php $grandTotal += $subtotal; $grandAdm += $admTotal; @endphp <tr class="subtotal">
                <td colspan="8" class="right"> SUB TOTAL : </td>
                <td class="right"> {{ number_format($admTotal,0,',','.') }} </td>
                <td class="right"> {{ number_format($subtotal,0,',','.') }} </td>
            </tr> @endforeach </tbody>
    </table>
    <div class="grand-total">
        <table>
            <tr>
                <td width="25%"> <b>DPHO :</b> </td>
                <td width="25%"> 0 </td>
                <td width="25%"> <b>NON DPHO :</b> </td>
                <td width="25%" class="right"> {{ number_format($grandTotal,0,',','.') }} </td>
            </tr>
            <tr>
                <td> <b>ADM :</b> </td>
                <td> {{ number_format($grandAdm,0,',','.') }} </td>
                <td> <b>TOTAL :</b> </td>
                <td class="right"> <b>{{ number_format($grandTotal,0,',','.') }}</b> </td>
            </tr>
        </table>
    </div>
    <div class="footer"> PRINTED : {{ now()->format('d/M/Y h:i:s A') }} </div>
</body>

</html>