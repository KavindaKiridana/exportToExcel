<?php
require_once 'config.php'; // Include the database configuration file
$conn=getDBConnection();

////////

// STEP 1: Define your desired sheet type
$sheetType = 'testing';

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

// STEP 3: Get thickness related to that type
$thicknessQuery = "SELECT * FROM sheet_thickness_table WHERE TypeId = ?";
$stmt = mysqli_prepare($conn, $thicknessQuery);
mysqli_stmt_bind_param($stmt, "i", $typeId);
mysqli_stmt_execute($stmt);
$thicknessResult = mysqli_stmt_get_result($stmt);

// Start HTML table
echo "<table border='1' cellpadding='8' cellspacing='0'>";
echo "<tr>
        <th>Type</th>
        <th>Type's Description</th>
        <th>No of Available Sheets</th>
        <th>Available Thickness</th>
        <th>Sizes</th>
        <th>No. of Sheets Available</th>
      </tr>";

// Outer loop for each thickness
while ($thicknessRow = mysqli_fetch_assoc($thicknessResult)) {
    $thicknessId = $thicknessRow['ThicknessId'];
    $thicknessValue = $thicknessRow['Thickness'];

    // STEP 4: Get sizes related to this thickness
    $sizeQuery = "SELECT * FROM sheet_sizes_table WHERE ThicknessId = ?";
    $sizeStmt = mysqli_prepare($conn, $sizeQuery);
    mysqli_stmt_bind_param($sizeStmt, "i", $thicknessId);
    mysqli_stmt_execute($sizeStmt);
    $sizeResult = mysqli_stmt_get_result($sizeStmt);

    $firstRow = true;

    while ($sizeRow = mysqli_fetch_assoc($sizeResult)) {
        echo "<tr>";

        if ($firstRow) {
            echo "<td rowspan='" . mysqli_num_rows($sizeResult) . "'>{$sheetType}</td>";
            echo "<td rowspan='" . mysqli_num_rows($sizeResult) . "'>{$typeDescription}</td>";

            // Count total available sheets for this type
            $totalSheetsQuery = "SELECT SUM(CurrentlyAvailableSheetCount) as total FROM sheet_sizes_table 
                                 WHERE ThicknessId IN (SELECT ThicknessId FROM sheet_thickness_table WHERE TypeId = ?)";
            $sumStmt = mysqli_prepare($conn, $totalSheetsQuery);
            mysqli_stmt_bind_param($sumStmt, "i", $typeId);
            mysqli_stmt_execute($sumStmt);
            $sumResult = mysqli_stmt_get_result($sumStmt);
            $sumRow = mysqli_fetch_assoc($sumResult);
            $totalSheets = $sumRow['total'] ?? 0;

            echo "<td rowspan='" . mysqli_num_rows($sizeResult) . "'>{$totalSheets}</td>";
            $firstRow = false;
        }

        echo "<td>{$thicknessValue}</td>";
        echo "<td>{$sizeRow['Size']}</td>";
        echo "<td>{$sizeRow['CurrentlyAvailableSheetCount']}</td>";
        echo "</tr>";
    }
}

echo "</table>";

// mysqli_close($connection);

////////

//import the PhpSpreadsheet library
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();                       //1->create s spreadsheet
$activeWorksheet = $spreadsheet->getActiveSheet();      //2->create a sheet insite the spreadsheet which u created before
$activeWorksheet->setCellValue('A1', 'Hello World !');  //3->give the cell reference to the sheet and the assign the value to it                                        

$writer = new Xlsx($spreadsheet);   //create writer object 
$writer->save('hello world.xlsx');  //save your sheet using writer object which you created before ,with a name