<?php
require_once 'config.php'; // Include the database configuration file
$conn = getDBConnection();

////////

// STEP 1: Define your desired sheet type
//$sheetType = 'testing';
$sheetType = 'Zn';

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

// Calculate total available sheets for this TYPE once, as it spans across the entire type
$totalSheetsQuery = "SELECT SUM(s.CurrentlyAvailableSheetCount) as total
                     FROM sheet_sizes_table s
                     INNER JOIN sheet_thickness_table th ON s.ThicknessId = th.ThicknessId
                     WHERE th.TypeId = ?";
$sumStmt = mysqli_prepare($conn, $totalSheetsQuery);
mysqli_stmt_bind_param($sumStmt, "i", $typeId);
mysqli_stmt_execute($sumStmt);
$sumResult = mysqli_stmt_get_result($sumStmt);
$sumRow = mysqli_fetch_assoc($sumResult);
$totalSheetsForType = $sumRow['total'] ?? 0; // Use a more descriptive variable name

// STEP 3: Get ALL thicknesses related to that type AND their associated sizes
// We'll fetch all data first to calculate rowspans correctly, then iterate for display.
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


// Start HTML table
echo "<table border='1' cellpadding='8' cellspacing='0'>";
echo "<thead>"; // Use thead for table headers
echo "<tr>
        <th>Type</th>
        <th>Type's Description</th>
        <th>No of Available Sheets</th>
        <th>Available Thickness</th>
        <th>Sizes</th>
        <th>No. of Sheets Available</th>
      </tr>";
echo "</thead>";
echo "<tbody>"; // Use tbody for table body

// Flags and counters for rowspans
$firstTypeRow = true; // Flag for the first row of the entire type
$processedThicknesses = []; // To keep track of thicknesses for which rowspan has been applied

// Outer loop iterates through the collected data
foreach ($data as $rowIndex => $row) {
    echo "<tr>";

    // Column 1: Type (rowspan for the entire type)
    if ($firstTypeRow) {
        // Calculate the total number of rows that will be displayed for this type
        // This is simply the count of all size entries for this type
        $totalRowsForType = count($data);
        echo "<td rowspan='{$totalRowsForType}'>{$sheetType}</td>";
        $firstTypeRow = false;
    }

    // Column 2: Type's Description (rowspan for the entire type)
    // This also only appears on the first row of the type
    if ($rowIndex === 0) { // Since $firstTypeRow handles the first row, this is equivalent
        $totalRowsForType = count($data); // Re-calculate or use the already calculated value
        echo "<td rowspan='{$totalRowsForType}'>{$typeDescription}</td>";
    }

    // Column 3: No of Available Sheets (rowspan for the entire type)
    // This also only appears on the first row of the type
    if ($rowIndex === 0) { // Since $firstTypeRow handles the first row, this is equivalent
        $totalRowsForType = count($data); // Re-calculate or use the already calculated value
        echo "<td rowspan='{$totalRowsForType}'>{$totalSheetsForType}</td>";
    }

    // Column 4: Available Thickness (rowspan for its associated sizes)
    $currentThickness = $row['Thickness'];
    if (!isset($processedThicknesses[$currentThickness])) {
        // This is the first time we encounter this thickness in the loop
        $thicknessSpan = $thicknessRowSpans[$currentThickness];
        echo "<td rowspan='{$thicknessSpan}'>{$currentThickness}</td>";
        $processedThicknesses[$currentThickness] = true; // Mark as processed
    }

    // Column 5: Sizes
    echo "<td>{$row['Size']}</td>";

    // Column 6: No. of Sheets Available
    echo "<td>{$row['CurrentlyAvailableSheetCount']}</td>";

    echo "</tr>";
}

echo "</tbody>";
echo "</table>";

// Close database connection
mysqli_close($conn);
?>