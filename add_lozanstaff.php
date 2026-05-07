<?php
ob_start(); // ✅ FIX HEADER ERROR

include "admin_header.php";

ini_set('display_errors', 1);

/* ================= SESSION MESSAGE ================= */
if (isset($_SESSION['msg5'])) {
    $msg = $_SESSION['msg5'];
    unset($_SESSION['msg5']);
}

/* ================= INSERT TEACHER ================= */
if (isset($_POST["sub_st"])) {

    $filename = $_FILES['st_img']['name'];
    $tmpname  = $_FILES['st_img']['tmp_name'];

    move_uploaded_file($tmpname, "teachers_img/" . $filename);

    insertTeachers(
        $filename,
        $_POST['st_name'],
        $_POST['st_m_name'],
        $_POST['st_gp']
    );

    $_SESSION['msg5'] = "Teacher added successfully!";

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_POST["cl_class"])) {

    insertClosedClass(
        $_POST['st_gp'],
        "disabled"
    );

    $_SESSION['msg5'] = "Class Disabled successfully!";

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

?>


<?php
if (isset($_GET['did'])) {
    $id = intval($_GET['did']);
    $getImg = mysqli_query($conn, "SELECT teacher_img FROM lozanstaff WHERE id = $id");
    $row = mysqli_fetch_assoc($getImg);
    if (!empty($row['teacher_img'])) {
        $imgPath1 = "teachers_img/" . $row['teacher_img'];
        if (file_exists($imgPath1)) {
            unlink($imgPath1);
        }
    }
    DeleteData("lozanstaff", $_GET['did']);
    echo "<script>
            alert('Data deleted successfully');
            window.location = 'add_lozanstaff.php';
          </script>";
    $_SESSION['msg5'] = "Teacher Deleted successfully!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
if (isset($_GET['did2'])) {
    $id = intval($_GET['did2']);
    DeleteData("staffclasscon", $_GET['did2']);
    echo "<script>
            alert('Data deleted successfully');
            window.location = 'add_lozanstaff.php';
          </script>";
    $_SESSION['msg5'] = "Class Enabled successfully!";
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
        margin-bottom: 15px;
    }

    /* BUTTON */
    .btn-success {
        border-radius: 10px !important;
        font-weight: 600;
        background: #22c55e !important;
        border: none !important;
    }

    /* ALERT */
    .alert {
        border-radius: 10px !important;
        font-weight: 600;
        margin-bottom: 20px;
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
        <div class="alert alert-success">
            <?php echo $msg; ?>
        </div>
    <?php } ?>

    <div class="student-form-card">

        <form action="" method="post" enctype="multipart/form-data">

            <div>
                <input type="text" style="width: 15%;" name="st_name" placeholder="T Name" >
                <input type="text" style="width: 15%;" name="st_m_name" placeholder="Education" >
                <input type="file" style="width: 15%;" name="st_img" >

                <select name="st_gp" style="width: 15%;" required>
                    <option value="">Select Class</option>
                    <?php
                    foreach (range('A', 'Z') as $letter) {
                        echo "<option value='12-$letter'>12-$letter</option>";
                    }
                    ?>
                </select>
                <button type="submit" name="cl_class" class="btn btn-danger col-2"> Disable</button>
            </div>

            <div style="width:100%; margin-top:20px;">
                <button type="submit" name="sub_st" class="btn btn-success col-12">
                    Add Staff
                </button>
            </div>

        </form>

    </div>
    <hr>
    <div class="scrollableBox">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th style="background-color:grey;">#</th>
                    <th style="background-color:grey; text-align: center;">Username</th>
                    <th style="background-color:grey; text-align: center;">Education</th>
                    <th style="background-color:grey; text-align: center;">Class</th>
                    <th style="background-color:grey; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $counter = 1;
                $stdList = getDh("lozanstaff");
                if ($stdList->num_rows > 0) {
                    while ($row = $stdList->fetch_assoc()) {
                        echo "<tr style='text-align:center'>";
                        echo "<td>{$counter}</td>";
                        echo "<td style='text-align:center'>{$row['name']}</td>";
                        echo "<td style='text-align:center'>{$row['education']}</td>";
                        echo "<td style='text-align:center'><button class='btn btn-info btn-sm' style='border-radius:80px'>{$row['class']}</button></td>";
                        echo "<td style='text-align:center'><a class='btn btn-danger btn-sm' role='button' href='add_lozanstaff.php?did=" . $row['id'] . "' onclick=\"return confirm('Are you sure you want to delete this?');\"'>Delete</a></td>";
                        echo "<tr style='text-align:center'>";
                        $counter++;
                        $totcou = $counter;
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
    <h6>Total Number Of Users : <?php echo $totcou - 1; ?></h6>
    <div class="scrollableBox">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th style="background-color:grey;">#</th>
                    <th style="background-color:grey; text-align: center;">Class</th>
                    <th style="background-color:grey; text-align: center;">Status</th>
                    <th style="background-color:grey; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $counter = 1;
                $stdList = getDh("staffclasscon");
                if ($stdList->num_rows > 0) {
                    while ($row = $stdList->fetch_assoc()) {
                        echo "<tr style='text-align:center'>";
                        echo "<td>{$counter}</td>";
                        echo "<td style='text-align:center'><button class='btn btn-info btn-sm' style='border-radius:80px'>{$row['class_name']}</button></td>";
                        echo "<td style='text-align:center'>{$row['class_status']}</td>";
                        echo "<td style='text-align:center'><a class='btn btn-danger btn-sm' role='button' href='add_lozanstaff.php?did2=" . $row['id'] . "' onclick=\"return confirm('Are you sure you want to delete this?');\"'>Delete</a></td>";
                        echo "<tr style='text-align:center'>";
                        $counter++;
                        $totcou = $counter;
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
    <h6>Total Number Of Users : <?php echo $totcou - 1; ?></h6>
</div>
</div>

<?php include "admin_footer.php"; ?>
<?php ob_end_flush(); ?>