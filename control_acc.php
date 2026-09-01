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

$switchLabels = [
    'st_stat' => 'Student Logins',
    't_stat'  => 'Teacher Logins',
    'm_stat'  => 'Mark Entry (Teachers)',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $targetSchool !== '') {
    $school = getSchoolByCode($targetSchool);

    if (isset($_POST['toggle_switch']) && $school) {
        $posName = (string)$_POST['toggle_switch'];
        if (!isset($switchLabels[$posName])) {
            $errors[] = 'Unknown switch.';
        } else {
            $state = getSwitchState($posName, $targetSchool);
            if ($state) {
                DeleteData('mark_con', $state['id']);
                $messages[] = $switchLabels[$posName] . ' re-enabled for ' . htmlspecialchars($school['school_name']) . '.';
            } else {
                if (insertAccCon($posName, $targetSchool)) {
                    $messages[] = $switchLabels[$posName] . ' blocked for ' . htmlspecialchars($school['school_name']) . '.';
                } else {
                    $errors[] = 'Could not change that switch. Please try again.';
                }
            }
        }
    }

    if (isset($_POST['ban_student_id'])) {
        $result = insertAccConById($_POST['ban_student_id']);
        if ($result === true) {
            $messages[] = 'Student banned from further access.';
        } else {
            $errors[] = (string)$result;
        }
    }

    if (isset($_POST['unban_id'])) {
        DeleteData('acc_con', $_POST['unban_id']);
        $messages[] = 'Student access restored.';
    }
}

$switchStates = [];
if ($targetSchool !== '') {
    foreach ($switchLabels as $key => $label) {
        $switchStates[$key] = getSwitchState($key, $targetSchool);
    }
}
$bannedStudents = $targetSchool !== '' ? getBannedStudentsDetailed($targetSchool) : null;

include "admin_header.php";
?>
<style>
:root{--bg:#07111f;--card:#101d30;--card2:#16263d;--line:rgba(255,255,255,.08);--text:#e8f0f8;--muted:#93a4b8;--accent:#38bdf8;--good:#4ade80;--bad:#f87171}
.ctl-wrap{padding:10px 5px 30px;color:var(--text)}
.ctl-card{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:22px;margin-bottom:16px}
.ctl-card h2{margin:0 0 6px;font-size:20px}
.ctl-card p{margin:0 0 15px;color:var(--muted)}
.ctl-card select,.ctl-card input{background:#0f172a!important;color:#fff!important;border:1px solid #334155!important;border-radius:8px}
.notice{padding:12px 15px;border-radius:8px;margin:10px 0}
.ok{background:#14532d;color:#dcfce7}
.bad{background:#7f1d1d;color:#fee2e2}
.hint{background:#172554;color:#dbeafe}
.switch-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px}
.switch-box{background:var(--card2);border:1px solid var(--line);border-radius:14px;padding:16px;text-align:center}
.switch-box h4{margin:0 0 10px;font-size:15px}
.switch-state{display:inline-block;padding:5px 14px;border-radius:999px;font-size:13px;font-weight:700;margin-bottom:12px}
.state-on{background:#14532d;color:#dcfce7}
.state-off{background:#7f1d1d;color:#fee2e2}
.ban-table{width:100%;border-collapse:collapse}
.ban-table th,.ban-table td{padding:12px;border-bottom:1px solid var(--line);text-align:center}
.ban-table th{color:#93c5fd}
</style>

<div class="ctl-wrap">
    <div class="ctl-card">
        <h2>Control — Login &amp; Access Switches</h2>
        <p>Block logins for an entire group, freeze mark entry, or ban one specific student. These take effect immediately for the school selected below.</p>

        <?php foreach ($messages as $m): ?><div class="notice ok"><?= $m ?></div><?php endforeach; ?>
        <?php foreach ($errors as $e): ?><div class="notice bad"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>

        <?php if (canSeeAllSchools()): ?>
            <form method="GET">
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
        <div class="ctl-card"><div class="notice bad">Select a school above to manage its access switches.</div></div>
    <?php else: ?>
        <div class="ctl-card">
            <h2>System-Wide Switches</h2>
            <p>Turning one of these on immediately signs out / blocks everyone it applies to at their next page load — no need to touch individual accounts.</p>
            <div class="switch-grid">
                <?php foreach ($switchLabels as $key => $label): $blocked = $switchStates[$key] !== null; ?>
                <div class="switch-box">
                    <h4><?= htmlspecialchars($label) ?></h4>
                    <div><span class="switch-state <?= $blocked ? 'state-off' : 'state-on' ?>"><?= $blocked ? 'Blocked' : 'Allowed' ?></span></div>
                    <form method="POST" onsubmit="return confirm('<?= $blocked ? 'Allow ' . htmlspecialchars($label, ENT_QUOTES) . ' again?' : 'Block ' . htmlspecialchars($label, ENT_QUOTES) . ' now? Affected accounts will be signed out at their next page load.' ?>');">
                        <input type="hidden" name="school_scope" value="<?= htmlspecialchars($targetSchool, ENT_QUOTES) ?>">
                        <input type="hidden" name="toggle_switch" value="<?= htmlspecialchars($key, ENT_QUOTES) ?>">
                        <button type="submit" class="btn btn-sm <?= $blocked ? 'btn-success' : 'btn-danger' ?>"><?= $blocked ? 'Allow Again' : 'Block Now' ?></button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="ctl-card">
            <h2>Ban an Individual Student</h2>
            <p>Blocks just one student's continued access — everyone else is unaffected. Find their ID on the Students Data page.</p>
            <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
                <input type="hidden" name="school_scope" value="<?= htmlspecialchars($targetSchool, ENT_QUOTES) ?>">
                <div>
                    <label><b>Student ID</b></label>
                    <input class="form-control" type="number" name="ban_student_id" placeholder="e.g. 42" required style="max-width:200px">
                </div>
                <button type="submit" class="btn btn-danger">Ban Student</button>
            </form>

            <table class="ban-table" style="margin-top:20px">
                <thead><tr><th>#</th><th>Student</th><th>Class</th><th>ID</th><th>Action</th></tr></thead>
                <tbody>
                <?php $i = 0; if ($bannedStudents && $bannedStudents->num_rows > 0): while ($row = $bannedStudents->fetch_assoc()): $i++; ?>
                    <tr>
                        <td><?= $i ?></td>
                        <td><?= $row['st_name'] ? htmlspecialchars($row['st_name']) : '<span style="color:var(--muted)">Student not found (deleted?)</span>' ?></td>
                        <td><?= $row['st_class'] ? htmlspecialchars($row['st_class'] . '-' . $row['st_group']) : '—' ?></td>
                        <td><?= (int)$row['acc_id'] ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Restore access for this student?');" style="display:inline">
                                <input type="hidden" name="school_scope" value="<?= htmlspecialchars($targetSchool, ENT_QUOTES) ?>">
                                <input type="hidden" name="unban_id" value="<?= (int)$row['ban_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success">Unban</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="5" style="color:var(--muted)">No students are currently banned.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php include "admin_footer.php"; ?>
