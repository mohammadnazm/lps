<?php include "viewer_header.php"; ?>


<?php
// If not already included, define the function
function normalizeName($text)
{
    $search  = ['ي', 'ك', 'ة', 'ۀ', 'ئ', 'أ', 'إ', 'آ'];
    $replace = ['ی', 'ک', 'ە', 'ە', 'ئ', 'ا', 'ا', 'ا'];
    $text = str_replace($search, $replace, $text);
    $text = trim(preg_replace('/\s+/u', ' ', $text));
    return $text;
}

function getDhySearch($table)
{
    if (!isset($_POST['st_se'])) return false;
    global $conn;
    $conditions = [];

    if (!empty($_POST['st_name'])) {
        $name = mysqli_real_escape_string($conn, normalizeName(trim($_POST['st_name'])));
        $words = explode(" ", $name);
        $nameConditions = [];
        foreach ($words as $word) {
            if (!empty($word)) $nameConditions[] = "TRIM(st_name) LIKE '%$word%'";
        }
        if (!empty($nameConditions)) $conditions[] = "(" . implode(" AND ", $nameConditions) . ")";
    }
    if (!empty($_POST['st_group'])) $conditions[] = "st_group = '" . mysqli_real_escape_string($conn, $_POST['st_group']) . "'";
    if (!empty($_POST['st_class'])) $conditions[] = "st_class = '" . mysqli_real_escape_string($conn, $_POST['st_class']) . "'";
    if (!empty($_POST['st_faculty'])) $conditions[] = "st_faculty = '" . mysqli_real_escape_string($conn, $_POST['st_faculty']) . "'";
    if (!empty($_POST['st_statue'])) $conditions[] = "st_statue = '" . mysqli_real_escape_string($conn, $_POST['st_statue']) . "'";

    if (empty($conditions)) return false;

    $sql = "SELECT * FROM `$table` WHERE " . implode(" AND ", $conditions);
    return mysqli_query($conn, $sql);
}
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
        overflow: hidden;
        /* prevent vertical scroll */
    }

    /* ================= ALERTS ================= */
    .alert {
        margin: 10px auto;
        width: 95%;
        text-align: center;
        border-radius: 10px;
    }

    /* ================= MAIN CONTAINER ================= */
    .dashboard-container {
        display: flex;
        flex-direction: column;
        height: 100vh;
        padding: 15px;
        gap: 15px;
    }

    /* ================= SEARCH FORM CARD ================= */
    .search-card {
        background: #1e293b;
        border-radius: 12px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
    }

    /* Inputs & selects */
    .search-card input.form-control,
    .search-card select.form-control {
        border-radius: 8px;
        padding: 8px;
        background-color: #0f172a;
        border: 1px solid #334155;
        color: #e2e8f0;
    }

    /* Placeholder text */
    .search-card ::placeholder {
        color: #cbd5e1;
        opacity: 1;
    }

    /* Dark select options */
    .search-card select option {
        background-color: #1e293b;
        color: #e2e8f0;
    }

    /* Focus */
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

    /* ================= TABLE CARD ================= */
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
        flex: 1;
        /* fills card */
        overflow-y: auto;
    }

    .scrollableBox table {
        width: 100%;
        border-collapse: collapse;
        text-align: center;
    }

    .scrollableBox th,
    .scrollableBox td {
        padding: 8px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .scrollableBox th {
        background-color: #334155;
        color: #e2e8f0;
    }

    .scrollableBox tr:hover {
        background-color: rgba(56, 189, 248, 0.1);
    }

    .scrollableBox button {
        font-size: 0.75rem;
    }
</style>

<?php
if (isset($_SESSION['msg2'])) {
    echo "<div class='alert alert-danger'>{$_SESSION['msg2']}</div>";
    unset($_SESSION['msg2']);
}
?>
<?php
if (isset($_GET['did'])) {
    $id = intval($_GET['did']);
    $getImg = mysqli_query($conn, "SELECT st_img,st_id_file FROM students WHERE id = $id");
    $row = mysqli_fetch_assoc($getImg);
    if (!empty($row['st_img']) && !empty($row['st_id_file'])) {
        $imgPath1 = "st_image/" . $row['st_img'];
        $imgPath2 = "id_data/" . $row['st_id_file']; /* 2️⃣ Delete image file */
        if (file_exists($imgPath1) && file_exists($imgPath2)) {
            unlink($imgPath1);
            unlink($imgPath2);
        }
    }
    DeleteData("students", $_GET['did']);
    echo "<script> alert('Data deleted successfully'); window.location = 'st_data.php'; </script>";
    $_SESSION['msg2'] = "User Deleted successfully!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>
<div class="dashboard-container">

    <!-- ================= SEARCH CARD ================= -->
    <div class="search-card" style="padding:15px; margin-bottom:20px;">
        <form action="" method="post">
            <div style="display: flex; gap:10px; flex-wrap: wrap; align-items: center;">
                <!-- Student Name -->
                <input class="form-control" type="text" name="st_name" placeholder="Student Name" style="flex:1; min-width:120px;">

                <!-- Group -->
                <select class="form-control" name="st_group" style="flex:1; min-width:80px;">
                    <option disabled selected>Group</option>
                    <option value="newst">قوتابی نوێ</option>
                    <?php foreach (range('A', 'Z') as $letter) echo "<option value='$letter'>$letter</option>"; ?>
                </select>

                <!-- Class -->
                <select class="form-control" name="st_class" style="flex:1; min-width:80px;">
                    <option disabled selected>Grade</option>
                    <option value="10">10</option>
                    <option value="11">11</option>
                    <option value="12">12</option>
                </select>

                <!-- Faculty -->
                <!-- <select class="form-control" name="st_faculty" style="flex:1; min-width:100px;">
                    <option disabled selected>Faculty</option>
                    <option value="زانستی">زانستی</option>
                    <option value="وێژەیی">وێژەیی</option>
                </select> -->

                <!-- Status -->
                <!-- <select class="form-control" name="st_statue" style="flex:1; min-width:100px;">
                    <option disabled selected>Status</option>
                    <option value="بەردەوام">بەردەوام</option>
                    <option value="بەجێهێشتوو">بەجێهێشتوو</option>
                </select> -->

                <!-- Submit -->
                <button class="btn btn-success" name="st_se" type="submit" style="flex:0 0 auto;">Search</button>
            </div>
        </form>
    </div>

    <!-- ================= TABLE CARD ================= -->
    <div class="table-card">
        <div class="scrollableBox">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Student Status</th>
                        <th>Student Type</th>
                        <th>Faculty</th>
                        <th>Class</th>
                        <th>Student Name</th>
                        <th>#</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $counter = 1;
                    $stdList = getDhySearch("students");
                    if ($stdList && $stdList->num_rows > 0) {
                        while ($row = $stdList->fetch_assoc()) {
                            $stcl = ($row['st_statue'] == "بەردەوام")
                                ? "class='btn btn-info btn-sm' style='border-radius:60px;width:100px'"
                                : "class='btn btn-danger btn-sm' style='border-radius:60px;width:100px'";
                            echo "<tr>";
                            echo "<td style='text-align:left'>
                                    <a class='btn btn-info btn-sm' role='button' href='st_profile.php?did=" . $row['id'] . "&nmn=" . $row['st_name'] . "' onclick=\"window.open(this.href,'PopupWindow','width=1000,height=800,scrollbars=yes'); return false;\">Profile</a>
                                  </td>";
                            echo "<td><button $stcl>{$row['st_statue']}</button></td>";
                            echo "<td>{$row['st_type']}</td>";
                            echo "<td><button class='btn btn-warning btn-sm' style='border-radius:60px;width:100px'>{$row['st_faculty']}</button></td>";
                            echo "<td>{$row['st_class']}-{$row['st_group']}</td>";
                            echo "<td>{$row['st_name']}</td>";
                            echo "<td>{$counter}</td>";
                            echo "</tr>";
                            $counter++;
                            $totcou = $counter;
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <h6 style="text-align:center; margin-top:10px;">Total Number Of Students : <?php echo isset($totcou) ? $totcou - 1 : 0; ?></h6>
    </div>

</div>
<?php include "viewer_footer.php"; ?>
