<?php
ob_start();
include "admin_header.php";
if (!isGeneralAdmin()) { http_response_code(403); echo '<div class="alert alert-danger">Only General Administration can manage grades.</div>'; include "admin_footer.php"; exit; }

$code = strtoupper(trim($_GET['school'] ?? $_POST['school'] ?? ''));
$school = getSchoolByCode($code);
if (!$school) { die('Unknown school.'); }
$sid = (int)$school['id'];

$msg = ''; $err = '';
$DEFAULT_GRADES = ['Kids','1','2','3','4','5','6','7','8','9','10','11','12'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['add_grade'])) {
        $name = trim($_POST['grade_name'] ?? '');
        $order = (int)($_POST['grade_order'] ?? 0);
        if ($name === '') {
            $err = 'Grade name is required.';
        } else {
            $dup = $conn->query("SELECT id FROM school_grades WHERE school_id=$sid AND grade_name='" . $conn->real_escape_string($name) . "' LIMIT 1");
            if ($dup && $dup->num_rows > 0) {
                $err = 'That grade already exists for this school.';
            } else {
                $stmt = $conn->prepare("INSERT INTO school_grades(school_id,grade_name,grade_order,status) VALUES(?,?,?,'active')");
                $stmt->bind_param('isi', $sid, $name, $order);
                if ($stmt->execute()) {
                    $msg = "Grade \"$name\" added.";
                } else {
                    $err = $stmt->error;
                }
                $stmt->close();
            }
        }
    }

    if (isset($_POST['seed_defaults'])) {
        $existing = [];
        $r = $conn->query("SELECT grade_name FROM school_grades WHERE school_id=$sid");
        if ($r) while ($row = $r->fetch_assoc()) $existing[] = strtolower(trim($row['grade_name']));
        $stmt = $conn->prepare("INSERT INTO school_grades(school_id,grade_name,grade_order,status) VALUES(?,?,?,'active')");
        $added = 0;
        foreach ($DEFAULT_GRADES as $i => $g) {
            if (in_array(strtolower($g), $existing, true)) continue;
            $order = $i;
            $stmt->bind_param('isi', $sid, $g, $order);
            $stmt->execute();
            $added++;
        }
        $stmt->close();
        $msg = $added > 0 ? "Added $added missing default grade(s) (Kids, 1–12)." : "This school already has every default grade.";
    }
}

if (isset($_GET['toggle'])) {
    $gid = (int)$_GET['toggle'];
    $conn->query("UPDATE school_grades SET status=IF(status='active','inactive','active') WHERE id=$gid AND school_id=$sid");
    header("Location: grades.php?school=" . urlencode($code));
    exit();
}

$grades = getSchoolGrades($code, false);
?>
<style>
html,body{overflow:hidden;height:100%;margin:0}
.gr-wrap{padding:10px 5px 16px;color:#e2e8f0;height:100vh;box-sizing:border-box;overflow-y:auto}
.gr-card{background:#111f33;border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:20px;margin-bottom:16px}
.gr-grid{display:grid;grid-template-columns:2fr 1fr auto;gap:12px;align-items:end}
.gr-card input{background:#0f172a!important;color:#fff!important;border:1px solid #334155!important}
.gr-table-scroll{max-height:44vh;overflow-y:auto}
.gr-table{width:100%;border-collapse:collapse}
.gr-table thead th{position:sticky;top:0;background:#111f33;z-index:1}
.gr-table th,.gr-table td{padding:10px;border-bottom:1px solid rgba(255,255,255,.08);text-align:center}
.gr-table th{color:#93c5fd}
.badge-on{background:#166534;padding:5px 10px;border-radius:999px}
.badge-off{background:#7f1d1d;padding:5px 10px;border-radius:999px}
@media(max-width:600px){.gr-grid{grid-template-columns:1fr}}
</style>
<div class="gr-wrap">
<div class="gr-card">
    <h2>Grades — <?=htmlspecialchars($school['school_name'])?> (<?=htmlspecialchars($code)?>)</h2>
    <p>Students, teachers, and imports can only use a class that's listed here as active for this school.</p>
    <?php if ($msg) echo '<div class="alert alert-success">'.htmlspecialchars($msg).'</div>'; ?>
    <?php if ($err) echo '<div class="alert alert-danger">'.htmlspecialchars($err).'</div>'; ?>

    <?php if (empty($grades)): ?>
        <div class="alert alert-warning">
            This school has <b>no grades at all</b> yet — that's why adding a student or teacher for it fails with
            "The selected school does not allow this class." Click below to add the standard set in one click,
            or add grades manually below.
        </div>
        <form method="post" style="margin-bottom:16px">
            <input type="hidden" name="school" value="<?=htmlspecialchars($code)?>">
            <button class="btn btn-success" name="seed_defaults" type="submit">＋ Add Default Grades (Kids, 1–12)</button>
        </form>
    <?php else: ?>
        <form method="post" style="margin-bottom:16px">
            <input type="hidden" name="school" value="<?=htmlspecialchars($code)?>">
            <button class="btn btn-outline-light btn-sm" name="seed_defaults" type="submit">＋ Add Any Missing Default Grades (Kids, 1–12)</button>
        </form>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="school" value="<?=htmlspecialchars($code)?>">
        <div class="gr-grid">
            <div><label>Grade Name</label><input class="form-control" name="grade_name" placeholder="e.g. 13 or Prep" required></div>
            <div><label>Order</label><input class="form-control" type="number" name="grade_order" value="<?=count($grades)?>"></div>
            <button class="btn btn-success" name="add_grade" type="submit">＋ Add Grade</button>
        </div>
    </form>
</div>

<div class="gr-card">
    <h3>Existing Grades (<?=count($grades)?>)</h3>
    <?php if (empty($grades)): ?>
        <p style="color:#93a4b8">No grades yet.</p>
    <?php else: ?>
    <div class="gr-table-scroll">
    <table class="gr-table">
        <thead><tr><th>#</th><th>Grade</th><th>Order</th><th>Standard Price</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($grades as $i => $g): ?>
        <tr>
            <td><?=$i+1?></td>
            <td><?=htmlspecialchars($g['grade_name'])?></td>
            <td><?=(int)$g['grade_order']?></td>
            <td><?=number_format((float)$g['standard_price'])?></td>
            <td><span class="<?=$g['status']==='active'?'badge-on':'badge-off'?>"><?=htmlspecialchars($g['status'])?></span></td>
            <td><a class="btn btn-sm btn-warning" href="grades.php?school=<?=urlencode($code)?>&toggle=<?=$g['id']?>"><?=$g['status']==='active'?'Disable':'Enable'?></a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
</div>
<?php include "admin_footer.php"; ?>
