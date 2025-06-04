<?php
// No need to include exportTypeToExcel.php directly here,
// as it will be accessed via a separate HTTP request.

// Define your desired sheet type for the button click
// This is the value that will be passed to the export script.
$sheetTypeToExport = 'Fe'; // Or 'Copper', 'Aluminum', etc.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Metal Type Data</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f4f4f4;
        }
        button {
            padding: 15px 30px;
            font-size: 1.2em;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <button id="downloadBtn">Download <?php echo htmlspecialchars($sheetTypeToExport); ?> Data</button>

    <script>
        document.getElementById('downloadBtn').addEventListener('click', function() {
            // Get the sheet type from a PHP variable (passed to JavaScript)
            const type = '<?php echo htmlspecialchars($sheetTypeToExport); ?>';
            // Redirect to the export script with the 'type' as a GET parameter
            window.location.href = 'exportTypeToExcel.php?type=' + type;
        });
    </script>
</body>
</html>