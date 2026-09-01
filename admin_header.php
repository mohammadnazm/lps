<?php
include_once "db_connection.php";
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['loginadmin']) || $_SESSION['loginadmin'] !== true) {
    unset($_SESSION['loginadmin']);
    header('location: logout_session.php');
    exit;
}

date_default_timezone_set('Asia/Baghdad');
$currentDateTime = date('Y-m-d H:i:s');
$currentDateTime2 = date('Y-m-d');

// Grouped navigation instead of one long row of buttons. "match" pages decide when a
// group's toggle should be highlighted as active, and when the group should auto-open.
$navGroups = [
    'students' => [
        'label' => 'Students',
        'items' => [
            ['add_st.php', 'Add Student'],
            ['st_data.php', 'Students Data'],
            ['class_prices.php', 'Class Prices'],
            ['promote_students.php', 'Promote Students (New Year)'],
            ['arch.php', 'Archive (Import/Export Excel)'],
        ],
    ],
    'academics' => [
        'label' => 'Academics',
        'items' => [
            ['st_marks.php', 'Add Subjects'],
            ['st_add_marks.php', 'Add Marks'],
            ['giv_sub.php', 'Give Class'],
            ['ad_atten.php', 'Attendance'],
        ],
    ],
    'staff' => [
        'label' => 'Staff & Users',
        'items' => [
            ['add_user.php', 'Add User'],
            ['add_tec.php', 'Add Teachers'],
            ['add_lozanstaff.php', 'Add Lozan Staff'],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lozan Admin Panel</title>

    <link rel="stylesheet" href="sorce/css/bootstrap.min.css">
    <script src="sorce/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="main.css">

    <style>
        :root{--bg:#07111f;--card:#101d30;--card2:#16263d;--line:rgba(255,255,255,.08);--text:#e8f0f8;--muted:#93a4b8;--accent:#38bdf8}
        html,body{background:var(--bg)!important;color:var(--text)}

        .top-navbar{background:linear-gradient(135deg,#0d1c2e,#0a1420)!important;border-bottom:1px solid var(--line);padding:.6rem 1rem}
        .navbar-brand{font-weight:800;font-size:1.3rem;letter-spacing:.3px;display:flex;align-items:center;gap:10px}
        .navbar-logo{width:42px;height:42px;border-radius:8px}

        .top-navbar .nav-link{color:#cbd5e1!important;font-size:14.5px;padding:8px 14px!important;border-radius:8px;transition:.15s}
        .top-navbar .nav-link:hover{color:#fff!important;background:rgba(56,189,248,.12)}
        .top-navbar .nav-link.active{background:var(--accent)!important;color:#062033!important;font-weight:700}

        .top-navbar .dropdown-menu{background:var(--card2);border:1px solid var(--line);border-radius:12px;padding:6px;margin-top:6px}
        .top-navbar .dropdown-item{color:#e2e8f0;border-radius:8px;padding:9px 14px;font-size:14px}
        .top-navbar .dropdown-item:hover,.top-navbar .dropdown-item:focus{background:rgba(56,189,248,.15);color:#fff}
        .top-navbar .dropdown-item.active{background:var(--accent);color:#062033;font-weight:700}

        .scope-badge{background:var(--card2);border:1px solid var(--line);color:#93c5fd;padding:7px 14px;border-radius:999px;font-size:12.5px;font-weight:600}
        .btn-logout{margin-left:8px;font-weight:600;border-radius:8px}

        @media (max-width: 991px){
            .navbar-brand{font-size:1.1rem}
            .navbar-logo{width:36px;height:36px}
            .top-navbar .nav-link{padding:10px 12px!important}
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark top-navbar sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= isGeneralAdmin() ? 'general_admin.php' : 'admin.php' ?>">
                <img src="<?= htmlspecialchars(schoolLogoUrl(currentSchoolScope())) ?>" alt="Logo" class="navbar-logo">
                <?= htmlspecialchars(schoolName(currentSchoolScope())) ?>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'admin.php' || $current_page == 'general_admin.php') ? 'active' : '' ?>" href="<?= isGeneralAdmin() ? 'general_admin.php' : 'admin.php' ?>">Home</a>
                    </li>

                    <?php if (isGeneralAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'schools.php') ? 'active' : '' ?>" href="schools.php">Schools</a>
                    </li>
                    <?php endif; ?>

                    <?php foreach ($navGroups as $group):
                        $isGroupActive = false;
                        foreach ($group['items'] as $item) if ($item[0] === $current_page) $isGroupActive = true;
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= $isGroupActive ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= htmlspecialchars($group['label']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach ($group['items'] as $item): ?>
                            <li><a class="dropdown-item <?= ($current_page === $item[0]) ? 'active' : '' ?>" href="<?= htmlspecialchars($item[0]) ?>"><?= htmlspecialchars($item[1]) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <?php endforeach; ?>

                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'activity_log.php') ? 'active' : '' ?>" href="activity_log.php">Activity Log</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'control_acc.php') ? 'active' : '' ?>" href="control_acc.php">Control</a>
                    </li>

                    <li class="nav-item d-flex align-items-center ms-lg-3 mt-2 mt-lg-0 gap-2">
                        <span class="scope-badge">Scope: <?= htmlspecialchars(currentSchoolScope()) ?></span>
                        <a class="btn btn-danger btn-sm btn-logout" href="logout_session.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-3">
