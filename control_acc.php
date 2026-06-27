<?php
ob_start();
include "admin_header.php"; ?>

<?php
if (isset($_SESSION['msg'])) {
    echo "<div class='alert alert-success'>{$_SESSION['msg']}</div>";
    unset($_SESSION['msg']);
}
if (isset($_SESSION['msg2'])) {
    echo "<div class='alert alert-danger'>{$_SESSION['msg2']}</div>";
    unset($_SESSION['msg2']);
}
?>

<?php
if (isset($_POST["u_add"])) {
    echo "<div class='alert alert-success' role='alert'>";
    insertAccCon($_POST["u_role"]);
    echo "</div>";
    $_SESSION['msg'] = "added successfully!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}


if (isset($_POST["uid_add"])) {
    echo "<div class='alert alert-success' role='alert'>";
    insertAccConById($_POST["uname"]);
    echo "</div>";
    $_SESSION['msg'] = "added successfully!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_GET['did'])) {
    DeleteData("mark_con", $_GET['did']);
    echo "<script>
            alert('Data deleted successfully');
            window.location = 'control_acc.php';
          </script>";
    $_SESSION['msg2'] = "Deleted successfully!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
if (isset($_GET['did1'])) {
    DeleteData("acc_con", $_GET['did1']);
    echo "<script>
            alert('Data deleted successfully');
            window.location = 'control_acc.php';
          </script>";
    $_SESSION['msg2'] = "Deleted successfully!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>


<style>
    /* ===== PAGE LAYOUT (Same as Dashboard) ===== */
    html,
    body {
        height: 100%;
        overflow: hidden;
        background: #1e293b;
        font-family: 'Segoe UI', sans-serif;
        color: #f1f5f9;
    }

    .page-container {
        display: flex;
        flex-direction: column;
        height: 100vh;
        padding: 20px;
        gap: 20px;
    }

    /* ===== TITLE ===== */
    .page-title {
        text-align: center;
        font-weight: 700;
        font-size: 22px;
        color: #ffffff;
        margin-bottom: 10px;
    }

    /* ===== FORM CARD ===== */
    .student-form-card {
        background: linear-gradient(145deg, #334155, #1e293b);
        padding: 20px;
        border-radius: 16px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    /* ===== INPUTS ===== */
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

    /* ===== BUTTON ===== */
    .student-form-card button {
        border-radius: 10px !important;
        font-weight: 600;
    }

    /* ===== ALERTS ===== */
    .alert {
        border-radius: 10px !important;
        font-weight: 600;
    }

    /* ===== TABLE CARD ===== */
    .scrollableBox {
        flex: 1;
        overflow-y: auto;
        background: linear-gradient(145deg, #334155, #1e293b);
        padding: 15px;
        border-radius: 16px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    /* ===== TABLE ===== */
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

    /* ===== ROLE BUTTONS ===== */
    .btn-warning {
        background-color: #facc15 !important;
        border: none !important;
        color: #000 !important;
    }

    .btn-info {
        background-color: #38bdf8 !important;
        border: none !important;
        color: #000 !important;
    }

    .btn-danger {
        border-radius: 8px !important;
    }

    /* ===== TOTAL TEXT ===== */
    .total-users {
        font-weight: 600;
        color: #4ade80;
        margin-top: 10px;
    }

    .student-form-card input::placeholder {
        color: #ffffff !important;
        opacity: 1;
        /* important for full white */
    }

    /* For better browser support */
    .student-form-card input::-webkit-input-placeholder {
        color: #ffffff !important;
    }

    .student-form-card input:-ms-input-placeholder {
        color: #ffffff !important;
    }

    .student-form-card input::-ms-input-placeholder {
        color: #ffffff !important;
    }
</style>









<div style="display: flex;flex-direction: column;flex-wrap: wrap;gap:5%;">


    <div class="student-form-card">
        <form action="" method="post">
            <div style="display: flex;gap:10px;flex-wrap: wrap;">
                <select class="form-control card shadow-sm" name="u_role" required>
                    <option value="" disabled selected>Account</option>
                    <option value="st_stat">Students</option>
                    <option value="t_stat">Teachers</option>
                    <option value="m_stat">Marks</option>
                </select>
                <button class="btn btn-success col-12" name="u_add" type="submit">Add User</button>
            </div>
        </form>
    </div>
    <br>
    <div class="scrollableBox">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th style="background-color:grey;">#</th>
                    <th style="background-color:grey; text-align: center;">Accounts</th>
                    <th style="background-color:grey; text-align: center;">Status</th>
                    <th style="background-color:grey; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $counter = 1;
                $totcou = 0;
                $stdList = getDh("mark_con");
                if ($stdList->num_rows > 0) {
                    while ($row = $stdList->fetch_assoc()) {
                        echo "<tr style='text-align:center'>";
                        echo "<td>{$counter}</td>";
                        echo "<td style='text-align:center'>{$row['pos_nm']}</td>";
                        echo "<td style='text-align:center'>{$row['pos_stat']}</td>";
                        echo "<td style='text-align:center'><a class='btn btn-danger btn-sm' role='button' href='control_acc.php?did=" . $row['id'] . "' onclick=\"return confirm('Are you sure you want to delete this?');\"'>Delete</a></td>";
                        echo "</tr style='text-align:center'>";
                        $counter++;
                        $totcou = $counter;
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

</div>
<br>
<div style="display: flex;flex-direction: column;flex-wrap: wrap;gap:5%;">


    <div class="student-form-card">
        <form action="" method="post">
            <div style="display: flex;gap:10px;flex-wrap: wrap;">
                <input class="form-control card shadow-sm" name="uname" placeholder="ID" type="number" required>
                <button class="btn btn-success col-12" name="uid_add" type="submit">Add User</button>
            </div>
        </form>
    </div>
    <br>
    <div class="scrollableBox">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th style="background-color:grey;">#</th>
                    <th style="background-color:grey; text-align: center;">ID</th>
                    <th style="background-color:grey; text-align: center;">Status</th>
                    <th style="background-color:grey; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $counter = 1;
                $totcou = 0;
                $stdList = getDh("acc_con");
                if ($stdList->num_rows > 0) {
                    while ($row = $stdList->fetch_assoc()) {
                        echo "<tr style='text-align:center'>";
                        echo "<td>{$counter}</td>";
                        echo "<td style='text-align:center'>{$row['acc_id']}</td>";
                        echo "<td style='text-align:center'>{$row['acc_st']}</td>";
                        echo "<td style='text-align:center'><a class='btn btn-danger btn-sm' role='button' href='control_acc.php?did1=" . $row['id'] . "' onclick=\"return confirm('Are you sure you want to delete this?');\"'>Delete</a></td>";
                        echo "</tr style='text-align:center'>";
                        $counter++;
                        $totcou = $counter;
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

</div>
<?php
ob_end_flush();
?>
<?php include "admin_footer.php"; ?>