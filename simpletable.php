<?php
require_once 'config.php';
$conn = getDBConnection();

//import the PhpSpreadsheet library
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$query = "SELECT addeddate FROM sheet_sizes_table";
$result_set = mysqli_query($conn, $query); // Execute the query

if (!$result_set) {
    die("Database query failed: " . mysqli_error($conn));
}

$spreadsheet = new Spreadsheet();
$activeWorksheet = $spreadsheet->getActiveSheet();
$activeWorksheet->setCellValue('A1', 'Added Date');

$rowCount = 2; // Start from row 2 to leave space for the header
while ($row = mysqli_fetch_assoc($result_set)) {
    $addedDate = $row['addeddate'];
    // Removed the echo statement as it's not needed for Excel export
    // echo $row['addeddate'] . "<br>"; 
    $activeWorksheet->setCellValue('A' . $rowCount, $addedDate);
    $rowCount++; // Increment row count for the next row
}

$writer = new Xlsx($spreadsheet);
$writer->save('sheet_sizes_table.xlsx');

// Close the database connection
mysqli_close($conn);

echo "Data successfully exported to sheet_sizes_table.xlsx";

?>