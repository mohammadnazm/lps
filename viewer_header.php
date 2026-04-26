<?php
ini_set('display_errors', 0);
session_start();  // Must be before any HTML output
include "db_connection.php";
$current_page = basename($_SERVER['PHP_SELF']);
?>
<?php
if (!isset($_SESSION['loginViewer']) || $_SESSION['loginViewer'] !== true) {
    unset($_SESSION['loginViewer']);
    header('location: index.php');
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
    <script src="sorce/js/bootstrap.min.js"></script>

    <title>Lozan</title>

    <style>
        .nav-link.active {
            background-color: #0dcaf0;
            color: #fff !important;
            border-radius: 6px;
            padding: 6px 12px;
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
            <img src="images/Lozan Logo.png" style="width:65px;height:65px;" alt="Logo">

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">

                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'viewer_home.php') ? 'active' : '' ?>"
                            href="viewer_home.php">پێشەکی</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'viewer_st_data.php') ? 'active' : '' ?>"
                            href="viewer_st_data.php">زانیاری قوتابی</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'viewer_att_data.php') ? 'active' : '' ?>"
                            href="viewer_att_data.php">تۆماری نەهاتوو</a>
                    </li>
                    <li class="nav-item">
                        <a class=" btn btn-danger"
                            href="index.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div style="margin:10px"></div>