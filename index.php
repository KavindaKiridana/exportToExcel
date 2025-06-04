<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <button id="downloadBtn">Download</button>
    <!-- Include the PhpSpreadsheet library -->
    <script>
        document.getElementById('downloadBtn').addEventListener('click', function() {
            window.location.href = 'exportToExcel.php'; // Adjust the path as necessary
        });
    </script>
</body>
</html>