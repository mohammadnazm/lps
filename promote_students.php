<?php
require_once __DIR__ . '/db_connection.php';

if (!isset($_SESSION['loginadmin']) || $_SESSION['loginadmin'] !== true) {
    header('Location: logout_session.php');
    exit;
}

$messages = [];
$errors = [];
$results = null;

if (canSeeAllSchools()) {
    $targetSchool = strtoupper(trim((string)($_POST['school_scope'] ?? $_GET['school_scope'] ?? '')));
    if ($targetSchool !== '' && !getSchoolByCode($targetSchool)) $targetSchool = '';
} else {
    $targetSchool = currentSchoolScope();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_promote']) && $targetSchool !== '') {
    $school = getSchoolByCode($targetSchool);
    $typedConfirm = strtoupper(trim((string)($_POST['confirm_school_code'] ?? '')));
    if (!$school) {
        $errors[] = 'Unknown school.';
    } elseif ($typedConfirm !== $school['school_code']) {
        $errors[] = 'The school code you typed did not match "' . htmlspecialchars($school['school_code']) . '". Nothing was changed.';
    } else {
        $results = promoteSchoolStudents($targetSchool);
        if ($results) {
            $messages[] = 'Promotion complete for ' . htmlspecialchars($school['school_name']) . '.';
        } else {
            $errors[] = 'Promotion failed and was rolled back — nothing was changed. Please try again.';
        }
    }
}

$preview = $targetSchool !== '' ? previewPromotion($targetSchool) : [];

include "admin_header.php";
?>
<style>
.pr-wrap{padding:10px 5px 30px;color:#e2e8f0}
.pr-card{background:#111f33;border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:22px;margin-bottom:16px}
.pr-card h2{margin:0 0 6px;font-size:20px}
.pr-card p{margin:0 0 15px;color:#93a4b8}
.pr-card select,.pr-card input{background:#0f172a!important;color:#fff!important;border:1px solid #334155!important;border-radius:8px}
.pr-table{width:100%;border-collapse:collapse}
.pr-table th,.pr-table td{padding:12px;border-bottom:1px solid rgba(255,255,255,.08);text-align:center}
.pr-table th{color:#93c5fd}
.arrow{color:#60a5fa;font-weight:700}
.grad-tag{color:#fbbf24;font-weight:700}
.notice{padding:12px 15px;border-radius:8px;margin:10px 0}
.ok{background:#14532d;color:#dcfce7}
.bad{background:#7f1d1d;color:#fee2e2}
.hint{background:#172554;color:#dbeafe}
.danger-card{border:2px solid #7f1d1d;background:#2a1414}
.danger-card h2{color:#fca5a5}
#promote-btn:disabled{opacity:.5;cursor:not-allowed}
</style>

<div class="pr-wrap">
    <div class="pr-card">
        <h2>Promote Students to Next Grade</h2>
        <p>For the new school year: every active student moves up one grade (e.g. 10 → 11, 11 → 12). Students already in the school's highest grade are marked <b>Graduated</b> instead of moved further. Attendance and mark history are untouched.</p>

        <?php foreach ($messages as $m): ?><div class="notice ok"><?= $m ?></div><?php endforeach; ?>
        <?php foreach ($errors as $e): ?><div class="notice bad"><?= $e ?></div><?php endforeach; ?>

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
        <?php else: ?>
            <div class="notice hint">School: <b><?= htmlspecialchars(schoolName(currentSchoolScope())) ?></b></div>
        <?php endif; ?>
    </div>

    <?php if ($targetSchool === ''): ?>
        <div class="pr-card"><div class="notice bad">Select a school above to see its promotion preview.</div></div>
    <?php elseif (empty($preview)): ?>
        <div class="pr-card"><div class="notice bad">This school has no active grades configured yet.</div></div>
    <?php else: ?>
        <div class="pr-card">
            <h2><?= $results ? 'What Changed' : 'Preview — Nothing Has Been Changed Yet' ?></h2>
            <table class="pr-table">
                <thead><tr><th>Grade</th><th></th><th>Becomes</th><th><?= $results ? 'Students Moved' : 'Active Students Now' ?></th></tr></thead>
                <tbody>
                <?php
                $rows = $results ?? $preview;
                $totalAffected = 0;
                foreach ($rows as $row):
                    $totalAffected += $row['count'];
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['grade_name']) ?></td>
                        <td class="arrow">→</td>
                        <td><?= $row['next_grade_name'] !== null ? htmlspecialchars($row['next_grade_name']) : '<span class="grad-tag">Graduated</span>' ?></td>
                        <td><?= number_format($row['count']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p style="margin-top:10px;color:#93a4b8;font-size:13px">Total active students affected: <b><?= number_format($totalAffected) ?></b>. Prices are left exactly as they are — promotion only changes grade (and status, for graduates).</p>
        </div>

        <?php if (!$results): ?>
        <div class="pr-card danger-card">
            <h2>⚠ Run the Promotion</h2>
            <p>This changes every row shown above in one go. It is not automatically reversible — export an Excel backup from the Archive page first if you want an easy way to restore the old grades.</p>
            <form method="POST" onsubmit="return confirm('Promote every active student for this school as shown above? This cannot be undone automatically.');">
                <input type="hidden" name="school_scope" value="<?= htmlspecialchars($targetSchool, ENT_QUOTES) ?>">
                <label><b>Type the school code (<?= htmlspecialchars($targetSchool) ?>) to confirm</b></label>
                <input class="form-control" type="text" id="confirm-school-code" name="confirm_school_code" placeholder="Type the exact school code" style="max-width:400px;margin:8px 0 15px" autocomplete="off" required>
                <button type="submit" name="confirm_promote" id="promote-btn" class="btn btn-danger" disabled>Promote All Students Now</button>
            </form>
        </div>
        <script>
        (function(){
            var input = document.getElementById('confirm-school-code');
            var btn = document.getElementById('promote-btn');
            var expected = <?= json_encode($targetSchool) ?>;
            function refresh(){ btn.disabled = (input.value || '').trim().toUpperCase() !== expected; }
            input.addEventListener('input', refresh);
            refresh();
        })();
        </script>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php include "admin_footer.php"; ?>
