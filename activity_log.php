<?php
include "admin_header.php";

$isGeneral = canSeeAllSchools();
$today = date('Y-m-d');

$selDate = $_GET['date'] ?? $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selDate)) { $selDate = $today; }

$selScope = $isGeneral ? strtoupper(trim($_GET['scope'] ?? 'ALL')) : currentSchoolScope();
if ($isGeneral && $selScope === '') { $selScope = 'ALL'; }

$diagResult = null;
if (isset($_GET['run_test'])) {
    $diagResult = testActivityLogInsert();
}

$activity = getActivityLog($isGeneral ? $selScope : currentSchoolScope(), $selDate, 300);
$dateCounts = getActivityDatesList($isGeneral ? $selScope : currentSchoolScope(), 60);
$activityReady = activityLogTableReady();

$actionMeta = [
    'student_created'  => ['icon' => '➕', 'color' => '#4ade80', 'label' => 'Student Added'],
    'student_updated'  => ['icon' => '✏️', 'color' => '#38bdf8', 'label' => 'Student Updated'],
    'student_deleted'  => ['icon' => '🗑️', 'color' => '#f87171', 'label' => 'Student Deleted'],
    'marks_updated'    => ['icon' => '📊', 'color' => '#fbbf24', 'label' => 'Marks Updated'],
    'diagnostic_test'  => ['icon' => '🔧', 'color' => '#a78bfa', 'label' => 'Diagnostic Test'],
];
?>
<style>
:root{--bg:#07111f;--card:#101d30;--card2:#16263d;--line:rgba(255,255,255,.08);--text:#e8f0f8;--muted:#93a4b8;--accent:#38bdf8}
html,body{background:var(--bg)!important;color:var(--text);height:100%;margin:0;overflow:hidden}
.al-page{padding:10px 5px 16px;height:100vh;box-sizing:border-box;overflow-y:auto}
.al-hero{background:linear-gradient(135deg,#13263d,#0b1626);border:1px solid var(--line);border-radius:22px;padding:22px 24px;margin-bottom:18px}
.al-hero h1{font-size:24px;margin:0 0 4px;font-weight:800}
.al-hero p{margin:0;color:var(--muted);font-size:14px}

.al-layout{display:grid;grid-template-columns:230px 1fr;gap:18px;align-items:start}
@media(max-width:900px){.al-layout{grid-template-columns:1fr}}

.al-dates{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:14px}
.al-dates h6{color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.05em;margin:0 0 10px}
.al-dates form{margin-bottom:10px}
.al-dates input[type=date]{width:100%;background:#0b1626;color:var(--text);border:1px solid #24374f;border-radius:8px;padding:8px}
.al-datelist{max-height:420px;overflow-y:auto;display:flex;flex-direction:column;gap:4px}
.al-datelist a{display:flex;justify-content:space-between;padding:8px 10px;border-radius:8px;color:#cbd5e1;text-decoration:none;font-size:13px}
.al-datelist a:hover{background:rgba(56,189,248,.1)}
.al-datelist a.active{background:var(--accent);color:#062033;font-weight:700}
.al-datelist span.cnt{color:var(--muted);font-size:11.5px}
.al-datelist a.active span.cnt{color:#062033}

.al-scope-form{display:flex;gap:8px;margin-bottom:12px;align-items:center;flex-wrap:wrap}
.al-scope-form select{background:#0b1626;color:var(--text);border:1px solid #24374f;border-radius:8px;padding:8px 10px}

.al-table-card{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:6px;overflow:hidden}
.al-scroll{max-height:52vh;overflow-y:auto}
table.al-table{width:100%;border-collapse:collapse}
.al-table thead th{position:sticky;top:0;background:#16263d;color:#93c5fd;padding:10px;text-align:left;font-size:12.5px;border-bottom:1px solid var(--line);z-index:1}
.al-table tbody td{padding:10px;border-bottom:1px solid var(--line);font-size:13.5px;vertical-align:top}
.al-table tbody tr:hover{background:rgba(56,189,248,.05)}
.al-type{font-weight:700;white-space:nowrap}
.al-summary{color:#cbd5e1;font-size:12.5px;line-height:1.5}
.al-actor{color:var(--muted);font-size:12px;white-space:nowrap}
.al-empty{padding:30px;text-align:center;color:var(--muted)}
.al-diag{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:14px 18px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap}
.al-diag .txt{font-size:13px;color:var(--muted)}
.al-diag .btn-diag{background:#334155;color:#e2e8f0;border:0;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap}
.al-diag-result{width:100%;font-size:13px;padding:10px 12px;border-radius:8px;margin-top:4px}
.al-diag-result.ok{background:rgba(74,222,128,.12);color:#4ade80;border:1px solid rgba(74,222,128,.3)}
.al-diag-result.fail{background:rgba(248,113,113,.12);color:#f87171;border:1px solid rgba(248,113,113,.3)}
</style>

<div class="al-page">
    <div class="al-hero">
        <h1>Activity Log</h1>
        <p>Every student and marks change, day by day.</p>
    </div>

    <div class="al-diag">
        <span class="txt">Not seeing your changes here? Run a quick test to check the log itself is working.</span>
        <a class="btn-diag" href="?date=<?=htmlspecialchars($selDate)?><?= $isGeneral ? '&scope=' . htmlspecialchars($selScope) : '' ?>&run_test=1">Run Diagnostic Test</a>
        <?php if ($diagResult !== null): ?>
            <div class="al-diag-result <?= $diagResult['ok'] ? 'ok' : 'fail' ?>"><?= $diagResult['ok'] ? '✅' : '❌' ?> <?=htmlspecialchars($diagResult['message'])?></div>
        <?php endif; ?>
    </div>

    <div class="al-layout">
        <div class="al-dates">
            <h6>Jump to Date</h6>
            <form method="get">
                <?php if ($isGeneral): ?><input type="hidden" name="scope" value="<?=htmlspecialchars($selScope)?>"><?php endif; ?>
                <input type="date" name="date" value="<?=htmlspecialchars($selDate)?>" max="<?=htmlspecialchars($today)?>" onchange="this.form.submit()">
            </form>
            <div class="al-datelist">
                <?php if (empty($dateCounts)): ?>
                    <span class="cnt">No history yet</span>
                <?php endif; ?>
                <?php foreach ($dateCounts as $dc):
                    $qs = 'date=' . urlencode($dc['d']) . ($isGeneral ? '&scope=' . urlencode($selScope) : '');
                ?>
                <a class="<?= $dc['d'] === $selDate ? 'active' : '' ?>" href="?<?=$qs?>">
                    <span><?=htmlspecialchars(date('M j, Y', strtotime($dc['d'])))?></span>
                    <span class="cnt"><?=(int)$dc['c']?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <?php if ($isGeneral): ?>
            <form method="get" class="al-scope-form">
                <input type="hidden" name="date" value="<?=htmlspecialchars($selDate)?>">
                <label for="al_scope" style="color:var(--muted);font-size:13px">School:</label>
                <select id="al_scope" name="scope" onchange="this.form.submit()">
                    <option value="ALL" <?= $selScope==='ALL'?'selected':'' ?>>All Schools</option>
                    <?php foreach (getSchools(false) as $sc): ?>
                        <option value="<?=htmlspecialchars($sc['school_code'])?>" <?= $selScope===strtoupper($sc['school_code'])?'selected':'' ?>><?=htmlspecialchars($sc['school_name'])?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>

            <div class="al-table-card">
                <?php if (!$activityReady): ?>
                    <div class="al-empty" style="color:#f87171">
                        Activity log isn't set up on this database yet (the <code>activity_log</code> table couldn't be created automatically — this usually means the database user doesn't have CREATE TABLE permission).<br>
                        Ask whoever manages the database to run <code>database/migration_activity_log.sql</code> once, then this page will start filling in.
                    </div>
                <?php elseif (empty($activity)): ?>
                    <div class="al-empty">No activity recorded for <?=htmlspecialchars(date('M j, Y', strtotime($selDate)))?>.</div>
                <?php else: ?>
                <div class="al-scroll">
                <table class="al-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Type</th>
                            <th>Student / Target</th>
                            <?php if ($isGeneral): ?><th>School</th><?php endif; ?>
                            <th>What Changed</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activity as $a):
                            $meta = $actionMeta[$a['action_type']] ?? ['icon' => '•', 'color' => '#93a4b8', 'label' => $a['action_type']];
                        ?>
                        <tr>
                            <td style="white-space:nowrap"><?=htmlspecialchars(date('h:i A', strtotime($a['created_at'])))?></td>
                            <td class="al-type" style="color:<?=$meta['color']?>"><?=$meta['icon']?> <?=htmlspecialchars($meta['label'])?></td>
                            <td><?=htmlspecialchars($a['target_name'])?></td>
                            <?php if ($isGeneral): ?><td><?=htmlspecialchars($a['school_scope'])?></td><?php endif; ?>
                            <td class="al-summary"><?=htmlspecialchars($a['summary'])?></td>
                            <td class="al-actor"><?=htmlspecialchars($a['actor_name'])?><br><?=htmlspecialchars($a['actor_role'])?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include "admin_footer.php"; ?>
