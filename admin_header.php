<?php
session_start();  // Must be before any HTML output
include "db_connection.php";
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['loginadmin']) || $_SESSION['loginadmin'] !== true) {
    unset($_SESSION['loginadmin']);
    header('location: logout_session.php');
    exit;
}

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
    <script src="sorce/js/bootstrap.min.js"></script>

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
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-secondary">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="admin.php">
                LOZAN
                <img src="images/Lozan Logo.png" alt="Logo" class="navbar-logo">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'admin.php') ? 'active' : '' ?>" href="admin.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'add_user.php') ? 'active' : '' ?>" href="add_user.php">Add User</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'add_st.php') ? 'active' : '' ?>" href="add_st.php">Add Student</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'st_data.php') ? 'active' : '' ?>" href="st_data.php">Students Data</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'ad_atten.php') ? 'active' : '' ?>" href="ad_atten.php">Attendance</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'edit_att_data.php') ? 'active' : '' ?>" href="edit_att_data.php">Control</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'arch.php') ? 'active' : '' ?>" href="arch.php">Archive</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-danger btn-logout" href="logout_session.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-3">
