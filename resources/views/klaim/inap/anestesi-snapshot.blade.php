<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Anestesi</title>

    <style>
        @page {
            margin: 10px;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
        }

        .page {
            width: 100%;
        }

        .page img {
            width: 100%;
            height: auto;
            display: block;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>

    @foreach($images as $image)

    <div class="page">
        <img src="{{ asset('storage/anestesi/' . $image['fjok'] . '.png') }}">
    </div>

    @if(!$loop->last)
    <div class="page-break"></div>
    @endif

    @endforeach

</body>

</html>