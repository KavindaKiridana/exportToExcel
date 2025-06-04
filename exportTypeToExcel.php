<?php

require_once 'config.php'; // Include the database configuration file
$conn = getDBConnection();
// Ensure vendor/autoload.php is correctly pointed to your PhpSpreadsheet installation
require 'vendor/autoload.php';
// Import PhpSpreadsheet classes
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate; // To work with cell coordinates easily
use PhpOffice\PhpSpreadsheet\Style\Alignment; // For alignment constants


// Get the sheet type from the GET request
// It's crucial to sanitize this input to prevent SQL injection or other issues.
$sheetType = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'Zn'; 

// STEP 1: Define your desired sheet type (same as your HTML display logic)
//$sheetType = 'Zn'; // Or whatever type you want to export

// STEP 2: Get the type's info
$typeQuery = "SELECT * FROM sheet_type_table WHERE Name = ?";
$stmt = mysqli_prepare($conn, $typeQuery);
mysqli_stmt_bind_param($stmt, "s", $sheetType);
mysqli_stmt_execute($stmt);
$typeResult = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($typeResult) == 0) {
    die("No such sheet type found.");
}

$typeRow = mysqli_fetch_assoc($typeResult);
$typeId = $typeRow['TypeId'];
$typeDescription = $typeRow['Description'];

// Calculate total available sheets for this TYPE
$totalSheetsQuery = "SELECT SUM(s.CurrentlyAvailableSheetCount) as total
                     FROM sheet_sizes_table s
                     INNER JOIN sheet_thickness_table th ON s.ThicknessId = th.ThicknessId
                     WHERE th.TypeId = ?";
$sumStmt = mysqli_prepare($conn, $totalSheetsQuery);
mysqli_stmt_bind_param($sumStmt, "i", $typeId);
mysqli_stmt_execute($sumStmt);
$sumResult = mysqli_stmt_get_result($sumStmt);
$sumRow = mysqli_fetch_assoc($sumResult);
$totalSheetsForType = $sumRow['total'] ?? 0;

// STEP 3: Get ALL thicknesses related to that type AND their associated sizes
$allDataQuery = "SELECT
                    th.ThicknessId,
                    th.Thickness,
                    s.Size,
                    s.CurrentlyAvailableSheetCount
                 FROM
                    sheet_thickness_table th
                 INNER JOIN
                    sheet_sizes_table s ON th.ThicknessId = s.ThicknessId
                 WHERE
                    th.TypeId = ?
                 ORDER BY
                    th.Thickness, s.Size"; // Order by thickness and then size for consistent output

$stmt = mysqli_prepare($conn, $allDataQuery);
mysqli_stmt_bind_param($stmt, "i", $typeId);
mysqli_stmt_execute($stmt);
$allDataResult = mysqli_stmt_get_result($stmt);

// Store all fetched data in an array for easier processing and rowspan calculation
$data = [];
while ($row = mysqli_fetch_assoc($allDataResult)) {
    $data[] = $row;
}

// Calculate rowspans for Thickness based on how many sizes each thickness has
$thicknessRowSpans = [];
foreach ($data as $row) {
    $thickness = $row['Thickness'];
    if (!isset($thicknessRowSpans[$thickness])) {
        $thicknessRowSpans[$thickness] = 0;
    }
    $thicknessRowSpans[$thickness]++;
}

// --- PHPSPREADSHEET EXPORT LOGIC STARTS HERE ---

// Create a new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Complex Sheet Data'); // Set the sheet title

// Define the header row
$headers = [
    'Type',
    'Type\'s Description',
    'No of Available Sheets',
    'Available Thickness',
    'Sizes',
    'No. of Sheets Available'
];

// Write headers to the first row of the Excel sheet
$sheet->fromArray($headers, NULL, 'A1'); // NULL means no value to convert to cell

// Initialize current row for data entry, starting after headers
$currentRow = 2;
$firstTypeRow = true; // Flag for the first row of the entire type
$processedThicknesses = []; // To keep track of thicknesses for which merging has been applied

// Iterate through the collected data to populate the Excel sheet
foreach ($data as $rowIndex => $row) {
    // Column 1: Type (merged for the entire type)
    if ($firstTypeRow) {
        $totalRowsForType = count($data);
        $startCell = 'A' . $currentRow;
        $endCell = 'A' . ($currentRow + $totalRowsForType - 1); // -1 because current row is included
        $sheet->setCellValue($startCell, $sheetType);
        $sheet->mergeCells("{$startCell}:{$endCell}"); // Merge cells
        $firstTypeRow = false;
    }

    // Column 2: Type's Description (merged for the entire type)
    if ($rowIndex === 0) {
        $totalRowsForType = count($data);
        $startCell = 'B' . $currentRow;
        $endCell = 'B' . ($currentRow + $totalRowsForType - 1);
        $sheet->setCellValue($startCell, $typeDescription);
        $sheet->mergeCells("{$startCell}:{$endCell}");
    }

    // Column 3: No of Available Sheets (merged for the entire type)
    if ($rowIndex === 0) {
        $totalRowsForType = count($data);
        $startCell = 'C' . $currentRow;
        $endCell = 'C' . ($currentRow + $totalRowsForType - 1);
        $sheet->setCellValue($startCell, $totalSheetsForType);
        $sheet->mergeCells("{$startCell}:{$endCell}");
    }

    // Column 4: Available Thickness (merged for its associated sizes)
    $currentThickness = $row['Thickness'];
    if (!isset($processedThicknesses[$currentThickness])) {
        // This is the first time we encounter this thickness in the loop
        $thicknessSpan = $thicknessRowSpans[$currentThickness];
        $startCell = 'D' . $currentRow;
        $endCell = 'D' . ($currentRow + $thicknessSpan - 1);
        $sheet->setCellValue($startCell, $currentThickness);
        $sheet->mergeCells("{$startCell}:{$endCell}");
        $processedThicknesses[$currentThickness] = true; // Mark as processed
    }

    // Column 5: Sizes
    $sheet->setCellValue('E' . $currentRow, $row['Size']);

    // Column 6: No. of Sheets Available
    $sheet->setCellValue('F' . $currentRow, $row['CurrentlyAvailableSheetCount']);

    // Move to the next row for the next data entry
    $currentRow++;
} // End of the foreach loop that populates data

// --- Apply styling for middle alignment and center justification ---

// Define the style for middle alignment and center justification
$styleArray = [
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER, // Use the imported constant
        'horizontal' => Alignment::HORIZONTAL_CENTER, // Use the imported constant
    ],
];

// Get the highest column and row that has content dynamically
$highestRow = $sheet->getHighestRow();
$highestColumn = $sheet->getHighestColumn();

// Apply the style to the entire used range of the sheet (from A1 to the last populated cell)
// This will apply to both merged and non-merged cells within this range.
$sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray($styleArray);

// --- Setting Headers for Download ---
$fileName = 'complex_sheet_data.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0'); // No cache

// Create a writer object and save the spreadsheet to output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output'); // Save directly to output

// Close database connection
mysqli_close($conn);

// Exit to prevent any further HTML output
exit();


?>

