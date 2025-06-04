<?php

//import the PhpSpreadsheet library
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();                       //1->create s spreadsheet
$activeWorksheet = $spreadsheet->getActiveSheet();      //2->create a sheet insite the spreadsheet which u created before
$activeWorksheet->setCellValue('A1', 'Hello World !');  //3->give the cell reference to the sheet and the assign the value to it                                        

$writer = new Xlsx($spreadsheet);   //create writer object 
$writer->save('hello world.xlsx');  //save your sheet using writer object which you created before ,with a name