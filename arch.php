<?php
require_once __DIR__ . '/db_connection.php';
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

if (!isset($_SESSION['loginadmin']) || $_SESSION['loginadmin'] !== true) {
    header('Location: logout_session.php');
    exit;
}

$messages = [];
$errors = [];
$deleteMessages = [];
$deleteErrors = [];
$MAX_FILE_SIZE = 15 * 1024 * 1024; // 15 MB

// The export format (export_excel.php) and import format below are intentionally identical.
$headers = [
    'ID','Name','Middle Name','Birth Date','Blood Group','Nation','Religion','Gender',
    'Brothers','Sisters','Birth Order','Home Location','Average Mark','Last School','First Year',
    'Father Tell','Mother Tell','Student Tell','Price','Citizenship','ID Type','ID Number','ID File',
    'Class','Group','Faculty','Type','Date','Status','Size','Image','Student Note','School Code'
];

$fields = [
    'st_name','st_m_name','st_bd_date','st_b_group','st_nation','st_religion','st_gender','n_bro','n_sis',
    'st_bd_order','st_home_loc','st_avg_mark','last_s_name','st_f_year','f_tell','m_tell','st_tell','st_price',
    'st_citiiz','type_of_id','st_id_number','st_id_file','st_class','st_group','st_faculty','st_type','st_date',
    'st_statue','st_size','st_img','st_note'
];

function cleanExcelValue($value): string {
    if ($value === null) return '';
    if (is_bool($value)) return $value ? '1' : '0';
    return trim((string)$value);
}

function excelDateToSql($value): string {
    $value = cleanExcelValue($value);
    if ($value === '') return '';
    try {
        if (is_numeric($value) && (float)$value > 20000) {
            return ExcelDate::excelToDateTimeObject((float)$value)->format('Y-m-d');
        }
        $ts = strtotime($value);
        return $ts !== false ? date('Y-m-d', $ts) : $value;
    } catch (Throwable $e) {
        return $value;
    }
}

function normalizeHeader($value): string {
    $value = preg_replace('/\x{FEFF}/u', '', (string)$value);
    $value = strtolower(trim($value));
    return preg_replace('/[^a-z0-9]+/', '', $value);
}

function cellValue($row, $index): string {
    return array_key_exists($index, $row) ? cleanExcelValue($row[$index]) : '';
}

// Give large workbooks (hundreds of students) enough room to import in one request.
@ini_set('memory_limit', '256M');
@set_time_limit(120);

if (isset($_POST['import_excel'])) {
    try {
        if (!isset($_FILES['excel_file'])) {
            throw new RuntimeException('Please select a valid Excel file.');
        }
        if ($_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'The file is larger than this server\'s upload_max_filesize setting. Ask your host to raise it, or split the file.',
                UPLOAD_ERR_FORM_SIZE => 'The file is larger than allowed by the upload form.',
                UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded. Please try again.',
                UPLOAD_ERR_NO_FILE => 'Please select a file to upload.',
                UPLOAD_ERR_NO_TMP_DIR => 'The server has no temporary folder configured for uploads. Contact your host.',
                UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded file to disk. Contact your host.',
                UPLOAD_ERR_EXTENSION => 'A server extension blocked this upload.',
            ];
            $code = $_FILES['excel_file']['error'];
            throw new RuntimeException($uploadErrors[$code] ?? ('Upload failed (error code ' . $code . ').'));
        }
        if ((int)$_FILES['excel_file']['size'] <= 0 || (int)$_FILES['excel_file']['size'] > $MAX_FILE_SIZE) {
            throw new RuntimeException('The Excel file must be larger than 0 bytes and no larger than 15 MB.');
        }

        $extension = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['xlsx','xls','csv'], true)) {
            throw new RuntimeException('Only .xlsx, .xls and .csv files are supported.');
        }

        // Never trust the school sent by a normal school admin. resolveTargetSchool()
        // forces them back to their session school.
        $targetSchool = resolveTargetSchool($_POST['school_scope'] ?? null);
        if ($targetSchool === 'ALL') {
            // General Admin must choose a school for new/updated records. This avoids
            // accidentally putting rows into an undefined school.
            throw new RuntimeException('Please select the school that this Excel file belongs to.');
        }

        try {
            $spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);
        } catch (Throwable $readError) {
            throw new RuntimeException('This file could not be read as ' . strtoupper($extension) . '. Make sure it is a real Excel/CSV file and is not password-protected or corrupted.');
        }
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);
        if (count($rows) < 2) throw new RuntimeException('The Excel file contains no student rows.');

        // Validate the first row so a random Excel file cannot be imported into students.
        $actualHeaders = array_map('normalizeHeader', $rows[0]);
        $expectedHeaders = array_map('normalizeHeader', $headers);
        $headerCount = max(count($actualHeaders), count($expectedHeaders));
        for ($i = 0; $i < $headerCount; $i++) {
            if (($actualHeaders[$i] ?? '') !== ($expectedHeaders[$i] ?? '')) {
                throw new RuntimeException('Invalid Excel template. Please use the Excel exported by this system or download a fresh template. Column ' . ($i + 1) . ' should be "' . ($headers[$i] ?? 'unknown') . '".');
            }
        }

        // Check that the target school is active.
        $school = getSchoolByCode($targetSchool);
        if (!$school || $school['status'] !== 'active') throw new RuntimeException('The selected school is not active.');

        $insertSql = "INSERT INTO students (`" . implode('`,`', $fields) . "`, `school_scope`) VALUES (" . rtrim(str_repeat('?,', count($fields) + 1), ',') . ")";
        $insertStmt = $conn->prepare($insertSql);
        if (!$insertStmt) throw new RuntimeException('Could not prepare student import.');

        $updateSql = "UPDATE students SET " . implode(',', array_map(fn($f) => "`$f`=?", $fields)) . " WHERE id=? AND school_scope=? LIMIT 1";
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) throw new RuntimeException('Could not prepare student update.');

        $conn->begin_transaction();
        $inserted = 0; $updated = 0; $skipped = 0; $rowErrors = [];

        for ($i = 1; $i < count($rows); $i++) {
            $excelRow = $i + 1;
            $row = $rows[$i];
            $isBlank = true;
            foreach ($row as $v) if (cleanExcelValue($v) !== '') { $isBlank = false; break; }
            if ($isBlank) continue;

            // Columns: ID + 31 student fields + School Code.
            $id = (int)cellValue($row, 0);
            $values = [];
            foreach ($fields as $index => $field) {
                $value = cellValue($row, $index + 1);
                if ($field === 'st_bd_date' || $field === 'st_date') $value = excelDateToSql($value);
                $values[] = $value;
            }

            $excelSchoolCode = cellValue($row, 32);
            if ($excelSchoolCode !== '' && strtoupper($excelSchoolCode) !== strtoupper($targetSchool)) {
                $skipped++;
                if (count($rowErrors) < 50) $rowErrors[] = "Row $excelRow: School Code \"$excelSchoolCode\" does not match the selected school.";
                continue;
            }

            $class = $values[22];
            if ($class === '' || !schoolAllowsClass($targetSchool, $class)) {
                $skipped++;
                if (count($rowErrors) < 50) $rowErrors[] = "Row $excelRow: class/grade \"$class\" does not belong to " . schoolName($targetSchool) . '.';
                continue;
            }
            if ($values[0] === '') {
                $skipped++;
                if (count($rowErrors) < 50) $rowErrors[] = "Row $excelRow: student name is required.";
                continue;
            }

            // ID is optional. Blank ID = insert. Existing ID = update, but only if that
            // record belongs to the selected school. Cross-school edits are rejected.
            if ($id > 0) {
                $check = $conn->prepare('SELECT id, school_scope FROM students WHERE id=? LIMIT 1');
                $check->bind_param('i', $id);
                $check->execute();
                $existing = $check->get_result()->fetch_assoc();
                $check->close();
                if (!$existing) {
                    $skipped++;
                    if (count($rowErrors) < 50) $rowErrors[] = "Row $excelRow: student ID $id does not exist; leave ID blank to create a new student.";
                    continue;
                }
                if (strtoupper($existing['school_scope']) !== strtoupper($targetSchool)) {
                    $skipped++;
                    if (count($rowErrors) < 50) $rowErrors[] = "Row $excelRow: student ID $id belongs to another school and was not changed.";
                    continue;
                }

                $types = str_repeat('s', count($fields)) . 'is';
                $params = array_merge($values, [$id, $targetSchool]);
                $updateStmt->bind_param($types, ...$params);
                if (!$updateStmt->execute()) throw new RuntimeException("Row $excelRow: update failed: " . $updateStmt->error);
                $updated++;
            } else {
                $types = str_repeat('s', count($fields) + 1);
                $params = array_merge($values, [$targetSchool]);
                $insertStmt->bind_param($types, ...$params);
                if (!$insertStmt->execute()) throw new RuntimeException("Row $excelRow: insert failed: " . $insertStmt->error);
                $inserted++;
            }
        }

        $conn->commit();
        $insertStmt->close();
        $updateStmt->close();
        $messages[] = "Import completed successfully: $inserted new student(s), $updated updated, $skipped skipped.";
        if ($rowErrors) $errors = $rowErrors;
    } catch (Throwable $e) {
        // Rollback is safe even if no transaction is active.
        $conn->rollback();
        $errors[] = 'Import failed: ' . $e->getMessage();
    }
}

// ================= Delete ALL students for one school (database rows only) =================
// This never touches st_image/ or id_data/ — only the database rows for students,
// attendance, and marks are removed. Deliberately requires the admin to type the exact
// school code as confirmation; there is no "delete everything, every school" option.
if (isset($_POST['delete_all_students'])) {
    $deleteTargetSchool = canSeeAllSchools()
        ? strtoupper(trim((string)($_POST['delete_school_scope'] ?? '')))
        : currentSchoolScope();
    $school = $deleteTargetSchool !== '' ? getSchoolByCode($deleteTargetSchool) : null;
    $typedConfirm = strtoupper(trim((string)($_POST['confirm_school_code'] ?? '')));

    if (!$school) {
        $deleteErrors[] = 'Select which school to delete students from.';
    } elseif ($typedConfirm !== $school['school_code']) {
        $deleteErrors[] = 'The school code you typed did not match "' . htmlspecialchars($school['school_code']) . '". Nothing was deleted.';
    } else {
        $result = deleteAllStudentsForSchool($school['school_code']);
        if ($result['ok']) {
            $deleteMessages[] = number_format($result['deleted']) . ' student record(s) deleted from ' . htmlspecialchars($school['school_name']) . '. Uploaded images and files were left untouched.';
        } else {
            $deleteErrors[] = 'Delete failed: ' . htmlspecialchars((string)$result['error']);
        }
    }
}

include "admin_header.php";
?>
<style>
    /* ================= GENERAL ================= */
    html,
    body {
        height: 100%;
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
        background-color: #0f172a;
        color: #e2e8f0;
    }

    /* ================= SEARCH FORM ================= */
    .search-card {
        background: #1e293b;
        border-radius: 12px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
    }

    .search-card input.form-control,
    .search-card select.form-control {
        border-radius: 8px;
        padding: 8px;
        background-color: #0f172a;
        border: 1px solid #334155;
        color: #e2e8f0;
    }

    .search-card ::placeholder {
        color: #cbd5e1;
        opacity: 1;
    }

    .search-card select option {
        background-color: #1e293b;
        color: #e2e8f0;
    }

    .search-card input:focus,
    .search-card select:focus {
        border-color: #2563eb;
        background-color: rgba(37, 99, 235, 0.15);
        outline: none;
        box-shadow: 0 0 0 0.15rem rgba(37, 99, 235, 0.25);
    }

    .btn-outline-success {
        color: #22c55e;
        border-color: #22c55e;
    }

    .btn-outline-success:hover {
        background-color: #22c55e;
        color: #0f172a;
    }

    /* ================= TABLE ================= */
    .table {
        color: #f1f5f9 !important;
    }

    .table thead th {
        background: #475569 !important;
        color: #ffffff !important;
        text-align: center;
        border: none !important;
    }

    .table tbody tr {
        background: #1e293b;
        transition: 0.2s;
        text-align: center;
    }

    .table tbody tr:hover {
        background: #334155;
    }

    .table td {
        vertical-align: middle !important;
    }


    .scrollableBox {
        overflow-y: auto;
        max-height: 65vh;
    }

    .scrollableBox table {
        width: 100%;
        border-collapse: collapse;
        text-align: center;
    }

    .scrollableBox th,
    .scrollableBox td {
        padding: 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .scrollableBox th {
        background-color: #334155;
        color: #f1f5f9;
        font-weight: 600;
    }

    .scrollableBox tr:nth-child(even) {
        background-color: rgba(255, 255, 255, 0.02);
    }

    .scrollableBox tr:hover {
        background-color: rgba(56, 189, 248, 0.1);
    }

    .scrollableBox button {
        font-size: 0.75rem;
        border-radius: 6px;
    }

    .excel-box{background:#1e293b;padding:28px;border-radius:14px;margin:25px 0;color:#e2e8f0}
    .excel-box h2{color:#fff;font-size:1.3rem}.excel-box .form-control{background:#0f172a!important;color:#fff!important;border:1px solid #475569!important}
    .excel-actions{display:flex;gap:12px;flex-wrap:wrap;align-items:center}.excel-actions a,.excel-actions button{border-radius:8px;padding:11px 18px;text-decoration:none;border:0}
    .notice{padding:12px 15px;border-radius:8px;margin:10px 0}.ok{background:#14532d;color:#dcfce7}.bad{background:#7f1d1d;color:#fee2e2}.hint{background:#172554;color:#dbeafe}
    .small{font-size:13px;color:#cbd5e1}.error-list{max-height:240px;overflow:auto;margin:8px 0;padding-left:25px}
    .danger-box{border:2px solid #7f1d1d;background:#2a1414}
    .danger-box h2{color:#fca5a5}
    #delete-all-btn:disabled{opacity:.5;cursor:not-allowed}
</style>

<div class="excel-box">
    <h2>Download Students (Excel)</h2>
    <p class="small">Downloads every student in your school as an .xlsx file. Re-use this exact file as the template for importing below.</p>
    <form action="export_excel.php<?= canSeeAllSchools() ? '' : '?school_scope=' . urlencode(currentSchoolScope()) ?>" method="POST">
        <button type="submit" style="
            padding:15px 30px;
            font-size:18px;
            background:#22c55e;
            color:white;
            border:none;
            border-radius:8px;"
            onclick="return confirm('Download the student list as Excel?');">
            Download Excel
        </button>
    </form>
</div>

<div class="excel-box">
    <h2>Import Students (Excel)</h2>
    <p class="small">Use the downloaded workbook above as your master template. Edit it and import it back here. Existing IDs are updated; blank IDs create new students.</p>

    <?php foreach ($messages as $m): ?><div class="notice ok"><?= htmlspecialchars($m) ?></div><?php endforeach; ?>
    <?php foreach ($errors as $e): ?><div class="notice bad"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>

    <?php if (canSeeAllSchools()): ?>
        <label><b>School for this import</b></label>
        <select name="school_scope" form="excel-import-form" required class="form-control" style="max-width:450px;margin:8px 0 15px">
            <option value="" disabled selected>Select School</option>
            <?php foreach (getSchools(true) as $school): ?>
                <option value="<?= htmlspecialchars($school['school_code'], ENT_QUOTES) ?>"><?= htmlspecialchars($school['school_name']) ?></option>
            <?php endforeach; ?>
        </select>
    <?php else: ?>
        <div class="notice hint">School: <b><?= htmlspecialchars(schoolName(currentSchoolScope())) ?></b></div>
        <input type="hidden" name="school_scope" value="<?= htmlspecialchars(currentSchoolScope(), ENT_QUOTES) ?>" form="excel-import-form">
    <?php endif; ?>

    <form id="excel-import-form" method="POST" enctype="multipart/form-data">
        <input class="form-control" type="file" name="excel_file" accept=".xlsx,.xls,.csv" required>
        <div class="excel-actions" style="margin-top:15px">
            <button type="submit" name="import_excel" class="btn btn-success">Upload & Import Excel</button>
        </div>
    </form>

    <hr>
    <h5>How it works</h5>
    <ul class="small">
        <li><b>ID filled:</b> the existing student in that school is updated.</li>
        <li><b>ID blank:</b> a new student is created.</li>
        <li><b>School Code in Excel:</b> never overrides the selected school; this prevents cross-school mistakes.</li>
        <li>Invalid grades, wrong templates, missing names, and cross-school IDs are rejected without partially importing the file.</li>
        <li>Phone numbers and ID numbers are exported as text to preserve leading zeroes.</li>
    </ul>
</div>

<div class="excel-box danger-box">
    <h2>⚠ Danger Zone — Delete All Students</h2>
    <p class="small">Permanently deletes every student record (and their attendance/mark records) for one school. <b>Uploaded photos and ID files are NOT deleted</b> — only the database rows. This cannot be undone. Download an Excel backup above first.</p>

    <?php foreach ($deleteMessages as $m): ?><div class="notice ok"><?= $m ?></div><?php endforeach; ?>
    <?php foreach ($deleteErrors as $e): ?><div class="notice bad"><?= $e ?></div><?php endforeach; ?>

    <form method="POST" id="delete-all-form" onsubmit="return confirm('This will permanently delete ALL student records for this school. This cannot be undone. Continue?');">
        <?php if (canSeeAllSchools()): ?>
            <label><b>School to delete students from</b></label>
            <select name="delete_school_scope" id="delete-school-select" required class="form-control" style="max-width:450px;margin:8px 0 15px">
                <option value="" disabled selected>Select School</option>
                <?php foreach (getSchools(true) as $school): ?>
                    <option value="<?= htmlspecialchars($school['school_code'], ENT_QUOTES) ?>" data-code="<?= htmlspecialchars($school['school_code'], ENT_QUOTES) ?>"><?= htmlspecialchars($school['school_name']) ?> (<?= htmlspecialchars($school['school_code']) ?>)</option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <div class="notice hint">School: <b><?= htmlspecialchars(schoolName(currentSchoolScope())) ?></b> (code: <b><?= htmlspecialchars(currentSchoolScope()) ?></b>)</div>
            <input type="hidden" name="delete_school_scope" value="<?= htmlspecialchars(currentSchoolScope(), ENT_QUOTES) ?>">
        <?php endif; ?>

        <label><b>Type the school code above to confirm</b></label>
        <input class="form-control" type="text" id="confirm-school-code" name="confirm_school_code" placeholder="Type the exact school code" style="max-width:450px;margin:8px 0 15px" autocomplete="off" required>

        <div class="excel-actions">
            <button type="submit" name="delete_all_students" id="delete-all-btn" class="btn btn-danger" disabled>Permanently Delete All Students</button>
        </div>
    </form>
</div>
<script>
(function() {
    var confirmInput = document.getElementById('confirm-school-code');
    var deleteBtn = document.getElementById('delete-all-btn');
    var schoolSelect = document.getElementById('delete-school-select');
    var fixedCode = <?= json_encode(canSeeAllSchools() ? null : currentSchoolScope()) ?>;

    function expectedCode() {
        if (fixedCode) return fixedCode;
        if (schoolSelect && schoolSelect.selectedOptions.length) return schoolSelect.selectedOptions[0].getAttribute('data-code');
        return null;
    }
    function refresh() {
        var expected = expectedCode();
        var typed = (confirmInput.value || '').trim().toUpperCase();
        deleteBtn.disabled = !expected || typed !== expected;
    }
    confirmInput.addEventListener('input', refresh);
    if (schoolSelect) schoolSelect.addEventListener('change', refresh);
    refresh();
})();
</script>
<?php include "admin_footer.php" ?>
