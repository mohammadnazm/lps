<?php
ini_set('display_errors', 0);
session_start();  // Must be before any HTML output
include "db_connection.php";
$current_page = basename($_SERVER['PHP_SELF']);
?>
<?php
if (!isset($_SESSION['loginaceo']) || $_SESSION['loginaceo'] !== true) {
    unset($_SESSION['loginaceo']);
    header('location: logout_session.php');
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
            <a class="navbar-brand fw-bold" href="ceo_se.php">LOZAN</a>
            <img src="images/Lozan Logo.png" style="width:65px;height:65px;" alt="Logo">

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <div style="display: flex; gap: 10px;">
                        <li class="nav-item">
                            <a class="btn btn-info"
                                href="ceo_home.php">پێشەکی</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-info"
                                href="ceo_se.php">گەران بەدوای قوتابی</a>
                        </li>
                        <li class="nav-item">
                            <a class=" btn btn-danger"
                                href="logout_session.php">چوونەدەرەوە</a>
                        </li>
                    </div>
                </ul>
            </div>
        </div>
    </nav>
    <div style="margin:10px"></div>
