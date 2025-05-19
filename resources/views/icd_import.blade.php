<!DOCTYPE html>
<html>
<head>
    <title>Import ICD-9CM</title>
</head>
<body>
    <h1>Import TSV File</h1>
    <form action="{{ route('icd.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" name="tsv_file" required>
        <button type="submit">Upload</button>
    </form>
</body>
</html>
