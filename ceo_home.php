<?php
include "ceo_header.php";
function ceoScalar($sql){global $conn;$r=$conn->query($sql);if(!$r)return 0;$x=$r->fetch_row();return (int)($x[0]??0);}

$scope = currentSchoolScope();
$isGeneral = canSeeAllSchools();
$today = date('Y-m-d');
?>
<style>
:root{--bg:#07111f;--card:#101d30;--card2:#16263d;--line:rgba(255,255,255,.08);--text:#e8f0f8;--muted:#93a4b8;--accent:#38bdf8;--good:#4ade80;--warn:#fbbf24;--male:#60a5fa;--female:#f472b6}
.admin-page{padding:10px 5px 30px;color:var(--text)}
.hero{background:linear-gradient(135deg,#13263d,#0b1626);border:1px solid var(--line);border-radius:22px;padding:24px;display:flex;justify-content:space-between;gap:20px;align-items:center;flex-wrap:wrap}
.hero h1{font-size:26px;margin:0 0 6px;font-weight:800}
.hero p{margin:0;color:var(--muted)}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:18px 0}
.stat{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:18px}
.stat .label{color:var(--muted);font-size:13px}
.stat .num{font-size:28px;font-weight:800;margin-top:8px}
.stat.good .num{color:var(--good)}
.stat.warn .num{color:var(--warn)}
.section-title{font-size:19px;font-weight:800;margin:25px 0 12px;display:flex;align-items:center;justify-content:space-between}
.section-title a{font-size:13px;font-weight:600;color:var(--accent);text-decoration:none}

/* General view: school rollup */
.school-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px}
.school-card{background:var(--card);border:1px solid var(--line);border-radius:20px;padding:18px}
.school-card h3{margin:0 0 4px;font-size:18px}
.school-card p{margin:0 0 14px;color:var(--muted);font-size:13px}
.school-card .big{font-size:30px;font-weight:800}
.school-card .big-label{color:var(--muted);font-size:12px}

/* School view: gender + grade breakdown */
.gender-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px}
.gender-card{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:18px;display:flex;align-items:center;gap:14px}
.gender-dot{width:14px;height:14px;border-radius:50%}
.gender-card.male .gender-dot{background:var(--male)}
.gender-card.female .gender-dot{background:var(--female)}
.gender-card .num{font-size:26px;font-weight:800}
.gender-bar{height:10px;border-radius:999px;overflow:hidden;display:flex;margin-top:14px;background:#0b1626}
.gender-bar .male-fill{background:var(--male)}
.gender-bar .female-fill{background:var(--female)}

.grade-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}
.grade{background:var(--card2);border:1px solid var(--line);border-radius:12px;padding:12px;text-align:center}
.grade-grad{background:#2a1e0a;border-color:#78350f}
.grade-grad span{color:#fbbf24}
.grade span{display:block;color:var(--muted);font-size:12px}
.grade b{font-size:19px}
.grade .disc{display:block;font-size:11px;color:var(--warn);margin-top:4px;font-weight:700}
.grade .disc.zero{color:var(--muted);font-weight:400}
.grade .mf{display:flex;justify-content:center;gap:8px;margin-top:6px;font-size:11px}
.grade .mf .m{color:var(--male);font-weight:700}
.grade .mf .f{color:var(--female);font-weight:700}
.no-price-note{margin-top:10px;font-size:12px;color:var(--muted)}
@media(max-width:1000px){.stats{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){.gender-row{grid-template-columns:1fr}}
</style>
<div class="admin-page">

<?php if ($isGeneral): ?>
    <?php
    // ============== GENERAL (ALL-schools) VIEW: schools + per-school totals only ==============
    $schools = getSchools(false);
    $totalStudents = ceoScalar("SELECT COUNT(*) FROM students WHERE st_statue='بەردەوام'");
    $totalTeachers = ceoScalar("SELECT COUNT(*) FROM teachers");
    $totalAttendance = ceoScalar("SELECT COUNT(DISTINCT student_id) FROM attendance WHERE date='$today'");
    ?>
    <div class="hero">
        <div><h1>All Schools</h1><p>Every school on the platform, at a glance.</p></div>
        <div class="badge bg-info text-dark p-2">Scope: ALL</div>
    </div>

    <div class="stats">
        <div class="stat"><div class="label">Schools</div><div class="num"><?=number_format(count($schools))?></div></div>
        <div class="stat"><div class="label">Active Students (All Schools)</div><div class="num"><?=number_format($totalStudents)?></div></div>
        <div class="stat"><div class="label">Teachers (All Schools)</div><div class="num"><?=number_format($totalTeachers)?></div></div>
        <div class="stat good"><div class="label">Attendance Today (All Schools)</div><div class="num"><?=number_format($totalAttendance)?></div></div>
    </div>

    <div class="section-title">Students Per School</div>
    <div class="school-grid">
        <?php foreach ($schools as $s):
            $code = $s['school_code'];
            $count = ceoScalar("SELECT COUNT(*) FROM students WHERE school_scope='" . $conn->real_escape_string($code) . "' AND st_statue='بەردەوام'");
        ?>
        <div class="school-card">
            <h3><?=htmlspecialchars($s['school_name'])?></h3>
            <p><?=htmlspecialchars($code)?> • <?=htmlspecialchars($s['status'])?></p>
            <div class="big"><?=number_format($count)?></div>
            <div class="big-label">Active Students</div>
        </div>
        <?php endforeach; ?>
    </div>

<?php else: ?>
    <?php
    // ============== SINGLE-SCHOOL VIEW: this school's numbers only ==============
    $school = getSchoolByCode($scope);
    $schoolNameLabel = $school['school_name'] ?? $scope;

    $totalStudents = ceoScalar("SELECT COUNT(*) FROM students WHERE school_scope='" . $conn->real_escape_string($scope) . "' AND st_statue='بەردەوام'");
    $totalTeachers = ceoScalar("SELECT COUNT(*) FROM teachers WHERE school_scope='" . $conn->real_escape_string($scope) . "'");
    $totalAttendance = ceoScalar("SELECT COUNT(DISTINCT a.student_id) FROM attendance a INNER JOIN students s ON s.id=a.student_id WHERE a.date='$today' AND s.school_scope='" . $conn->real_escape_string($scope) . "'");

    $genders = getSchoolGenderCounts($scope);
    $genderTotal = max(1, $genders['Male'] + $genders['Female'] + $genders['Other']);
    $malePct = round($genders['Male'] / $genderTotal * 100);
    $femalePct = round($genders['Female'] / $genderTotal * 100);

    $gradeStats = getSchoolGradeStats($scope);
    $totalDiscounted = 0; $anyPriceConfigured = false;
    foreach ($gradeStats as $row) {
        $totalDiscounted += $row['discounted'];
        if ((float)$row['grade']['standard_price'] > 0) $anyPriceConfigured = true;
    }
    $graduateStats = getGraduateStats($scope);
    ?>
    <div class="hero">
        <div><h1><?=htmlspecialchars($schoolNameLabel)?></h1><p>Your school's students, at a glance.</p></div>
        <div class="badge bg-info text-dark p-2">Scope: <?=htmlspecialchars($scope)?></div>
    </div>

    <div class="stats">
        <div class="stat"><div class="label">Active Students</div><div class="num"><?=number_format($totalStudents)?></div></div>
        <div class="stat"><div class="label">Teachers</div><div class="num"><?=number_format($totalTeachers)?></div></div>
        <div class="stat good"><div class="label">Attendance Today</div><div class="num"><?=number_format($totalAttendance)?></div></div>
        <div class="stat warn"><div class="label">Discounted Students</div><div class="num"><?=number_format($totalDiscounted)?></div></div>
    </div>

    <div class="section-title">Students by Gender</div>
    <div class="gender-row">
        <div class="gender-card male"><div class="gender-dot"></div><div><div class="num"><?=number_format($genders['Male'])?></div><div class="label" style="color:var(--muted);font-size:13px">Male (<?=$malePct?>%)</div></div></div>
        <div class="gender-card female"><div class="gender-dot"></div><div><div class="num"><?=number_format($genders['Female'])?></div><div class="label" style="color:var(--muted);font-size:13px">Female (<?=$femalePct?>%)</div></div></div>
    </div>
    <div class="gender-bar">
        <div class="male-fill" style="width:<?=$malePct?>%"></div>
        <div class="female-fill" style="width:<?=$femalePct?>%"></div>
    </div>
    <?php if ($genders['Other'] > 0): ?>
        <p style="color:var(--muted);font-size:12px;margin-top:8px"><?=number_format($genders['Other'])?> student(s) with an unspecified gender value.</p>
    <?php endif; ?>

    <div class="section-title">Students by Class</div>
    <?php if (!$anyPriceConfigured): ?>
    <div class="no-price-note">No class prices are set yet, so discounts show as 0 everywhere.</div>
    <?php endif; ?>
    <div class="grade-grid">
        <?php
        $genderByClass = getSchoolGenderCountsByClass($scope);
        foreach ($gradeStats as $row):
            $g = $row['grade'];
            $priceSet = (float)($g['standard_price'] ?? 0) > 0;
            $gc = $genderByClass[$g['grade_name']] ?? ['Male' => 0, 'Female' => 0];
        ?>
        <div class="grade">
            <span><?=htmlspecialchars($g['grade_name'])?></span>
            <b><?=number_format($row['count'])?></b>
            <div class="mf"><span class="m">♂ <?=number_format($gc['Male'])?></span><span class="f">♀ <?=number_format($gc['Female'])?></span></div>
            <?php if ($priceSet): ?>
                <span class="disc <?=$row['discounted']==0?'zero':''?>"><?=number_format($row['discounted'])?> discounted</span>
            <?php else: ?>
                <span class="disc zero">no price set</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <div class="grade grade-grad">
            <span>🎓 Graduates</span>
            <b><?=number_format($graduateStats['count'])?></b>
            <?php if ($graduateStats['count'] > 0 && $anyPriceConfigured): ?>
                <span class="disc <?=$graduateStats['discounted']==0?'zero':''?>"><?=number_format($graduateStats['discounted'])?> discounted</span>
            <?php elseif ($graduateStats['count'] > 0): ?>
                <span class="disc zero">no price set</span>
            <?php else: ?>
                <span class="disc zero">none yet</span>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

</div>
<?php include "ceo_footer.php"; ?>
