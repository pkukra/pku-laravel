<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
        }

        .page {
            width: 210mm;
            height: 297mm;
            page-break-after: always;
        }

        .page img {
            width: 210mm;
            height: 297mm;
            object-fit: contain;
        }
    </style>
</head>

<body>

    @foreach($images as $image)

    <img
        src="{{ public_path('storage/anestesi/' . $image['fjok'] . '.png') }}"
        style="width:210mm;height:297mm;">

    @if(!$loop->last)
    <div class="page-break"></div>
    @endif

    @endforeach

</body>

</html>