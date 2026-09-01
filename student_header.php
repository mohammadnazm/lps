<?php
include_once "db_connection.php";
$current_page = basename($_SERVER['PHP_SELF']);
?>
<?php
if (!isset($_SESSION['student_login']) || $_SESSION['student_login'] !== true) {
    unset($_SESSION['student_login']);
    header('location: logout_session.php');
    exit;
}

$stdList = getMarkConById("acc_con", $_SESSION['student_id']);
if ($stdList->num_rows > 0) {
    while ($row = $stdList->fetch_assoc()) {
        $st_mk_stat1 = $row["acc_st"];
    }
}
if ($st_mk_stat1 == "disb") {
    header('location: acc_ban.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <!-- Bootstrap -->
    <link rel="stylesheet" href="sorce/css/bootstrap.min.css">
    <script src="sorce/js/bootstrap.bundle.min.js"></script>

    <title>Lozan</title>

    <style>
        * {
            box-sizing: border-box;
            font-family: "Segoe UI", Tahoma, sans-serif;
        }

        .nav-link.active {
            background-color: #0dcaf0;
            color: #fff !important;
            border-radius: 6px;
            padding: 6px 12px;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Center wrapper */
        .page-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px;
            min-height: 50vh;
            /* full screen height */
            overflow-x: hidden;
        }

        /* Main card */
        .profile-card {
            width: 100%;
            max-width: 520px;
            background: #fff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin: auto;
        }

        /* Header */
        .profile-header {
            background: linear-gradient(135deg, #0a1a33 0%, #142f5f 50%, #d4af37 120%);
            color: #fff;
            text-align: center;
            padding: 30px 20px;
            position: relative;
            overflow: hidden;
        }

        /* subtle glow effect */
        .profile-header::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.15), transparent 60%);
            transform: rotate(25deg);
        }

        .profile-header img,
        .profile-header h2,
        .profile-header p {
            position: relative;
            z-index: 2;
        }

        .profile-header img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: contain;
            /* مهم: shows full image */
            object-position: center;
            border: 4px solid #fff;
            background: #fff;
            display: block;
            margin: 0 auto;
        }

        .profile-header h2 {
            margin: 12px 0 5px;
            font-size: 22px;
            font-weight: 600;
        }

        .profile-header p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }

        /* Body */
        .profile-body {
            padding: 20px;
        }

        /* Info grid (responsive) */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .info-box {
            background: #f8f9fc;
            padding: 14px 15px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #eee;
        }

        .info-box span {
            font-weight: 600;
            color: #555;
        }

        .info-box .value {
            color: #222;
            font-weight: 500;
        }

        /* Footer */
        .profile-footer {
            text-align: center;
            padding: 14px;
            background: #f1f3f9;
            font-size: 13px;
            color: #666;
        }

        /* Tablet */
        @media (min-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* Small phones */
        @media (max-width: 400px) {
            .profile-header img {
                width: 90px;
                height: 90px;
            }

            .profile-header h2 {
                font-size: 18px;
            }
        }

        .theme-btn {
            display: block;
            width: 100%;
            text-align: center;
            padding: 12px 15px;
            margin-top: 10px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            color: #fff;

            background: linear-gradient(135deg, #0a1a33 0%, #142f5f 50%, #d4af37 130%);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
            transition: 0.3s ease;
        }

        .theme-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            opacity: 0.95;
        }
    </style>
</head>
<?php
date_default_timezone_set('Asia/Baghdad');

$currentDateTime = date('Y-m-d H:i:s');
$currentDateTime2 = date('Y-m-d');
?>

<body>

    <nav class="navbar navbar-expand-lg bg-dark border-bottom border-body" data-bs-theme="dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="admin.php">LOZAN</a>
            <img src="<?= htmlspecialchars(schoolLogoUrl(currentSchoolScope())) ?>" style="width:65px;height:65px;" alt="Logo">

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><span class="badge bg-info text-dark me-2">Scope: <?= htmlspecialchars(currentSchoolScope()) ?></span></li>
                    <li class="nav-item">
                        <a class=" btn btn-danger"
                            href="logout_session.php">چوونە دەرەوە</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div style="margin:10px"></div>