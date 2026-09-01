<?php
ob_start();
include "admin_header.php";

$counter = 1;
$totcou = 0;

/* ================= SAVE MARKS ================= */
if (isset($_POST['save_marks'])) {

    $subject = $_POST['subject'];
    $semester = $_POST['semester'];

    foreach ($_POST['mark1'] as $student_id => $val) {

        if (!scopedStudentExists($student_id)) { continue; }

        $mark1 = mysqli_real_escape_string($conn, $_POST['mark1'][$student_id]);
        $mark2 = mysqli_real_escape_string($conn, $_POST['mark2'][$student_id]);
        $mark3 = mysqli_real_escape_string($conn, $_POST['mark3'][$student_id]);

        $check = mysqli_query($conn, "
            SELECT * FROM st_marks
            WHERE st_id='$student_id' AND EXISTS (SELECT 1 FROM students s WHERE s.id=st_marks.st_id AND " . schoolSql('s.school_scope') . ")
            AND subject='$subject'
            AND semseter='$semester'
        ");
        $oldMark = $check ? mysqli_fetch_assoc($check) : null;

        if ($oldMark) {

            mysqli_query($conn, "
                UPDATE st_marks SET
                    mark1='$mark1',
                    mark2='$mark2',
                    mark3='$mark3'
                WHERE st_id='$student_id'
                AND subject='$subject'
                AND semseter='$semester'
            ");

            $changedParts = [];
            if ((string)$oldMark['mark1'] !== (string)$mark1) $changedParts[] = "20%: {$oldMark['mark1']} → $mark1";
            if ((string)$oldMark['mark2'] !== (string)$mark2) $changedParts[] = "10%: {$oldMark['mark2']} → $mark2";
            if ((string)$oldMark['mark3'] !== (string)$mark3) $changedParts[] = "5%: {$oldMark['mark3']} → $mark3";

            if ($changedParts) {
                $stInfoRes = mysqli_query($conn, "SELECT st_name, school_scope FROM students WHERE id='$student_id' LIMIT 1");
                $stInfo = $stInfoRes ? mysqli_fetch_assoc($stInfoRes) : null;
                $summary = "Subject: $subject, Semester $semester — " . implode(', ', $changedParts);
                logActivity('marks_updated', 'student', $student_id, $stInfo['st_name'] ?? ('Student #' . $student_id), $summary, $stInfo['school_scope'] ?? null);
            }
        } else {

            mysqli_query($conn, "
                INSERT INTO st_marks(st_id,subject,semseter,mark1,mark2,mark3)
                VALUES('$student_id','$subject','$semester','$mark1','$mark2','$mark3')
            ");

            if ($mark1 !== '' || $mark2 !== '' || $mark3 !== '') {
                $stInfoRes = mysqli_query($conn, "SELECT st_name, school_scope FROM students WHERE id='$student_id' LIMIT 1");
                $stInfo = $stInfoRes ? mysqli_fetch_assoc($stInfoRes) : null;
                $summary = "Subject: $subject, Semester $semester — 20%: $mark1, 10%: $mark2, 5%: $mark3 (new)";
                logActivity('marks_updated', 'student', $student_id, $stInfo['st_name'] ?? ('Student #' . $student_id), $summary, $stInfo['school_scope'] ?? null);
            }
        }
    }

    echo "<div class='alert alert-success'>Marks Saved Successfully!</div>";
}
?>

<style>
    html,
    body {
        height: 100%;
        overflow: hidden;
        background: #1e293b;
        font-family: 'Segoe UI', sans-serif;
        color: #f1f5f9;
    }

    .student-form-card {
        background: linear-gradient(145deg, #334155, #1e293b);
        padding: 20px;
        border-radius: 16px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .student-form-card input,
    .student-form-card select {
        background: #0f172a !important;
        color: #f1f5f9 !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        border-radius: 10px !important;
        padding: 10px !important;
    }

    .student-form-card input:focus,
    .student-form-card select:focus {
        border-color: #60a5fa !important;
        box-shadow: 0 0 0 0.15rem rgba(96, 165, 250, 0.3) !important;
    }

    .scrollableBox {
        max-height: 70vh;
        overflow-y: auto;
        background: linear-gradient(145deg, #334155, #1e293b);
        padding: 15px;
        border-radius: 16px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .table {
        color: #f1f5f9 !important;
    }

    .table thead th {
        background: #475569 !important;
        color: white !important;
        text-align: center;
    }

    .table tbody tr {
        background: #1e293b;
    }

    .table tbody tr:hover {
        background: #334155;
    }

    .table td {
        text-align: center;
        vertical-align: middle !important;
    }

    .total-users {
        font-weight: 600;
        color: #4ade80;
        margin-top: 10px;
    }
</style>
<?php
$selected_grade = $_POST['grade'] ?? '';
$selected_group = $_POST['group'] ?? '';
$selected_subject = $_POST['subject'] ?? '';
$selected_semester = $_POST['semester'] ?? '';
?>
<div style="display:flex;flex-direction:column;gap:20px;">

    <!-- FILTER FORM -->
    <div class="student-form-card">
        <form method="POST">
            <div style="
            display:flex;
            gap:10px;
            align-items:center;
            flex-wrap:nowrap;
        ">

                <select class="form-control card shadow-sm" name="grade" required style="width:140px;">

                    <option value="Kids">Kids</option>
                    <?php for ($g=1; $g<=12; $g++) { $sel = ((string)($selected_grade ?? $stclass ?? '') === (string)$g) ? 'selected' : ''; echo "<option value=\"$g\" $sel>$g</option>"; } ?>
                </select>

                <select class="form-control card shadow-sm" name="group" required style="width:140px;">
                    <option value="">Group</option>
                    <?php
                    foreach (range('A', 'Z') as $letter) {
                        $sel = ($selected_group == $letter) ? "selected" : "";
                        echo "<option value='$letter' $sel>$letter</option>";
                    }
                    ?>
                </select>

                <select class="form-control card shadow-sm" name="subject" required style="width:220px;">
                    <option value="">Subject</option>
                    <?php
                    $sub = mysqli_query($conn, "SELECT * FROM subjects WHERE " . schoolSql('school_scope') . "");
                    while ($s = mysqli_fetch_assoc($sub)) {
                        $sel = ($selected_subject == $s['sb_name']) ? "selected" : "";
                        echo "<option value='{$s['sb_name']}' $sel>{$s['sb_name']}</option>";
                    }
                    ?>
                </select>

                <select class="form-control card shadow-sm" name="semester" required style="width:170px;">
                    <option value="">Semester</option>
                    <option value="1" <?php if ($selected_semester == "1") echo "selected"; ?>>
                        Semester 1
                    </option>

                    <option value="2" <?php if ($selected_semester == "2") echo "selected"; ?>>
                        Semester 2
                    </option>
                </select>

                <button class="btn btn-success" name="load_students" style="height:45px; min-width:160px;">
                    Load Students
                </button>

            </div>
        </form>
    </div>


    <?php
    /* ================= LOAD STUDENTS ================= */
    if (isset($_POST['load_students'])) {

        $grade = $_POST['grade'];
        $group = $_POST['group'];
        $subject = $_POST['subject'];
        $semester = $_POST['semester'];

        $students = mysqli_query($conn, "
            SELECT * FROM students
            WHERE st_class='$grade'
            AND st_group='$group'
            AND " . schoolSql('school_scope') . "
            ORDER BY st_name ASC
        ");

        echo "<form method='POST'>";
        echo "<div class='scrollableBox'>";
        echo "<table class='table table-striped'>";
        echo "<thead>
                <tr>
                    <th>20%</th>
                    <th>10%</th>
                    <th>5%</th>
                    <th>Student Name</th>
                    <th>#</th>
                </tr>
              </thead>";
        echo "<tbody>";

        $counter = 1;

        while ($st = mysqli_fetch_assoc($students)) {

            $sid = $st['id'];

            $mq = mysqli_query($conn, "
                SELECT m.* FROM st_marks m INNER JOIN students s ON s.id=m.st_id
                WHERE m.st_id='$sid'
                AND m.subject='$subject'
                AND m.semseter='$semester'
                AND " . schoolSql('s.school_scope') . "
            ");

            $mark = mysqli_fetch_assoc($mq);

            $m1 = $mark['mark1'] ?? '';
            $m2 = $mark['mark2'] ?? '';
            $m3 = $mark['mark3'] ?? '';

            echo "<tr>";

            echo "<td>
                    <input class='form-control text-center'
                    type='text'
                    name='mark1[$sid]'
                    value='$m1'>
                  </td>";

            echo "<td>
                    <input class='form-control text-center'
                    type='text'
                    name='mark2[$sid]'
                    value='$m2'>
                  </td>";

            echo "<td>
                    <input class='form-control text-center'
                    type='text'

                    name='mark3[$sid]'
                    value='$m3'>
                  </td>";
            echo "<td>{$st['st_name']}</td>";
            echo "<td>{$counter}</td>";

            echo "</tr>";

            $counter++;
        }

        $totcou = $counter - 1;

        echo "</tbody></table>";
        echo "</div>";

        echo "<input type='hidden' name='subject' value='$subject'>";
        echo "<input type='hidden' name='semester' value='$semester'>";

        echo "<br><button class='btn btn-success col-12' name='save_marks'>Save All Marks</button>";
        echo "</form>";
    }
    ?>

    <h6 class="total-users">Total Number Of Students : <?php echo $totcou; ?></h6>

</div>

<?php
ob_end_flush();
include "admin_footer.php";
?>