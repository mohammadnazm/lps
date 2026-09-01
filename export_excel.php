<?php
// Secure student Excel export. General Admin can choose a school with ?school_scope=CODE;
// school admins are always restricted to their own school.
ob_start();
// Give large student lists enough room/time to export in one request.
@ini_set('memory_limit', '256M');
@set_time_limit(120);
require_once __DIR__ . '/db_connection.php';
require __DIR__ . '/excel_bootstrap.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

if (!isset($_SESSION['loginadmin']) || $_SESSION['loginadmin'] !== true) {
    ob_end_clean();
    header('Location: logout_session.php');
    exit;
}


$requestedSchool = trim((string)($_GET['school_scope'] ?? $_POST['school_scope'] ?? ''));
if (canSeeAllSchools()) {
    $targetSchool = $requestedSchool === '' ? 'ALL' : resolveTargetSchool($requestedSchool);
} else {
    $targetSchool = currentSchoolScope();
}

$headers = [
    'ID','Name','Middle Name','Birth Date','Blood Group','Nation','Religion','Gender',
    'Brothers','Sisters','Birth Order','Home Location','Average Mark','Last School','First Year',
    'Father Tell','Mother Tell','Student Tell','Price','Citizenship','ID Type','ID Number','ID File',
    'Class','Group','Faculty','Type','Date','Status','Size','Image','Student Note','School Code'
];

$columns = [
    'id','st_name','st_m_name','st_bd_date','st_b_group','st_nation','st_religion','st_gender',
    'n_bro','n_sis','st_bd_order','st_home_loc','st_avg_mark','last_s_name','st_f_year',
    'f_tell','m_tell','st_tell','st_price','st_citiiz','type_of_id','st_id_number','st_id_file',
    'st_class','st_group','st_faculty','st_type','st_date','st_statue','st_size','st_img','st_note','school_scope'
];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Students');

foreach ($headers as $i => $header) {
    $sheet->setCellValue([$i + 1, 1], $header);
}
$sheet->getStyle('A1:AG1')->getFont()->setBold(true);
$sheet->getStyle('A1:AG1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->freezePane('A2');
$sheet->setAutoFilter('A1:AG1');

$where = schoolSql('school_scope');
if ($targetSchool !== 'ALL') {
    $safe = $conn->real_escape_string($targetSchool);
    $where = "school_scope='$safe'";
}

$sql = "SELECT " . implode(',', array_map(fn($c) => "`$c`", $columns)) . " FROM students WHERE $where ORDER BY id ASC";
$result = $conn->query($sql);
if (!$result) {
    throw new RuntimeException('Could not read students: ' . $conn->error);
}

$rowNum = 2;
while ($row = $result->fetch_assoc()) {
    foreach ($columns as $i => $column) {
        // Keep phone numbers, ID numbers and other identifiers as text so Excel does not
        // remove leading zeroes or convert them to scientific notation.
        $value = $row[$column] ?? '';
        $sheet->setCellValueExplicit([$i + 1, $rowNum], (string)$value, DataType::TYPE_STRING);
    }
    $rowNum++;
}

// Useful widths without making the workbook enormous.
$widths = [8,24,24,14,14,18,18,12,10,10,12,28,14,22,14,18,18,18,14,18,14,20,24,16,12,18,14,18,14,12,12,24,30];
foreach ($widths as $i => $width) $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($width);

$schoolName = $targetSchool === 'ALL' ? 'ALL_SCHOOLS' : preg_replace('/[^A-Za-z0-9_-]+/', '_', schoolName($targetSchool));
$filename = 'students_' . $schoolName . '_' . date('Y-m-d_H-i-s') . '.xlsx';

$conn->close();
ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
