<?php
ob_start(); // ✅ FIX HEADER ERROR

include "admin_header.php";

/* ================= SESSION MESSAGE ================= */
if (isset($_SESSION['msg5'])) {
    $msg = $_SESSION['msg5'];
    unset($_SESSION['msg5']);
}

/* ================= INSERT TEACHER ================= */
if (isset($_POST["sub_st"])) {



    // ✅ MULTIPLE CLASSES SUPPORT
    $classes = isset($_POST['st_gp']) ? (array)$_POST['st_gp'] : [];

    foreach ($classes as $class) {

        if ($class != "") {
            insertGivenClass(
                $_POST['u_role'],
                $class, $_POST['school_scope'] ?? null);
        }
    }

    $_SESSION['msg5'] = "added successfully!";

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<?php
if (isset($_GET['did'])) {
    DeleteData("givenclass", $_GET['did']);

    $_SESSION['msg5'] = " Deleted successfully!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<style>
    /* ===== PAGE LAYOUT ===== */
    html,
    body {
        height: 100%;
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', sans-serif;
        background: #0f172a;
        color: #f1f5f9;
        overflow: hidden;
    }

    .page-container {
        display: flex;
        flex-direction: column;
        height: 100vh;
        padding: 20px;
        gap: 20px;
        overflow-y: auto;
    }

    .page-title {
        text-align: center;
        font-size: 24px;
        font-weight: 700;
    }

    /* FORM */
    .student-form-card {
        background: linear-gradient(145deg, #1e293b, #0f172a);
        padding: 25px;
        border-radius: 16px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.4);
    }

    /* INPUTS */
    .student-form-card input,
    .student-form-card select {
        background: #1e293b !important;
        color: #f1f5f9 !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        border-radius: 10px !important;
        padding: 10px !important;
    }

    /* FLEX */
    .student-form-card>form>div {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }
</style>

<div class="page-container">

    <div class="page-title">Lozan Staff Form</div>

    <?php if (isset($msg)) { ?>
        <div class="alert alert-success"><?php echo $msg; ?></div>
    <?php } ?>

    <div class="student-form-card">

        <form method="post" enctype="multipart/form-data">

            <div>
                <?php if (canSeeAllSchools()) { ?>
                <select class="form-control card shadow-sm" name="school_scope" required>
                    <option value="" disabled selected>School</option>
                    <?php foreach (getSchools(true) as $school) { echo "<option value='" . htmlspecialchars($school['school_code'], ENT_QUOTES) . "'>" . htmlspecialchars($school['school_name']) . "</option>"; } ?>
                </select>
                <?php } else { ?>
                <input type="hidden" name="school_scope" value="<?php echo htmlspecialchars(currentSchoolScope()); ?>">
                <?php } ?>
                <select class="form-control card shadow-sm" name="u_role" required>
                    <option value="" disabled selected>Teachers</option>
                    <?php
                    $stdList = getDh("teachers");
                    if ($stdList->num_rows > 0) {
                        while ($row = $stdList->fetch_assoc()) {
                            echo "<option value='" . $row['id'] . "'>" . $row['t_name'] . "</option>";
                        }
                    }
                    ?>
                </select>

                <select name="st_gp[]" multiple style="width:45%; height:120px; text-align:center;">
                    <?php
                    foreach (['Kids'] as $specialGrade) { foreach (range('A','Z') as $letter) echo "<option value='{$specialGrade}-{$letter}'>{$specialGrade}-{$letter}</option>"; }
                    for ($grade=1; $grade<=12; $grade++) {
                        foreach (range('A', 'Z') as $letter) {
                            echo "<option value='{$grade}-{$letter}'>{$grade}-{$letter}</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div style="width:100%; margin-top:20px;">
                <button type="submit" name="sub_st" class="btn btn-success col-12">
                    Give Subject
                </button>
            </div>

        </form>
    </div>

    <hr>

    <!-- ================= TEACHERS TABLE (UNCHANGED) ================= -->
    <div class="scrollableBox">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th style="text-align: center;">#</th>
                    <th style="text-align: center;">Username</th>
                    <th style="text-align: center;">Class</th>
                    <th style="text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $counter = 1;
                $stdList = getDh("givenclass");

                if ($stdList->num_rows > 0) {
                    while ($row = $stdList->fetch_assoc()) {
                        echo "<tr style='text-align:center'>";
                        echo "<td>{$counter}</td>";
                        echo "<td>{$row['t_id']}</td>";
                        echo "<td><button class='btn btn-info btn-sm'>{$row['t_class']}</button></td>";
                        echo "<td>
    <a class='btn btn-danger btn-sm'
       href='?did={$row['id']}'
       onclick=\"return confirm('Are you sure you want to delete this Data?');\">
       Delete
    </a>
</td>";
                        echo "</tr>";
                        $counter++;
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "admin_footer.php"; ?>
<?php ob_end_flush(); ?>