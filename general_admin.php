<?php
include_once "db_connection.php";

if (!isset($_SESSION['general_admin']) || $_SESSION['general_admin'] !== true) {
    header('Location: index.php');
    exit;
}

date_default_timezone_set('Asia/Baghdad');

function gaScalar($sql) {
    global $conn;
    $r = $conn->query($sql);
    if (!$r) return 0;
    $row = $r->fetch_row();
    return (int)($row[0] ?? 0);
}

$schools = getSchools(false);
$totalSchools = count($schools);
$totalStudents = gaScalar("SELECT COUNT(*) FROM students");
$totalTeachers = gaScalar("SELECT COUNT(*) FROM teachers");
$totalUsers = gaScalar("SELECT COUNT(*) FROM users");
$totalAdmins = gaScalar("SELECT COUNT(*) FROM users WHERE u_role='Admin'");
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>General Administration</title>
<link rel="stylesheet" href="sorce/css/bootstrap.min.css">
<link rel="stylesheet" href="main.css">
<style>
body{background:#07111f;color:#e8f0f8;font-family:Segoe UI,sans-serif}
.ga{padding:28px}.hero{background:linear-gradient(135deg,#13263d,#0b1626);border:1px solid rgba(255,255,255,.08);border-radius:22px;padding:25px;display:flex;justify-content:space-between;align-items:center;gap:15px}
.hero h1{margin:0;font-weight:800}.muted{color:#93a4b8}.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin:20px 0}.stat{background:#101d30;border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:20px}.stat b{display:block;font-size:30px;margin-top:7px}.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(330px,1fr));gap:16px}.cardx{background:#101d30;border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:18px}.cardx h3{margin:0 0 5px}.mini{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:15px 0}.mini div{background:#16263d;border-radius:11px;padding:12px;text-align:center}.mini small{display:block;color:#93a4b8}.mini b{font-size:20px}.top-actions{display:flex;gap:10px;flex-wrap:wrap}@media(max-width:800px){.stats{grid-template-columns:repeat(2,1fr)}.hero{display:block}}
</style>
</head>
<body>
<div class="ga">
<div class="hero">
<div><h1>General Administration</h1><p class="muted mb-0">Manage every school using the platform from one account.</p></div>
<div class="top-actions">
<a class="btn btn-success" href="schools.php">＋ Manage Schools</a>
<a class="btn btn-info" href="add_user.php">＋ Add School Admin</a>
<a class="btn btn-danger" href="logout_session.php">Logout</a>
</div>
</div>

<div class="stats">
<div class="stat"><span class="muted">Schools Using System</span><b><?=number_format($totalSchools)?></b></div>
<div class="stat"><span class="muted">Total Students</span><b><?=number_format($totalStudents)?></b></div>
<div class="stat"><span class="muted">Total Teachers</span><b><?=number_format($totalTeachers)?></b></div>
<div class="stat"><span class="muted">School Admins</span><b><?=number_format($totalAdmins)?></b></div>
</div>

<h3 class="mb-3">School Overview</h3>
<div class="cards">
<?php foreach($schools as $school):
$code=$conn->real_escape_string($school['school_code']);
$students=gaScalar("SELECT COUNT(*) FROM students WHERE school_scope='$code'");
$teachers=gaScalar("SELECT COUNT(*) FROM teachers WHERE school_scope='$code'");
$users=gaScalar("SELECT COUNT(*) FROM users WHERE school_scope='$code'");
?>
<div class="cardx">
<h3><?=htmlspecialchars($school['school_name'])?></h3>
<div class="muted"><?=htmlspecialchars($school['school_code'])?> · <?=htmlspecialchars($school['status'])?></div>
<div class="mini">
<div><small>Students</small><b><?=number_format($students)?></b></div>
<div><small>Teachers</small><b><?=number_format($teachers)?></b></div>
<div><small>Users</small><b><?=number_format($users)?></b></div>
</div>
<a class="btn btn-outline-light btn-sm" href="add_user.php">Add School Admin</a>
</div>
<?php endforeach; ?>
</div>
</div>
</body>
</html>
