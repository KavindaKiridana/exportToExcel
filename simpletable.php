<?php
require_once 'config.php';
$conn = getDBConnection();

$query = "SELECT addeddate FROM sheet_sizes_table";
$result_set=mysqli_query($conn,$query); //excute the query

$spreadsheet = new Spreadsheet();                       //1->create s spreadsheet
$activeWorksheet = $spreadsheet->getActiveSheet();  //2->create a sheet insite the spreadsheet which u created before
//display the result using a while loop
while ($row = mysqli_fetch_assoc($result_set)) {
    $addedDate = $row['addeddate'];
    echo $row['addeddate'] . "<br>";
}