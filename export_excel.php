<?php
ob_start();
session_start();
include "db_connection.php";
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$headers = [
"Name","Middle Name","Birth Date","Blood Group","Nation",
"Religion","Gender","Brothers","Sisters","Birth Order",
"Home Location","Average Mark","Last School","First Year",
"Father Tell","Mother Tell","Student Tell","Price",
"Citizenship","ID Type","ID Number","ID File","Class",
"Group","Faculty","Type","Date","Status","Size","Image"
];

$col='A';
foreach($headers as $h){
    $sheet->setCellValue($col.'1',$h);
    $col++;
}

$result = $conn->query("SELECT * FROM students");

$rowNum = 2;

while($row=$result->fetch_assoc()){

    $sheet->fromArray([
        $row['st_name'],
        $row['st_m_name'],
        $row['st_bd_date'],
        $row['st_b_group'],
        $row['st_nation'],
        $row['st_religion'],
        $row['st_gender'],
        $row['n_bro'],
        $row['n_sis'],
        $row['st_bd_order'],
        $row['st_home_loc'],
        $row['st_avg_mark'],
        $row['last_s_name'],
        $row['st_f_year'],
        $row['f_tell'],
        $row['m_tell'],
        $row['st_tell'],
        $row['st_price'],
        $row['st_citiiz'],
        $row['type_of_id'],
        $row['st_id_number'],
        $row['st_id_file'],
        $row['st_class'],
        $row['st_group'],
        $row['st_faculty'],
        $row['st_type'],
        $row['st_date'],
        $row['st_statue'],
        $row['st_size'],
        $row['st_img']
    ],NULL,'A'.$rowNum);

    $rowNum++;
}

ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="students.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
