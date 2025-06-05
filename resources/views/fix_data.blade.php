<!DOCTYPE html>
<html>

<head>
    <title>Upload CSV</title>
</head>

<body>
    <h2>Upload CSV File</h2>

    @if (session('success'))
    <p style="color: green">{{ session('success') }}</p>
    @endif

    <form action="{{ route('fix-data.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" name="csv_file" accept=".csv" required>
        <button type="submit">Upload</button>
    </form>
</body>

</html>