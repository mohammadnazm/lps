<?php
require_once __DIR__ . '/db_connection.php';

if (!isset($_SESSION['loginadmin']) || $_SESSION['loginadmin'] !== true) {
    header('Location: logout_session.php');
    exit;
}

$messages = [];
$errors = [];

if (canSeeAllSchools()) {
    $targetSchool = strtoupper(trim((string)($_POST['school_scope'] ?? $_GET['school_scope'] ?? '')));
    if ($targetSchool !== '' && !getSchoolByCode($targetSchool)) $targetSchool = '';
} else {
    $targetSchool = currentSchoolScope();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_prices']) && $targetSchool !== '') {
    $school = getSchoolByCode($targetSchool);
    if (!$school) {
        $errors[] = 'Unknown school.';
    } else {
        $stmt = $conn->prepare('UPDATE school_grades SET standard_price=? WHERE id=? AND school_id=?');
        $updated = 0;
        foreach (($_POST['price'] ?? []) as $gradeId => $rawPrice) {
            $gradeId = (int)$gradeId;
            // Accept plain numbers only; anything else (blank, letters) is treated as "not set".
            $clean = preg_replace('/[^0-9.]/', '', (string)$rawPrice);
            $price = $clean === '' ? 0 : round((float)$clean, 2);
            $stmt->bind_param('dii', $price, $gradeId, $school['id']);
            if ($stmt->execute() && $stmt->affected_rows >= 0) $updated++;
        }
        $stmt->close();
        $messages[] = "Prices saved for " . htmlspecialchars($school['school_name']) . '.';
    }
}

$grades = $targetSchool !== '' ? getSchoolGrades($targetSchool) : [];

include "admin_header.php";
?>
<style>
.cp-wrap{padding:10px 5px 30px;color:#e2e8f0}
.cp-card{background:#111f33;border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:22px;margin-bottom:16px}
.cp-card h2{margin:0 0 6px;font-size:20px}
.cp-card p{margin:0 0 15px;color:#93a4b8}
.cp-card select,.cp-card input{background:#0f172a!important;color:#fff!important;border:1px solid #334155!important;border-radius:8px}
.cp-table{width:100%;border-collapse:collapse}
.cp-table th,.cp-table td{padding:12px;border-bottom:1px solid rgba(255,255,255,.08);text-align:center}
.cp-table th{color:#93c5fd}
.cp-table input{width:150px;padding:8px;text-align:center}
.notice{padding:12px 15px;border-radius:8px;margin:10px 0}
.ok{background:#14532d;color:#dcfce7}
.bad{background:#7f1d1d;color:#fee2e2}
</style>
<div class="cp-wrap">
    <div class="cp-card">
        <h2>Class Prices</h2>
        <p>Set the normal (original) price for each grade. The dashboard uses this to show how many students are paying less than that — a discount.</p>

        <?php foreach ($messages as $m): ?><div class="notice ok"><?= htmlspecialchars($m) ?></div><?php endforeach; ?>
        <?php foreach ($errors as $e): ?><div class="notice bad"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>

        <?php if (canSeeAllSchools()): ?>
            <form method="GET" style="margin-bottom:15px">
                <label><b>School</b></label>
                <select name="school_scope" class="form-control" style="max-width:400px" onchange="this.form.submit()">
                    <option value="" <?= $targetSchool === '' ? 'selected' : '' ?> disabled>Select School</option>
                    <?php foreach (getSchools(true) as $school): ?>
                        <option value="<?= htmlspecialchars($school['school_code'], ENT_QUOTES) ?>" <?= $targetSchool === $school['school_code'] ? 'selected' : '' ?>><?= htmlspecialchars($school['school_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>

        <?php if ($targetSchool === ''): ?>
            <div class="notice bad">Select a school above to manage its class prices.</div>
        <?php elseif (empty($grades)): ?>
            <div class="notice bad">This school has no active grades yet.</div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="school_scope" value="<?= htmlspecialchars($targetSchool, ENT_QUOTES) ?>">
                <table class="cp-table">
                    <thead><tr><th>Grade</th><th>Standard Price</th></tr></thead>
                    <tbody>
                        <?php foreach ($grades as $g): ?>
                        <tr>
                            <td><?= htmlspecialchars($g['grade_name']) ?></td>
                            <td>
                                <input type="text" inputmode="decimal" name="price[<?= (int)$g['id'] ?>]"
                                       value="<?= (float)$g['standard_price'] > 0 ? htmlspecialchars((string)(0 + $g['standard_price'])) : '' ?>"
                                       placeholder="Not set">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" name="save_prices" class="btn btn-success" style="margin-top:15px">Save Prices</button>
                <p style="margin-top:10px;color:#93a4b8;font-size:13px">Leave a grade blank to treat it as "not set" — students in that grade will not be counted as discounted until you set a price.</p>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php include "admin_footer.php"; ?>
