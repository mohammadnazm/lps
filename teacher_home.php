<?php
ob_start();
include "teachers_header.php";
$teid = $_SESSION['teacher_id'];
$counter = 1;
$totcou = 0;

/* ================= SAVE MARKS ================= */
if (isset($_POST['save_marks'])) {

    $subject = $_POST['subject'];
    $semester = $_POST['semester'];

    foreach ($_POST['mark1'] as $student_id => $val) {

        if (!scopedStudentExists($student_id) || !teacherCanAccessStudent($teid, $student_id, $subject)) { continue; }

        $mark1 = mysqli_real_escape_string($conn, $_POST['mark1'][$student_id]);
        $mark2 = mysqli_real_escape_string($conn, $_POST['mark2'][$student_id]);
        $mark3 = mysqli_real_escape_string($conn, $_POST['mark3'][$student_id]);

        $check = mysqli_query($conn, "
            SELECT m.* FROM st_marks m INNER JOIN students s ON s.id=m.st_id
            WHERE m.st_id='$student_id' AND " . schoolSql('s.school_scope') . "
            AND subject='$subject'
            AND semseter='$semester'
        ");

        if (mysqli_num_rows($check) > 0) {

            mysqli_query($conn, "
                UPDATE st_marks SET
                    mark1='$mark1',
                    mark2='$mark2',
                    mark3='$mark3'
                WHERE st_id='$student_id'
                AND subject='$subject'
                AND semseter='$semester'
            ");
        } else {

            mysqli_query($conn, "
                INSERT INTO st_marks(st_id,subject,semseter,mark1,mark2,mark3)
                VALUES('$student_id','$subject','$semester','$mark1','$mark2','$mark3')
            ");
        }
    }

    echo "<div class='alert alert-success'>Marks Saved Successfully!</div>";
}
?>
<?php
$counter = 1;
$totcou = 0;
$stdList = getMarkCon("mark_con" , "m_stat");
if ($stdList->num_rows > 0) {
    while ($row = $stdList->fetch_assoc()) {
        $st_mk_stat = $row["pos_stat"];
    }
}
if($st_mk_stat == "disb") {
    $stt="disabled";
}else
{
    $stt= " ";
}
?>
<style>
    html,
    body {
        height: 100%;
        margin: 0;
        background: #1e293b;
        font-family: 'Segoe UI', sans-serif;
        color: #f1f5f9;
        overflow: hidden;
    }

    /* MAIN LAYOUT */
    .page-wrapper {
        height: 100vh;
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 10px;
    }

    /* FILTER CARD */
    .student-form-card {
        background: linear-gradient(145deg, #334155, #1e293b);
        padding: 15px;
        border-radius: 16px;
    }

    /* FORM FLEX */
    .student-form-card>form>div {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    /* INPUTS */
    .student-form-card input,
    .student-form-card select {
        background: #0f172a !important;
        color: #f1f5f9 !important;
        border-radius: 10px !important;
        padding: 10px !important;
    }

    /* TABLE AREA */
    .scrollableBox {
        flex: 1;
        overflow-y: auto;
        background: linear-gradient(145deg, #334155, #1e293b);
        padding: 10px;
        border-radius: 16px;
    }

    /* TABLE */
    .table {
        color: #f1f5f9 !important;
        min-width: 700px;
    }

    .table thead th {
        background: #475569 !important;
        color: white !important;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .table td {
        text-align: center;
        white-space: nowrap;
    }

    /* SAVE BAR */
    .save-bar {
        padding: 10px;
        background: #0f172a;
        border-radius: 12px;
        position: sticky;
        bottom: 0;
    }

    /* TOTAL */
    .total-users {
        color: #4ade80;
        font-weight: 600;
        margin: 0;
    }

    /* ================= MOBILE FIX ================= */
    @media (max-width: 768px) {

        .page-wrapper {
            gap: 6px;
            padding: 5px;
        }

        /* FILTER FORM */
        .student-form-card {
            padding: 8px;
            border-radius: 10px;
        }

        .student-form-card>form>div {
            gap: 6px;
        }

        /* 🔥 MAKE TEXT READABLE AGAIN */
        .student-form-card select,
        .student-form-card input {
            height: 38px !important;
            font-size: 14px !important;
            /* FIX READABILITY */
            padding: 6px !important;
            border-radius: 8px !important;
        }

        /* 🔥 LOAD STUDENTS BUTTON (FIXED READABLE SIZE) */
        .student-form-card button {
            height: 38px !important;
            font-size: 14px !important;
            /* FIX READABILITY */
            padding: 6px 10px !important;
            min-width: auto !important;
            border-radius: 8px !important;
            font-weight: 600;
        }

        /* TABLE AREA */
        /* FIX SCROLL AREA PROPERLY */
        .scrollableBox {
            flex: 1;
            max-height: calc(100vh - 260px);
            /* 🔥 KEY FIX (space for form + save) */
            overflow-y: auto;
            padding: 6px;
            border-radius: 10px;
        }

        /* MAKE SURE SAVE BAR DOES NOT COVER CONTENT */
        .save-bar {
            position: sticky;
            bottom: 0;
            z-index: 999;
            background: #0f172a;
        }

        /* TABLE */
        .table {
            font-size: 13px;
            min-width: 100%;
        }

        .table td,
        .table th {
            padding: 5px;
        }

        .table input {
            height: 30px;
            font-size: 13px;
        }

        /* SAVE BUTTON */
        .save-bar {
            padding: 6px;
        }

        .save-bar button {
            height: 34px;
            font-size: 13px;
            font-weight: 600;
        }
    }

    @media (max-width: 768px) {

        .table input {
            min-width: 100px !important;
            /* ensures visible number space */
            height: 30px !important;
            font-size: 14px !important;
            padding: 6px !important;
            text-align: center;
            border-radius: 6px;
        }

        /* STOP AUTO ZOOM ON INPUT FOCUS (MOBILE) */
        input,
        select,
        textarea {
            font-size: 16px !important;
        }

        /* keep your existing input style but prevent zoom */
        .table input {
            font-size: 16px !important;
        }
    }
</style>
<?php
$selected_group = $_POST['group'] ?? '';
$selected_subject = $_POST['subject'] ?? '';
$selected_semester = $_POST['semester'] ?? '';
?>
<div class="page-wrapper">
    <!-- FILTER FORM -->
    <div class="student-form-card">
        <form method="POST">
            <div style="
            display:flex;
            gap:10px;
            align-items:center;
            flex-wrap:nowrap;
        ">
                <select class="form-control card shadow-sm" name="group" required style="width:220px;">
                    <option value="">Class</option>
                    <?php
                    $sub = mysqli_query($conn, "SELECT * FROM givenclass WHERE t_id = $teid AND " . schoolSql('school_scope') . " ");
                    while ($s = mysqli_fetch_assoc($sub)) {
                        $sel = ($selected_group == $s['t_class']) ? "selected" : "";
                        echo "<option value='{$s['t_class']}' $sel>{$s['t_class']}</option>";
                    }
                    ?>
                </select>

                <select class="form-control card shadow-sm" name="subject" required style="width:220px;">
                    <option value="">Subject</option>
                    <?php
                    $sub = mysqli_query($conn, "SELECT * FROM teachers WHERE id = $teid AND " . schoolSql('school_scope') . " ");
                    while ($s = mysqli_fetch_assoc($sub)) {
                        $sel = ($selected_subject == $s['t_sub']) ? "selected" : "";
                        echo "<option value='{$s['t_sub']}' $sel>{$s['t_sub']}</option>";
                    }
                    ?>
                </select>

                <select class="form-control card shadow-sm" name="semester" required style="width:170px;">
                    <option value="">Semester</option>
                    <option value="1" <?php if ($selected_semester == "1") echo "selected"; ?>>
                        وەرزی یەکەم </option>

                    <option value="2" <?php if ($selected_semester == "2") echo "selected"; ?>>
                        وەرزی دووەم </option>
                </select>

                <button class="btn btn-success" name="load_students" style="height:45px; min-width:160px;">
                    گەران </button>

            </div>
        </form>
    </div>


    <?php
    /* ================= LOAD STUDENTS ================= */
    if (isset($_POST['load_students'])) {

        $parts = explode('-', $_POST['group']);

        $grade = $parts[0];   // 10
        $class = $parts[1];   // A

        $subject = $_POST['subject'];
        $semester = $_POST['semester'];

        if (!teacherCanAccess($teid, $grade . '-' . $class, $subject)) {
            echo '<div class="alert alert-danger">You are not assigned to this class or subject.</div>';
            $students = false;
        } else {
            $students = mysqli_query($conn, "
            SELECT * FROM students
            WHERE st_class='$grade' AND " . schoolSql('school_scope') . "
            AND st_group='$class'
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
                WHERE m.st_id='$sid' AND " . schoolSql('s.school_scope') . "
                AND subject='$subject'
                AND semseter='$semester'
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

        echo "<div class='save-bar'>
        <button class='btn btn-success col-12' name='save_marks' $stt>Save All Marks</button>
      </div>";
        echo "</form>";
        }
    }
    ?>

    <h6 class="total-users">Total Number Of Students : <?php echo $totcou; ?></h6>

</div>

<?php
ob_end_flush();
include "teacher_footer.php.php";
?>