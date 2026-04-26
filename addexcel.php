<?php
session_start();

if (!isset($_SESSION['loginadmin']) || $_SESSION['loginadmin'] !== true) {
    unset($_SESSION['loginadmin']);
    header('location: logout_session.php');
    exit;
}
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// ================= IMPORT =================
if(isset($_POST['import_excel'])){

    include "db_connection.php";

    // CHECK FILE
    if($_FILES['excel_file']['name']){

        $file = $_FILES['excel_file']['tmp_name'];

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        // START FROM ROW 2 (skip headers)
        for($i = 1; $i < count($data); $i++){

            $row = $data[$i];

            $st_name      = $row[0];
            $st_m_name    = $row[1];
            $st_bd_date   = $row[2];
            $st_b_group   = $row[3];
            $st_nation    = $row[4];
            $st_religion  = $row[5];
            $st_gender    = $row[6];
            $n_bro        = $row[7];
            $n_sis        = $row[8];
            $st_bd_order  = $row[9];
            $st_home_loc  = $row[10];
            $st_avg_mark  = $row[11];
            $last_s_name  = $row[12];
            $st_f_year    = $row[13];
            $f_tell       = $row[14];
            $m_tell       = $row[15];
            $st_tell      = $row[16];
            $st_price     = $row[17];
            $st_citiiz    = $row[18];
            $type_of_id   = $row[19];
            $st_id_number = $row[20];
            $st_id_file   = $row[21];
            $st_class     = $row[22];
            $st_group     = $row[23];
            $st_faculty   = $row[24];
            $st_type      = $row[25];
            $st_date      = $row[26];
            $st_statue    = $row[27];
            $st_size      = $row[28];
            $st_img       = $row[29];

            $sql = "INSERT INTO students (
                st_name,st_m_name,st_bd_date,st_b_group,st_nation,
                st_religion,st_gender,n_bro,n_sis,st_bd_order,
                st_home_loc,st_avg_mark,last_s_name,st_f_year,f_tell,
                m_tell,st_tell,st_price,st_citiiz,type_of_id,
                st_id_number,st_id_file,st_class,st_group,st_faculty,
                st_type,st_date,st_statue,st_size,st_img
            ) VALUES (
                '$st_name','$st_m_name','$st_bd_date','$st_b_group','$st_nation',
                '$st_religion','$st_gender','$n_bro','$n_sis','$st_bd_order',
                '$st_home_loc','$st_avg_mark','$last_s_name','$st_f_year','$f_tell',
                '$m_tell','$st_tell','$st_price','$st_citiiz','$type_of_id',
                '$st_id_number','$st_id_file','$st_class','$st_group','$st_faculty',
                '$st_type','$st_date','$st_statue','$st_size','$st_img'
            )";

            $conn->query($sql);
        }

        echo "Imported Successfully ✔";
    }

}
?>

<?php include "admin_header.php" ?>
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
.box{
    background:#1e293b;
    padding:50px;
    border-radius:12px;
    text-align:center;
}
h2{color:white;}
input,button{
    padding:10px;
    margin:10px;
}
button{
    background:#22c55e;
    border:none;
    color:white;
    padding:12px 25px;
    border-radius:8px;
    cursor:pointer;
}
</style>



<div class="box">

<h2>Import Students From Excel</h2>

<form method="POST" enctype="multipart/form-data">

    <input type="file" name="excel_file" required>

    <br>

    <button name="import_excel">Upload & Import</button>

</form>

</div>
<hr>
<?php
if(isset($_POST['delall']))
    {
        DeleteAllStData();
    }
?>
<form action="" method="post" onsubmit="return confirm('⚠️ Are you sure you want to delete ALL data? This cannot be undone!');">
    <button name="delall" class="btn btn-danger col-7">
        Delete All Data
    </button>
</form>
<?php include "admin_footer.php" ?>

