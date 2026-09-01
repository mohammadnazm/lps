<?php
include "admin_header.php";
?>
    <?php
    if (isset($_GET['del_id'])) {
        $delId = intval($_GET['del_id']);
        $delRes = mysqli_query($conn, "SELECT st_name,st_img,st_id_file,school_scope FROM students WHERE id = $delId AND " . schoolSql('school_scope') . "");
        $delRow = mysqli_fetch_assoc($delRes);
        if ($delRow) {
            if (!empty($delRow['st_img'])) { $p = "st_image/" . $delRow['st_img']; if (file_exists($p)) unlink($p); }
            if (!empty($delRow['st_id_file'])) { $p = "id_data/" . $delRow['st_id_file']; if (file_exists($p)) unlink($p); }
            DeleteData("students", $delId);
            logActivity('student_deleted', 'student', $delId, $delRow['st_name'], 'Student record deleted', $delRow['school_scope']);
            $_SESSION['msg'] = "Student \"{$delRow['st_name']}\" deleted successfully!";
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    ?>

    <?php
    if (isset($_SESSION['msg'])) {
        echo "<div class='alert alert-success'>{$_SESSION['msg']}</div>";
        unset($_SESSION['msg']);
    }
    ?>

    <?php
    if (isset($_POST["sub_st"])) {

        $filename = handleSecureUpload('st_img', 'st_image', ['jpg', 'jpeg', 'png', 'gif'], true) ?? '';
        $filename2 = handleSecureUpload('id_file', 'id_data', ['pdf', 'jpg', 'jpeg', 'png'], false) ?? '';

        if ($_POST['st_class'] == "12") {
            $stprice = "2500000";
        } else {
            $stprice = "2000000";
        }
        $targetSchool = $_POST['school_scope'] ?? null;

        insertStudents(
            $_POST['st_name'],
            $_POST['st_m_name'],
            $filename,
            $_POST['bd_date'],
            $_POST['bloodg'],
            $_POST['nation'],
            $_POST['religion'],
            $_POST['gender'],
            $_POST['n_bro'],
            $_POST['n_sis'],
            $_POST['birth_or'],
            $_POST['home_loc'],
            $_POST['av_mark'],
            $_POST['l_school'],
            $_POST['f_year'],
            $_POST['f_tell'],
            $_POST['m_tell'],
            $_POST['s_tell'],
            $_POST['citiz'],
            $_POST['type_id'],
            $_POST['id_number'],
            $filename2,
            $_POST['st_class'],
            $_POST['st_faculty'],
            $_POST['st_type'],
            $currentDateTime,
            $stprice,
            $_POST['cl_size'],
            $_POST['st_gp'],
            $targetSchool
        );

        $_SESSION['msg'] = "Student added successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    ?>

<style>
/* ===== PAGE LAYOUT ===== */
html, body {
    height: 100%;
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #0f172a;
    color: #f1f5f9;
    overflow: hidden; /* prevent full page scroll */
}

.page-container {
    display: flex;
    flex-direction: column;
    height: 100vh;
    padding: 20px;
    gap: 20px;
    overflow-y: auto; /* scroll inside container */
}

/* ===== TITLE ===== */
.page-title {
    text-align: center;
    font-size: 24px;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 10px;
}

/* ===== FORM CARD ===== */
.student-form-card {
    background: linear-gradient(145deg, #1e293b, #0f172a);
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.4);
    border: 1px solid rgba(255,255,255,0.05);
}

/* ===== INPUTS & SELECTS ===== */
.student-form-card input,
.student-form-card select {
    background: #1e293b !important;
    color: #f1f5f9 !important;
    border: 1px solid rgba(255,255,255,0.15) !important;
    border-radius: 10px !important;
    padding: 10px !important;
    margin-bottom: 15px; /* vertical spacing */
}

.student-form-card input:focus,
.student-form-card select:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 0.15rem rgba(59,130,246,0.25) !important;
}

/* ===== PLACEHOLDER COLOR ===== */
.student-form-card input::placeholder,
.student-form-card select::placeholder {
    color: #f1f5f9 !important;
    opacity: 1;
}

/* ===== BUTTON ===== */
.btn-success {
    border-radius: 10px !important;
    font-weight: 600;
    background-color: #22c55e !important;
    border: none !important;
    margin-top: 10px;
}

/* ===== ALERTS ===== */
.alert {
    border-radius: 10px !important;
    font-weight: 600;
    margin-bottom: 20px;
}

/* ===== FLEX WRAPPER ===== */
.student-form-card > form > div {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

/* RESPONSIVE INPUTS */
.student-form-card input,
.student-form-card select,
.student-form-card button {
    flex: 1 1 200px; /* responsive width */
}

/* File inputs styling */
.student-form-card input[type="file"] {
    padding: 6px;
    cursor: pointer;
}

/* ===== RECENTLY ADDED STUDENTS ===== */
.recent-students-card {
    background: linear-gradient(145deg, #1e293b, #0f172a);
    padding: 20px;
    border-radius: 16px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.4);
    border: 1px solid rgba(255,255,255,0.05);
    flex-shrink: 0;
}
.recent-students-title {
    font-size: 17px;
    font-weight: 700;
    margin-bottom: 14px;
    color: #f1f5f9;
}
.recent-empty {
    color: #93a4b8;
    font-size: 13.5px;
    margin: 0;
}
.recent-students-grid {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.recent-student-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    background: #0f172a;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 12px 16px;
    flex-wrap: wrap;
}
.rs-name {
    font-weight: 700;
    font-size: 15px;
    color: #f1f5f9;
}
.rs-meta {
    color: #93a4b8;
    font-size: 12.5px;
    margin-top: 2px;
}
.rs-actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.rs-btn {
    border: 0;
    border-radius: 8px;
    padding: 7px 14px;
    font-size: 12.5px;
    font-weight: 600;
    text-decoration: none;
    color: #fff;
    cursor: pointer;
}
.rs-view { background: #0ea5e9; }
.rs-update { background: #2563eb; }
.rs-delete { background: #dc2626; }
</style>

<div class="page-container">
    <div class="page-title">Students Form</div>
    <div class="student-form-card">
        <form action="" method="post" enctype="multipart/form-data">
            <div>
                <input class="form-control card shadow-sm" type="text" name="st_name" placeholder="Student Full Name" required>
                <input class="form-control card shadow-sm" type="text" name="st_m_name" placeholder="Mother Full Name" required>
                <input class="form-control card shadow-sm" type="file" name="st_img" required>
                <input class="form-control card shadow-sm" type="date" value="2008-01-01" name="bd_date" required>
                <select class="form-control card shadow-sm" name="bloodg" required>
                    <option value="" disabled selected>Blood Group</option>
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                </select>
                <select class="form-control card shadow-sm" name="nation" required>
                    <option value="" disabled selected>Nation</option>
                    <option value="کورد">کورد</option>
                    <option value="عەرەب">عەرەب</option>
                    <option value="تورك">تورك</option>
                    <option value="فارس">فارس</option>
                    <option value="ئەرمەنی">ئەرمەنی</option>
                    <option value="کلندانی">کلدانی</option>
                    <option value="سریانی">سریانی</option>
                    <option value="ئاشوری">ئاشوری</option>
                    <option value="تورکمانی">تورکمانی</option>
                    <option value="ئەلمانی">ئەلمانی</option>
                    <option value="نەتەوەی تر">نەتەوەی تر</option>
                </select>
                <select class="form-control card shadow-sm" name="religion" required>
                    <option value="" disabled selected>Religion</option>
                    <option value="ئیسلام">ئیسلام</option>
                    <option value="مەسیحی">مەسیحی</option>
                    <option value="ئیزیدی">ئیزیدی</option>
                    <option value="زەردەشتی">زەردەشتی</option>
                    <option value="کاکەیی">کاکەیی</option>
                    <option value="هیتر">هیتر</option>
                </select>
                <select class="form-control card shadow-sm" name="gender" required>
                    <option value="" disabled selected>Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
                <select class="form-control card shadow-sm" name="cl_size" required>
                    <option value="" disabled selected>Size</option>
                    <option value="XS">XS</option>
                    <option value="Small">Small</option>
                    <option value="Medium">Medium</option>
                    <option value="Large">Large</option>
                    <option value="XL">XL</option>
                    <option value="2XL">2XL</option>
                    <option value="3XL">3XL</option>
                    <option value="4XL">4XL</option>
                </select>
                <input class="form-control card shadow-sm" type="number" name="n_bro" placeholder="Number of Brothers" required>
                <input class="form-control card shadow-sm" type="number" name="n_sis" placeholder="Number of Sisters" required>
                <input class="form-control card shadow-sm" type="number" name="birth_or" placeholder="Birth Order" required>
                <input class="form-control card shadow-sm" type="text" name="home_loc" placeholder="Home Location" required>
                <select class="form-control card shadow-sm" name="st_type" required>
                    <option value="ئاسایی" selected>ئاسایی</option>
                    <option value="دەرەکی">دەرەکی</option>
                    <option value="تێکرای نمرە">تێکرای نمرە</option>
                </select>
                <select class="form-control card shadow-sm" name="school_scope" required>
                    <option value="" disabled selected>School</option>
                    <?php if (canSeeAllSchools()) { ?>
                        <?php foreach (getSchools(true) as $school) { echo "<option value='" . htmlspecialchars($school['school_code'], ENT_QUOTES) . "'>" . htmlspecialchars($school['school_name']) . "</option>"; } ?>
                    <?php } else { ?>
                        <option value="<?php echo htmlspecialchars(currentSchoolScope()); ?>" selected><?php echo htmlspecialchars(currentSchoolScope()); ?></option>
                    <?php } ?>
                </select>
                <select class="form-control card shadow-sm" name="st_class" required>
                    <option value="" disabled selected>Class</option>
                    <?php $gradeSchool = canSeeAllSchools() ? 'LOZAN' : currentSchoolScope(); $gradeList = getSchoolGrades($gradeSchool); if (canSeeAllSchools()) { $gradeList=[]; foreach(getSchools(true) as $sx){ foreach(getSchoolGrades($sx['school_code']) as $gx) $gradeList[$gx['grade_name']]=$gx; } } else { $tmp=[]; foreach($gradeList as $gx) $tmp[$gx['grade_name']]=$gx; $gradeList=$tmp; } foreach($gradeList as $gx) echo '<option value="'.htmlspecialchars($gx['grade_name'],ENT_QUOTES).'">'.htmlspecialchars($gx['grade_name']).'</option>'; ?>
                </select>
                <select class="form-control card shadow-sm" name="st_gp">
                    <option value="newst" selected>Group</option>
                    <?php foreach (range('A', 'Z') as $letter) echo "<option value='{$letter}'>{$letter}</option>"; ?>
                </select>
                <select class="form-control card shadow-sm" name="st_faculty" required>
                    <option value="" disabled selected>Faculty</option>
                    <option value="زانستی">زانستی</option>
                    <option value="وێژەیی">وێژەیی</option>
                    <option value="تایبەت">تایبەت</option>
                </select>
                <input class="form-control card shadow-sm" type="text" name="av_mark" placeholder="Average Mark" required>
                <input class="form-control card shadow-sm" type="text" name="l_school" placeholder="Last School Name" required>
                <select class="form-control card shadow-sm" name="f_year" required>
                    <option value="" disabled selected>Field Year</option>
                    <?php for ($i=1;$i<=7;$i++){echo "<option value='{$i}'>{$i}</option>";} ?>
                </select>
            </div>

            <div>
                <input class="form-control card shadow-sm" type="text" maxlength="13" name="f_tell" placeholder="Father Tell." required>
                <input class="form-control card shadow-sm" type="text" maxlength="13" name="m_tell" placeholder="Mother Tell." required>
                <input class="form-control card shadow-sm" type="text" maxlength="13" name="s_tell" placeholder="Student Tell.">
                <select class="form-control card shadow-sm" name="citiz" required>
                    <option value="" disabled selected>Citizenship</option>
                    <option value="عێراق">عێراق</option>
                    <option value="سوریا">سوریا</option>
                    <option value="تورکیا">تورکیا</option>
                    <option value="ئێران">ئێران</option>
                    <option value="هیتر">هیتر</option>
                </select>
                <select class="form-control card shadow-sm" name="type_id" required>
                    <option value="" disabled selected>Type of ID</option>
                    <option value="کارتی نیشتیمانی">کارتی نیشتیمانی</option>
                    <option value="ناسنامەی باری شارستانی">ناسنامەی باری شارستانی</option>
                    <option value="پاسەپۆرت">پاسەپۆرت</option>
                    <option value="ئیقامە">ئیقامە</option>
                </select>
                <input class="form-control card shadow-sm" type="text" name="id_number" placeholder="ID Number" required>
                <input class="form-control card shadow-sm" type="file" name="id_file" required>
            </div>

            <div style="width:100%; margin-top:20px;">
                <button type="submit" name="sub_st" class="btn btn-success col-12">Submit Form</button>
            </div>
        </form>
    </div>

    <?php
    $recentStudents = [];
    $rsQ = mysqli_query($conn, "SELECT id, st_name, st_class, st_group, st_date FROM students WHERE " . schoolSql('school_scope') . " ORDER BY id DESC LIMIT 2");
    if ($rsQ) while ($rrow = mysqli_fetch_assoc($rsQ)) $recentStudents[] = $rrow;
    ?>
    <div class="recent-students-card">
        <div class="recent-students-title">Recently Added Students</div>
        <?php if (empty($recentStudents)): ?>
            <p class="recent-empty">No students added yet.</p>
        <?php else: ?>
        <div class="recent-students-grid">
            <?php foreach ($recentStudents as $rs):
                $groupLabel = ($rs['st_group'] && $rs['st_group'] !== 'newst') ? '-' . $rs['st_group'] : '';
                $addedLabel = $rs['st_date'] ? date('M j, Y g:i A', strtotime($rs['st_date'])) : '';
            ?>
            <div class="recent-student-item">
                <div class="rs-info">
                    <div class="rs-name"><?=htmlspecialchars($rs['st_name'])?></div>
                    <div class="rs-meta">Class <?=htmlspecialchars($rs['st_class'] . $groupLabel)?><?= $addedLabel ? ' · Added ' . htmlspecialchars($addedLabel) : '' ?></div>
                </div>
                <div class="rs-actions">
                    <a class="rs-btn rs-view" href="st_profile.php?did=<?=$rs['id']?>&nmn=<?=urlencode($rs['st_name'])?>" onclick="window.open(this.href,'PopupWindow','width=1000,height=800,scrollbars=yes'); return false;">View</a>
                    <a class="rs-btn rs-update" href="up_st_profile.php?did=<?=$rs['id']?>&nmn=<?=urlencode($rs['st_name'])?>" onclick="window.open(this.href,'PopupWindow','width=1000,height=800,scrollbars=yes'); return false;">Update</a>
                    <a class="rs-btn rs-delete" href="?del_id=<?=$rs['id']?>" onclick="return confirm('Delete <?=htmlspecialchars(addslashes($rs['st_name']))?>? This cannot be undone.');">Delete</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include "admin_footer.php"; ?>