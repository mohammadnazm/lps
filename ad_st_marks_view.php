<?php
include_once "db_connection.php";
$current_page = basename($_SERVER['PHP_SELF']);


date_default_timezone_set('Asia/Baghdad');
$currentDateTime = date('Y-m-d H:i:s');
$currentDateTime2 = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lozan Admin Panel</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="sorce/css/bootstrap.min.css">
    <script src="sorce/js/bootstrap.bundle.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="main.css">

    <style>
        /* Navbar branding */
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
            letter-spacing: 1px;
        }

        /* Navbar logo */
        .navbar-logo {
            width: 60px;
            height: 60px;
            margin-left: 10px;
        }

        /* Active link styling */
        .nav-link.active {
            background-color: #0dcaf0;
            color: #fff !important;
            border-radius: 6px;
            padding: 6px 12px;
            transition: 0.3s;
        }

        /* Navbar links hover effect */
        .nav-link {
            color: #cbd5e1 !important;
            transition: 0.3s;
        }

        .nav-link:hover {
            color: #0dcaf0 !important;
            background: rgba(13, 202, 240, 0.1);
            border-radius: 6px;
        }

        /* Logout button styling */
        .btn-logout {
            margin-left: 10px;
            font-weight: 500;
        }

        /* Adjust navbar padding */
        .navbar {
            padding: 0.5rem 1rem;
        }

        /* Responsive logo & brand alignment */
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.2rem;
            }

            .navbar-logo {
                width: 50px;
                height: 50px;
            }
        }
    </style>
</head>

<body>
<style>
    * {
        box-sizing: border-box;
        font-family: "Segoe UI", Tahoma, sans-serif;
    }

    body {
        margin: 0;
        background: #f4f6fb;
    }

    /* Page wrapper */
    .page {
        max-width: 1000px;
        margin: auto;
        padding: 20px;
    }

    /* Semester title */
    .sem-title {
        margin: 25px 0 10px;
        font-size: 20px;
        font-weight: 700;
        color: #142a52;
        border-left: 5px solid #d4af37;
        padding-left: 10px;
    }

    /* Card */
    .card {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        margin-bottom: 25px;
    }

    /* Table */
    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background: linear-gradient(135deg, #0a1a33, #142f5f);
        color: #fff;
        padding: 12px;
        text-align: left;
        font-size: 14px;
    }

    td {
        padding: 12px;
        border-bottom: 1px solid #eee;
        font-size: 14px;
    }

    tr:hover {
        background: #f8f9fc;
    }

    /* subject column */
    .subject {
        font-weight: 600;
        color: #222;
    }

    /* marks style */
    .mark {
        text-align: center;
        font-weight: 600;
    }

    /* responsive */
    @media (max-width: 600px) {

        th,
        td {
            font-size: 12px;
            padding: 10px;
        }
    }
</style>

<div class="page">
    <!-- ================= FIRST SEMESTER ================= -->
    <div class="sem-title">وەرزی یەکەم</div>

    <div class="card">
        <table>
            <tr>
                <th>20%</th>
                <th>10%</th>
                <th>5%</th>
                <th>بابەت</th>

            </tr>

            <?php
            $stdList = getMarksBySemster("st_marks", $_GET['did'], "1");

            if ($stdList->num_rows > 0) {
                while ($row = $stdList->fetch_assoc()) {
            ?>
                    <tr>
                        <td class="mark"><?php echo $row['mark1']; ?></td>
                        <td class="mark"><?php echo $row['mark2']; ?></td>
                        <td class="mark"><?php echo $row['mark3']; ?></td>
                        <td class="subject"><?php echo $row['subject']; ?></td>

                    </tr>
            <?php
                }
            } else {
                echo "<tr><td colspan='4' style='text-align:center;padding:15px;'>No data found</td></tr>";
            }
            ?>

        </table>
    </div>

    <!-- ================= SECOND SEMESTER ================= -->
    <div class="sem-title">وەرزی دووەم</div>

    <div class="card">
        <table>
            <tr>
                <th>20%</th>
                <th>10%</th>
                <th>5%</th>
                <th>بابەت</th>
            </tr>

            <?php
            $stdList = getMarksBySemster("st_marks", $_GET['did'], "2");

            if ($stdList->num_rows > 0) {
                while ($row = $stdList->fetch_assoc()) {
            ?>
                    <tr>
                        <td class="mark"><?php echo $row['mark1']; ?></td>
                        <td class="mark"><?php echo $row['mark2']; ?></td>
                        <td class="mark"><?php echo $row['mark3']; ?></td>
                        <td class="subject"><?php echo $row['subject']; ?></td>
                    </tr>
            <?php
                }
            } else {
                echo "<tr><td colspan='4' style='text-align:center;padding:15px;'>No data found</td></tr>";
            }
            ?>

        </table>
    </div>

</div>

</body>
</html>