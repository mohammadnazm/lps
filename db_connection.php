<?php
// Safe to include/require this file more than once from the same request
// (some pages require_once it directly and then include a *_header.php file
// that also includes it) — the second call just returns immediately instead
// of redeclaring every function below, which used to be a fatal error.
if (defined('LOZAN_DB_CONNECTION_LOADED')) {
    return;
}
define('LOZAN_DB_CONNECTION_LOADED', true);

require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// This codebase was written before PHP 8.1 changed mysqli's default behavior.
// Since 8.1, a query error throws an exception and crashes the whole page unless
// told otherwise; this app was written entirely around the older behavior, where a
// failed query just returns false and every `if ($result) {...}` check below (and
// in every other file) handles that gracefully. Restore that behavior explicitly
// so a schema hiccup degrades a feature instead of taking down the whole page.
mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) { die("Database connection failed. Please check config.php."); }
$conn->set_charset("utf8mb4");

// Self-installing migration for the Class Prices / discount dashboard feature: add the
// standard_price column the first time it's needed, so this never depends on someone
// remembering to run a .sql file by hand. Safe to run on every request — it only does
// anything the very first time, and never throws even if school_grades doesn't exist yet.
$priceColumnCheck = $conn->query("SHOW COLUMNS FROM school_grades LIKE 'standard_price'");
if ($priceColumnCheck && $priceColumnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE school_grades ADD COLUMN standard_price DECIMAL(12,2) NOT NULL DEFAULT 0");
}
// Same self-installing approach for each school's logo.
$logoColumnCheck = $conn->query("SHOW COLUMNS FROM schools LIKE 'logo'");
if ($logoColumnCheck && $logoColumnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE schools ADD COLUMN logo VARCHAR(255) NULL DEFAULT NULL");
}
// Self-installing table for the Recent Activity feature: every student create/update/delete
// and every marks change gets a row here so admins can see "what changed" per day.
$activityLogCheck = $conn->query("SHOW TABLES LIKE 'activity_log'");
if ($activityLogCheck && $activityLogCheck->num_rows === 0) {
    $activityLogCreated = $conn->query("CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_scope VARCHAR(50) NOT NULL,
        actor_name VARCHAR(150) NOT NULL,
        actor_role VARCHAR(50) NOT NULL,
        action_type VARCHAR(40) NOT NULL,
        target_type VARCHAR(40) NOT NULL,
        target_id INT NULL,
        target_name VARCHAR(190) NOT NULL,
        summary TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_scope_date (school_scope, created_at),
        INDEX idx_date (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!$activityLogCreated) {
        // Don't fail silently: this is the #1 reason Recent Activity ends up permanently
        // empty with no error shown anywhere — most commonly a hosting DB user that isn't
        // granted CREATE TABLE. Logged here so it shows up in logs/php-error.log, and also
        // surfaced on-screen (see activityLogTableReady()) instead of just looking broken.
        error_log('LOZAN activity_log auto-create failed: ' . $conn->error);
    }
}

// Self-installing table for login brute-force protection. If this silently fails
// (no CREATE TABLE privilege), tooManyFailedLogins() below fails open — login still
// works, it just skips the lockout check — so this is lower-severity than activity_log,
// but still worth a log line so it isn't a total mystery. Manual fallback:
// database/migration_login_attempts.sql
$loginAttemptsCheck = $conn->query("SHOW TABLES LIKE 'login_attempts'");
if ($loginAttemptsCheck && $loginAttemptsCheck->num_rows === 0) {
    $loginAttemptsCreated = $conn->query("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(150) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_username_time (username, attempted_at),
        INDEX idx_ip_time (ip_address, attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!$loginAttemptsCreated) {
        error_log('LOZAN login_attempts auto-create failed: ' . $conn->error);
    }
}

/* ======================= LOGIN SECURITY ======================= */

// True once the stored value is already a proper bcrypt/argon hash — vs. a
// legacy plaintext password still sitting in the database from before this
// build. Lets login accept BOTH transparently and self-heal old rows.
function isHashedPassword(string $stored): bool {
    return (bool)preg_match('/^\$(2[aby]|argon2[id]{1,2})\$/', $stored);
}

// Verifies a login password against whatever is stored, and if the match
// came from a legacy plaintext row, immediately re-saves it as a proper
// bcrypt hash — so every account is upgraded automatically the first time
// its owner logs in, with zero manual migration and zero downtime.
function verifyAndUpgradePassword(string $plain, string $stored, string $table, string $idColumn, $id, string $passColumn): bool {
    global $conn;
    if (isHashedPassword($stored)) {
        return password_verify($plain, $stored);
    }
    // Legacy plaintext row: constant-time compare, then upgrade in place on success.
    if ($stored !== '' && hash_equals($stored, $plain)) {
        $newHash = password_hash($plain, PASSWORD_DEFAULT);
        $idSafe = (int)$id;
        $stmt = $conn->prepare("UPDATE `$table` SET `$passColumn` = ? WHERE `$idColumn` = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("si", $newHash, $idSafe);
            $stmt->execute();
            $stmt->close();
        }
        return true;
    }
    return false;
}

// Brute-force lockout: counts recent failed attempts for this username AND
// this IP (whichever is higher) over a sliding window, independent of which
// account type (admin/teacher/student) was being tried.
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

function clientIp(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function tooManyFailedLogins(string $username): bool {
    global $conn;
    $r = $conn->query("SHOW TABLES LIKE 'login_attempts'");
    if (!$r || $r->num_rows === 0) return false; // fail open if the table itself is missing
    $ip = $conn->real_escape_string(clientIp());
    $uname = $conn->real_escape_string($username);
    $mins = LOGIN_LOCKOUT_MINUTES;
    $res = $conn->query("SELECT COUNT(*) c FROM login_attempts WHERE (username='$uname' OR ip_address='$ip') AND attempted_at > (NOW() - INTERVAL $mins MINUTE)");
    $count = $res ? (int)$res->fetch_assoc()['c'] : 0;
    return $count >= LOGIN_MAX_ATTEMPTS;
}

function recordFailedLogin(string $username): void {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO login_attempts (username, ip_address) VALUES (?, ?)");
    if (!$stmt) return;
    $ip = clientIp();
    $stmt->bind_param("ss", $username, $ip);
    $stmt->execute();
    $stmt->close();
}

function clearFailedLogins(string $username): void {
    global $conn;
    $ip = $conn->real_escape_string(clientIp());
    $uname = $conn->real_escape_string($username);
    $conn->query("DELETE FROM login_attempts WHERE username='$uname' OR ip_address='$ip'");
}

// Simple session-bound CSRF token for the login form.
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfCheck(?string $token): bool {
    return !empty($_SESSION['csrf_token']) && !empty($token) && hash_equals($_SESSION['csrf_token'], $token);
}

/* ======================= SECURE FILE UPLOADS ======================= *
 * Every upload in this app used to trust the browser-supplied filename and
 * extension completely, then move_uploaded_file() straight into a public,
 * PHP-executable folder (st_image/, id_data/, teachers_img/) — meaning any
 * account that can add/edit a student or teacher could upload a .php file
 * disguised as a "photo" and have it execute on the server. This helper
 * fixes that: it whitelists the extension, verifies image uploads are
 * actually images (not just named like one), verifies PDF uploads actually
 * start with the PDF magic bytes, and always saves under a fresh random
 * filename so nothing about what's on disk is attacker-controlled.
 * ===================================================================== */
function handleSecureUpload(string $fieldName, string $destDir, array $allowedExt, bool $requireImage = false, int $maxBytes = 8 * 1024 * 1024): ?string {
    if (empty($_FILES[$fieldName]) || ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null; // nothing submitted — caller decides whether that's fine (e.g. edit form, keep existing file)
    }
    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    if (($_FILES[$fieldName]['size'] ?? 0) <= 0 || $_FILES[$fieldName]['size'] > $maxBytes) {
        return null;
    }
    $tmp = $_FILES[$fieldName]['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        return null;
    }
    $ext = strtolower(pathinfo((string)$_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        return null;
    }
    if ($requireImage) {
        if (@getimagesize($tmp) === false) {
            return null; // named like an image, but the content isn't decodable as one
        }
    } elseif ($ext === 'pdf') {
        $head = @file_get_contents($tmp, false, null, 0, 5);
        if ($head !== '%PDF-') {
            return null; // named .pdf, but doesn't start with the real PDF signature
        }
    }
    if (!is_dir($destDir) || !is_writable($destDir)) {
        return null;
    }
    $newName = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!@move_uploaded_file($tmp, rtrim($destDir, '/') . '/' . $newName)) {
        return null;
    }
    return $newName;
}

// Cheap, cached-per-request check so the UI can tell "table missing / no CREATE
// privilege on this host" apart from "table exists, just nothing happened today".
function activityLogTableReady(): bool {
    global $conn;
    static $ready = null;
    if ($ready !== null) return $ready;
    $r = $conn->query("SHOW TABLES LIKE 'activity_log'");
    $ready = ($r && $r->num_rows === 1);
    return $ready;
}

function currentSchoolScope(): string {
    $scope = strtoupper(trim((string)($_SESSION['school_scope'] ?? 'LOZAN')));
    if ($scope === 'ALL') return 'ALL';
    if (!isset($GLOBALS['conn'])) return $scope ?: 'LOZAN';
    $safe = mysqli_real_escape_string($GLOBALS['conn'], $scope);
    $r = $GLOBALS['conn']->query("SELECT school_code FROM schools WHERE school_code='$safe' AND status='active' LIMIT 1");
    return ($r && $r->num_rows === 1) ? $scope : 'LOZAN';
}
function canSeeAllSchools(): bool { return currentSchoolScope()==='ALL'; }
function isGeneralAdmin(): bool { return !empty($_SESSION['general_admin']) || strtoupper((string)($_SESSION['user_role'] ?? '')) === 'GENERALADMIN'; }
function getSchools(bool $activeOnly=true): array {
    global $conn;
    $where = $activeOnly ? " WHERE status='active'" : '';
    $out=[];
    $r=$conn->query("SELECT id,school_code,school_name,status,logo FROM schools$where ORDER BY school_name ASC");
    if($r) while($row=$r->fetch_assoc()) $out[]=$row;
    return $out;
}
function getSchoolByCode(string $code): ?array {
    global $conn;
    $code=strtoupper(trim($code));
    $safe=$conn->real_escape_string($code);
    $r=$conn->query("SELECT id,school_code,school_name,status,logo FROM schools WHERE school_code='$safe' LIMIT 1");
    return ($r && $r->num_rows===1) ? $r->fetch_assoc() : null;
}
function schoolName(string $code): string {
    if($code==='ALL') return 'All Schools';
    $s=getSchoolByCode($code); return $s['school_name'] ?? $code;
}
// Path to use in an <img src="..."> for this school's logo, or the default brand logo
// if the school has none set (or code is 'ALL'/unknown — General Admin's own pages).
function schoolLogoUrl(string $code): string {
    $s = getSchoolByCode($code);
    if ($s && !empty($s['logo']) && is_file(__DIR__ . '/school_logos/' . $s['logo'])) {
        return 'school_logos/' . rawurlencode($s['logo']);
    }
    return 'images/Lozan Logo.png';
}
function getSchoolGrades(string $schoolCode, bool $activeOnly=true): array {
    global $conn;
    $safe=$conn->real_escape_string(strtoupper(trim($schoolCode)));
    $where=$activeOnly ? " AND sg.status='active'" : '';
    $out=[];
    $sql="SELECT sg.id,sg.grade_name,sg.grade_order,sg.status,sg.standard_price FROM school_grades sg INNER JOIN schools s ON s.id=sg.school_id WHERE s.school_code='$safe'$where ORDER BY sg.grade_order ASC, sg.id ASC";
    $r=$conn->query($sql);
    if($r) while($row=$r->fetch_assoc()) $out[]=$row;
    return $out;
}
// The status given to a student who was in the school's highest grade at promotion
// time — they don't move to a "next" grade, they finish. Kept as its own constant so
// every place that needs to recognise a graduate uses the exact same value.
define('GRADUATED_STATUS', 'Graduated');

// Builds the ordered "ladder" of active grades for a school (lowest to highest) and,
// for each one, which grade an active student in it would move to next — or null for
// the top grade, meaning "graduate" instead of "move up".
// Returns a list of ['grade_name'=>..., 'next_grade_name'=>string|null].
function getPromotionLadder(string $schoolCode): array {
    $grades = getSchoolGrades($schoolCode);
    $ladder = [];
    foreach ($grades as $i => $g) {
        $next = $grades[$i + 1]['grade_name'] ?? null;
        $ladder[] = ['grade_name' => $g['grade_name'], 'next_grade_name' => $next];
    }
    return $ladder;
}

// Dry run: how many active students are in each grade right now, and what would
// happen to them (move to X, or graduate). Nothing in the database is changed.
function previewPromotion(string $schoolCode): array {
    global $conn;
    $safe = $conn->real_escape_string(strtoupper(trim($schoolCode)));
    $rows = [];
    foreach (getPromotionLadder($schoolCode) as $step) {
        $cls = $conn->real_escape_string($step['grade_name']);
        $count = 0;
        $r = $conn->query("SELECT COUNT(*) AS c FROM students WHERE school_scope='$safe' AND st_class='$cls' AND st_statue='بەردەوام'");
        if ($r) $count = (int)$r->fetch_assoc()['c'];
        $rows[] = [
            'grade_name' => $step['grade_name'],
            'next_grade_name' => $step['next_grade_name'],
            'count' => $count,
        ];
    }
    return $rows;
}

// Actually promotes every active student for one school, one grade at a time, starting
// from the HIGHEST grade and working down. Processing top-down is essential: if grade 1
// were promoted to grade 2 first, that same UPDATE statement's effects would then get
// caught again when grade 2 is processed next, promoting those students twice in one
// run. Working from the top removes that risk — a grade is never touched again after
// its students have already been moved out of it.
// Returns the same per-grade shape as previewPromotion(), with actual affected counts.
function promoteSchoolStudents(string $schoolCode): array {
    global $conn;
    $school = getSchoolByCode($schoolCode);
    if (!$school) return [];
    $safe = $conn->real_escape_string($school['school_code']);
    $ladder = array_reverse(getPromotionLadder($schoolCode));

    $results = [];
    $conn->begin_transaction();
    try {
        foreach ($ladder as $step) {
            $cls = $conn->real_escape_string($step['grade_name']);
            if ($step['next_grade_name'] === null) {
                // Top grade: graduate them instead of moving to a next class.
                $conn->query("UPDATE students SET st_statue='" . GRADUATED_STATUS . "' WHERE school_scope='$safe' AND st_class='$cls' AND st_statue='بەردەوام'");
            } else {
                $next = $conn->real_escape_string($step['next_grade_name']);
                $conn->query("UPDATE students SET st_class='$next' WHERE school_scope='$safe' AND st_class='$cls' AND st_statue='بەردەوام'");
            }
            $results[] = [
                'grade_name' => $step['grade_name'],
                'next_grade_name' => $step['next_grade_name'],
                'count' => $conn->affected_rows,
            ];
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        return [];
    }
    // Return in the same low-to-high order the preview used, for a consistent display.
    return array_reverse($results);
}
// Per-grade student count and "discounted" count (price less than the grade's standard
// price) for one school. A grade with standard_price = 0 is treated as "not configured
// yet" and always reports 0 discounted, since there is nothing to compare against.
//
// This deliberately compares in PHP rather than in SQL (no CAST/JOIN price comparison).
// The search page's per-row "Discount" badge has always computed it this same way and
// is known to match correctly against real data (mixed comma-formatted prices, etc.);
// an equivalent SQL-side comparison was tried here before and did not reliably agree
// with it, so both places now share this one method instead of two different ones.
function getSchoolGradeStats(string $schoolCode): array {
    global $conn;
    $out = [];
    foreach (getSchoolGrades($schoolCode) as $g) {
        $out[$g['grade_name']] = ['grade' => $g, 'count' => 0, 'discounted' => 0];
    }
    $safe = $conn->real_escape_string(strtoupper(trim($schoolCode)));
    $r = $conn->query("SELECT st_class, st_price FROM students WHERE school_scope='$safe' AND st_statue='بەردەوام'");
    if ($r) while ($row = $r->fetch_assoc()) {
        $cls = $row['st_class'];
        if (!isset($out[$cls])) {
            // A class on students that isn't (or is no longer) one of the school's
            // configured grades. Still show it so nobody is hidden from the count.
            $out[$cls] = ['grade' => ['grade_name' => $cls, 'standard_price' => 0], 'count' => 0, 'discounted' => 0];
        }
        $out[$cls]['count']++;
        $standardPrice = (float)$out[$cls]['grade']['standard_price'];
        if ($standardPrice > 0) {
            $price = (float)str_replace([',', ' '], '', (string)$row['st_price']);
            if ($price < $standardPrice) $out[$cls]['discounted']++;
        }
    }
    return array_values($out);
}
// Gender split for one school's active students. Returns ['Male'=>n,'Female'=>n,'Other'=>n]
// so the caller never has to guard against a missing key.
function getSchoolGenderCounts(string $schoolCode): array {
    global $conn;
    $out = ['Male' => 0, 'Female' => 0, 'Other' => 0];
    $safe = $conn->real_escape_string(strtoupper(trim($schoolCode)));
    $r = $conn->query("SELECT st_gender, COUNT(*) AS cnt FROM students WHERE school_scope='$safe' AND st_statue='بەردەوام' GROUP BY st_gender");
    if ($r) while ($row = $r->fetch_assoc()) {
        // Normalise case/whitespace before matching — some records (older manual edits,
        // Excel imports typed as "female"/"FEMALE"/etc.) don't use the exact "Male"/
        // "Female" casing the Add/Edit Student forms always save, and a strict match
        // was silently dropping those students into "Other", undercounting Female.
        $g = ucfirst(strtolower(trim((string)$row['st_gender'])));
        if ($g === 'Male' || $g === 'Female') $out[$g] += (int)$row['cnt'];
        else $out['Other'] += (int)$row['cnt'];
    }
    return $out;
}
// Same Male/Female/Other split as getSchoolGenderCounts(), but broken down per class
// (st_class / "grade_name") so the dashboard can show how many boys and girls are in
// each grade, not just the school-wide total. Keyed by grade_name so the caller can
// look it up alongside getSchoolGradeStats()'s output.
function getSchoolGenderCountsByClass(string $schoolCode): array {
    global $conn;
    $out = [];
    $safe = $conn->real_escape_string(strtoupper(trim($schoolCode)));
    $r = $conn->query("SELECT st_class, st_gender, COUNT(*) AS cnt FROM students WHERE school_scope='$safe' AND st_statue='بەردەوام' GROUP BY st_class, st_gender");
    if ($r) while ($row = $r->fetch_assoc()) {
        $cls = $row['st_class'];
        if (!isset($out[$cls])) $out[$cls] = ['Male' => 0, 'Female' => 0, 'Other' => 0];
        $g = ucfirst(strtolower(trim((string)$row['st_gender'])));
        if ($g === 'Male' || $g === 'Female') $out[$cls][$g] += (int)$row['cnt'];
        else $out[$cls]['Other'] += (int)$row['cnt'];
    }
    return $out;
}
// Graduated students for one school: how many, and how many of them graduated with a
// discount (price below the standard price of the grade they graduated from — a
// graduate's st_class is left as their final grade by the promotion tool, so that
// grade's price is still the right one to compare against). Uses the exact same
// PHP-side comparison as getSchoolGradeStats() / the Discount badge, not a SQL-side
// comparison, so this always agrees with what's shown everywhere else.
function getGraduateStats(string $schoolCode): array {
    global $conn;
    $safe = $conn->real_escape_string(strtoupper(trim($schoolCode)));
    $priceMap = [];
    foreach (getSchoolGrades($schoolCode, false) as $g) {
        $priceMap[$g['grade_name']] = (float)$g['standard_price'];
    }
    $count = 0; $discounted = 0;
    $r = $conn->query("SELECT st_class, st_price FROM students WHERE school_scope='$safe' AND st_statue='" . GRADUATED_STATUS . "'");
    if ($r) while ($row = $r->fetch_assoc()) {
        $count++;
        $standardPrice = $priceMap[$row['st_class']] ?? 0;
        if ($standardPrice > 0) {
            $price = (float)str_replace([',', ' '], '', (string)$row['st_price']);
            if ($price < $standardPrice) $discounted++;
        }
    }
    return ['count' => $count, 'discounted' => $discounted];
}
function schoolAllowsClass(string $school, string $class): bool {
    if(strtoupper($school)==='ALL') return true;
    $class=trim($class);
    foreach(getSchoolGrades($school) as $g) if(strtolower(trim($g['grade_name']))===strtolower($class)) return true;
    return false;
}
function resolveTargetSchool(?string $requested=null): string {
    $current=currentSchoolScope();
    if($current!=='ALL') return $current;
    $requested=strtoupper(trim((string)$requested));
    if($requested!=='' && getSchoolByCode($requested)) return $requested;
    die('Please select a valid active school.');
}
function schoolSql(string $column='school_scope'): string {
    if(canSeeAllSchools()) return '1=1';
    return $column."='".mysqli_real_escape_string($GLOBALS['conn'],currentSchoolScope())."'";
}
// A Manager/Viewer/CEO/Assistant account can be restricted to only Male or only Female
// students (set on Add User as "Access"). 'All' (or unset) means no restriction.
/* ======================= RECENT ACTIVITY (audit log) ======================= *
 * Records who did what, and (for student edits / marks edits) exactly which
 * fields changed and their old -> new values, so the admin dashboard can show
 * a real changelog instead of just "something was updated".
 * ============================================================================ */

// Best-effort actor identity from the session. Admin/GeneralAdmin logins store
// 'username'; teacher logins already store 'teacher_name'. Falls back to the
// role name if nothing else is available (never blocks the action itself).
function currentActorInfo(): array {
    $role = $_SESSION['user_role'] ?? (isGeneralAdmin() ? 'GeneralAdmin' : 'Admin');
    $name = $_SESSION['username'] ?? $_SESSION['teacher_name'] ?? $role;
    return [(string)$name, (string)$role];
}

function logActivity(string $actionType, string $targetType, $targetId, string $targetName, string $summary, ?string $schoolScope = null): void {
    global $conn;
    if (!activityLogTableReady()) { error_log('LOZAN logActivity skipped: activity_log table does not exist'); return; }
    [$actorName, $actorRole] = currentActorInfo();
    $schoolScope = $schoolScope !== null && $schoolScope !== '' ? strtoupper($schoolScope) : currentSchoolScope();
    $targetIdVal = $targetId !== null ? (int)$targetId : null;
    $stmt = $conn->prepare("INSERT INTO activity_log (school_scope, actor_name, actor_role, action_type, target_type, target_id, target_name, summary) VALUES (?,?,?,?,?,?,?,?)");
    if (!$stmt) { error_log('LOZAN logActivity prepare failed: ' . $conn->error); return; }
    $stmt->bind_param("sssssiss", $schoolScope, $actorName, $actorRole, $actionType, $targetType, $targetIdVal, $targetName, $summary);
    if (!$stmt->execute()) { error_log('LOZAN logActivity execute failed: ' . $stmt->error); }
    $stmt->close();
}

// Same insert logActivity() does, but returns exactly what happened instead of only
// writing to logs/php-error.log — so a non-technical admin can hit "Run Diagnostic
// Test" on the Activity Log page and immediately see the real MySQL error on screen,
// rather than guessing or having to find a log file on the server.
function testActivityLogInsert(): array {
    global $conn;
    if (!activityLogTableReady()) {
        return ['ok' => false, 'message' => 'The activity_log table does not exist. Database said: ' . ($conn->error ?: '(no error text — most likely a missing CREATE TABLE privilege)')];
    }
    [$actorName, $actorRole] = currentActorInfo();
    $scope = currentSchoolScope();
    $actionType = 'diagnostic_test';
    $targetType = 'system';
    $targetIdVal = null;
    $targetName = 'Diagnostic Test';
    $summary = 'Manual test entry from the Activity Log page — safe to ignore. Created ' . date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO activity_log (school_scope, actor_name, actor_role, action_type, target_type, target_id, target_name, summary) VALUES (?,?,?,?,?,?,?,?)");
    if (!$stmt) {
        return ['ok' => false, 'message' => 'Could not prepare the insert. Database said: ' . $conn->error];
    }
    $stmt->bind_param("sssssiss", $scope, $actorName, $actorRole, $actionType, $targetType, $targetIdVal, $targetName, $summary);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        return ['ok' => false, 'message' => 'Insert failed. Database said: ' . $err];
    }
    $newId = $conn->insert_id;
    $stmt->close();
    return ['ok' => true, 'message' => "Test row inserted successfully (id $newId, as $actorName / $actorRole, school $scope). Logging itself works — if real student updates still don't appear, double check you're using the Update button on Students Data / Add Student (not a different edit screen)."];
}

// $schoolScope: null or 'ALL' = every school (General Admin view). Any other value scopes to that school.
// $date: 'Y-m-d' string to filter to a single day, or null for no date filter.
function getActivityLog(?string $schoolScope, ?string $date, int $limit = 100): array {
    global $conn;
    if (!activityLogTableReady()) return [];
    $limit = max(1, min(500, $limit));
    $conditions = [];
    if ($schoolScope !== null && strtoupper($schoolScope) !== 'ALL') {
        $conditions[] = "school_scope='" . $conn->real_escape_string(strtoupper($schoolScope)) . "'";
    }
    if ($date) {
        $conditions[] = "DATE(created_at)='" . $conn->real_escape_string($date) . "'";
    }
    $sql = "SELECT * FROM activity_log";
    if ($conditions) $sql .= " WHERE " . implode(" AND ", $conditions);
    $sql .= " ORDER BY created_at DESC, id DESC LIMIT $limit";
    $out = [];
    $r = $conn->query($sql);
    if ($r) while ($row = $r->fetch_assoc()) $out[] = $row;
    return $out;
}

// Which past dates actually have activity (used to populate a quick-jump list on the full log page).
function getActivityDatesList(?string $schoolScope, int $limitDays = 60): array {
    global $conn;
    if (!activityLogTableReady()) return [];
    $where = "";
    if ($schoolScope !== null && strtoupper($schoolScope) !== 'ALL') {
        $where = "WHERE school_scope='" . $conn->real_escape_string(strtoupper($schoolScope)) . "'";
    }
    $sql = "SELECT DATE(created_at) d, COUNT(*) c FROM activity_log $where GROUP BY DATE(created_at) ORDER BY d DESC LIMIT " . (int)$limitDays;
    $out = [];
    $r = $conn->query($sql);
    if ($r) while ($row = $r->fetch_assoc()) $out[] = $row;
    return $out;
}

// Compares an existing student row against the new values about to be saved and
// returns a human-readable "Field: old -> new" summary of only the fields that changed.
function buildStudentDiffSummary(array $old, array $new): string {
    $labels = [
        'st_name' => 'Name', 'st_m_name' => "Mother's Name", 'st_nation' => 'Nationality', 'st_religion' => 'Religion',
        'st_citiiz' => 'Citizenship', 'type_of_id' => 'ID Type', 'st_id_number' => 'ID Number', 'st_class' => 'Class',
        'st_group' => 'Group', 'st_type' => 'Type', 'st_price' => 'Price', 'st_faculty' => 'Faculty', 'st_statue' => 'Status',
        'f_tell' => "Father's Phone", 'm_tell' => "Mother's Phone", 'st_tell' => "Student's Phone", 'n_bro' => 'Brothers',
        'n_sis' => 'Sisters', 'st_bd_order' => 'Birth Order', 'st_bd_date' => 'Birth Date', 'st_gender' => 'Gender',
        'st_b_group' => 'Blood Group', 'st_home_loc' => 'Home Location', 'last_s_name' => 'Last School', 'st_avg_mark' => 'Average Mark',
        'st_f_year' => 'Field Year', 'st_size' => 'Size', 'st_note' => 'Note',
    ];
    $parts = [];
    foreach ($labels as $col => $label) {
        $ov = trim((string)($old[$col] ?? ''));
        $nv = trim((string)($new[$col] ?? ''));
        if ($ov !== $nv) {
            $parts[] = $label . ": '" . ($ov !== '' ? $ov : '—') . "' → '" . ($nv !== '' ? $nv : '—') . "'";
        }
    }
    return implode('; ', $parts);
}

function currentGenderAccess(): string {
    $v = ucfirst(strtolower(trim((string)($_SESSION['useraccess'] ?? 'All'))));
    return in_array($v, ['Male', 'Female'], true) ? $v : 'All';
}
function genderSql(string $column='st_gender'): string {
    $access = currentGenderAccess();
    if ($access === 'All') return '1=1';
    return $column."='".mysqli_real_escape_string($GLOBALS['conn'],$access)."'";
}
function teacherCanAccess($teacherId, $className, $subject): bool {
    global $conn;
    $tid=(int)$teacherId;
    $class=mysqli_real_escape_string($conn,(string)$className);
    $sub=mysqli_real_escape_string($conn,(string)$subject);
    $sql="SELECT t.id FROM teachers t INNER JOIN givenclass g ON g.t_id=t.id
          WHERE t.id=$tid AND t.t_sub='$sub' AND g.t_class='$class'
          AND ".schoolSql('t.school_scope')." AND ".schoolSql('g.school_scope')." LIMIT 1";
    $r=$conn->query($sql); return $r&&$r->num_rows===1;
}
function teacherCanAccessStudent($teacherId, $studentId, $subject): bool {
    global $conn;
    $sid=(int)$studentId;
    $sub=mysqli_real_escape_string($conn,(string)$subject);
    $r=$conn->query("SELECT st_class, st_group FROM students WHERE id=$sid AND ".schoolSql('school_scope')." LIMIT 1");
    if(!$r || !$r->num_rows) return false;
    $st=$r->fetch_assoc();
    return teacherCanAccess($teacherId, $st['st_class'].'-'.$st['st_group'], $subject);
}
function scopedStudentExists($id): bool { global $conn; $id=(int)$id; $r=$conn->query("SELECT id FROM students WHERE id=$id AND ".schoolSql('school_scope')." LIMIT 1"); return $r&&$r->num_rows===1; }
function scopedTeacherExists($id): bool { global $conn; $id=(int)$id; $r=$conn->query("SELECT id FROM teachers WHERE id=$id AND ".schoolSql('school_scope')." LIMIT 1"); return $r&&$r->num_rows===1; }

function insertStudents($tv, $as, $yt, $qw, $we, $rt, $cv, $zx, $adas, $xcv, $mcx, $lkoi, $usxa, $bva, $nbc, $mnr, $bcvs, $kjius, $sdrza, $posq, $trqs, $bvfgsd, $mkas, $uyw, $azss, $mnas, $uioc, $kmns, $gtraz, $schoolScope = null)
{
  global $conn;
  $schoolScope = resolveTargetSchool($schoolScope);
  if (!schoolAllowsClass($schoolScope, (string)$azss)) { die('The selected school does not allow this class.'); }
  $sql = "INSERT INTO students(st_name,st_m_name,st_img,st_bd_date,st_b_group,st_nation,st_religion,st_gender,n_bro,n_sis,
    st_bd_order,st_home_loc,st_avg_mark,last_s_name,st_f_year,f_tell,m_tell,st_tell,st_citiiz,type_of_id,st_id_number,st_id_file,st_class,st_faculty,st_type,st_date,st_price,st_size,st_group,school_scope)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
  $stmt = $conn->prepare($sql);
  if (!$stmt) { die("Error is: " . $conn->error); }
  $stmt->bind_param("ssssssssssssssssssssssssssssss",
    $tv, $as, $yt, $qw, $we, $rt, $cv, $zx, $adas, $xcv, $mcx, $lkoi, $usxa, $bva, $nbc, $mnr,
    $bcvs, $kjius, $sdrza, $posq, $trqs, $bvfgsd, $mkas, $uyw, $azss, $mnas, $uioc, $kmns, $gtraz, $schoolScope
  );
  if ($stmt->execute()) {
    $newId = $conn->insert_id;
    $stmt->close();
    logActivity('student_created', 'student', $newId, $tv, "New student registered — Class $mkas" . ($gtraz && $gtraz !== 'newst' ? ", Group $gtraz" : ''), $schoolScope);
    echo "Student Added successfully. &#10004";
  } else {
    $err = $stmt->error; $stmt->close();
    die("Error is: " . $err);
  }
}
function insertUsers($re, $yt, $iu, $bhs, $schoolScope = null)
{
  global $conn;
  $schoolScope = mysqli_real_escape_string($conn, resolveTargetSchool($schoolScope));
  $uname=mysqli_real_escape_string($conn,$re);
  $dup=$conn->query("SELECT id FROM users WHERE u_name='$uname' LIMIT 1");
  if($dup && $dup->num_rows>0){ die('Username already exists. Please choose another username.'); }
  $yt = mysqli_real_escape_string($conn, password_hash((string)$yt, PASSWORD_DEFAULT));
  $iu = mysqli_real_escape_string($conn, $iu);
  $bhs = mysqli_real_escape_string($conn, $bhs);
  $sql = "INSERT INTO users(u_name,u_pass,u_role,u_access,school_scope) VALUES ('$uname','$yt','$iu','$bhs','$schoolScope')";
  if ($conn->query($sql) === TRUE) {
    echo "Student Added successfully. &#10004";
  } else {
    die("Error is: " . $conn->connect_error);
  }
}
// Bans/unbans an individual student's continued access (acc_con). Looks the student up
// by ID in the students table (not users — banning a student ID against the users table
// was the bug: it meant General Admin couldn't use this at all, since no student ID
// exists as a user id, which made resolveTargetSchool() fall through and hard-crash the
// page). Returns true on success, or an error string to show the admin.
function insertAccConById($studentId) {
  global $conn;
  $studentId = (int)$studentId;
  if ($studentId <= 0) return 'Enter a valid student ID.';
  $stu = $conn->query("SELECT id, st_name, school_scope FROM students WHERE id=$studentId LIMIT 1");
  if (!$stu || $stu->num_rows === 0) return 'No student found with that ID.';
  $row = $stu->fetch_assoc();
  if (!canSeeAllSchools() && strtoupper($row['school_scope']) !== strtoupper(currentSchoolScope())) {
      return 'That student does not belong to your school.';
  }
  $safeScope = mysqli_real_escape_string($conn, $row['school_scope']);
  $sql = "INSERT INTO acc_con(acc_id,school_scope) VALUES ($studentId,'$safeScope')";
  return $conn->query($sql) ? true : 'Could not save the ban.';
}
// Turns a school-wide switch on ('st_stat'=students, 't_stat'=teachers, 'm_stat'=mark
// entry). Presence of a row is what blocks/freezes it — see getSwitchState().
function insertAccCon($posName, ?string $requestedSchool = null): bool {
  global $conn;
  $schoolScope = mysqli_real_escape_string($conn, resolveTargetSchool($requestedSchool));
  $posName = mysqli_real_escape_string($conn, $posName);
  $sql = "INSERT INTO mark_con(pos_nm,school_scope) VALUES ('$posName','$schoolScope')";
  return (bool)$conn->query($sql);
}
function insertTeachers($re, $yt, $iu, $bhs, $schoolScope = null)
{
  global $conn;
  $schoolScope = resolveTargetSchool($schoolScope);
  $teacherClass=(string)$bhs; $teacherGrade=explode('-', $teacherClass, 2)[0];
  if (!schoolAllowsClass($schoolScope, $teacherGrade)) { die('The selected school does not allow this teacher class.'); }
  $stmt = $conn->prepare("INSERT INTO lozanstaff(teacher_img,name,education,class,school_scope) VALUES (?,?,?,?,?)");
  if (!$stmt) { die("Error is: " . $conn->error); }
  $stmt->bind_param("sssss", $re, $yt, $iu, $bhs, $schoolScope);
  if ($stmt->execute()) {
    $stmt->close();
    echo "Student Added successfully. &#10004";
  } else {
    $err = $stmt->error; $stmt->close();
    die("Error is: " . $err);
  }
}
function insertGivenClass($re, $yt, $schoolScope = null)
{
  global $conn;
  $teacherId=(int)$re;
  $teacherScope=resolveTargetSchool($schoolScope);
  $ts=$conn->query("SELECT school_scope FROM teachers WHERE id=$teacherId LIMIT 1");
  if($ts && ($tr=$ts->fetch_assoc())) { $teacherScope=$tr['school_scope']; }
  $classValue=(string)$yt; $gradeValue=explode('-', $classValue, 2)[0];
  if (!schoolAllowsClass($teacherScope, $gradeValue)) { die('The selected class does not belong to the teacher school.'); }
  $stmt = $conn->prepare("INSERT INTO givenclass(t_id,t_class,school_scope) VALUES (?,?,?)");
  if (!$stmt) { die("Error is: " . $conn->error); }
  $stmt->bind_param("iss", $teacherId, $classValue, $teacherScope);
  if ($stmt->execute()) {
    $stmt->close();
    echo "Student Added successfully. &#10004";
  } else {
    $err = $stmt->error; $stmt->close();
    die("Error is: " . $err);
  }
}
function insertClosedClass($re,$tys, $schoolScope = null)
{
  global $conn;
  $schoolScope = resolveTargetSchool($schoolScope);
  $stmt = $conn->prepare("INSERT INTO staffclasscon(class_name,class_status,school_scope) VALUES (?,?,?)");
  if (!$stmt) { die("Error is: " . $conn->error); }
  $stmt->bind_param("sss", $re, $tys, $schoolScope);
  if ($stmt->execute()) {
    $stmt->close();
    echo "Class Disabled successfully. &#10004";
  } else {
    $err = $stmt->error; $stmt->close();
    die("Error is: " . $err);
  }
}
function insertTeacherss($re, $yt, $iu, $schoolScope = null)
{
  global $conn;
  $schoolScope = resolveTargetSchool($schoolScope);
  $tname=mysqli_real_escape_string($conn,$re);
  $dup=$conn->query("SELECT id FROM teachers WHERE t_name='$tname' LIMIT 1");
  if($dup && $dup->num_rows>0){ die('Teacher username already exists. Please choose another username.'); }
  $hashedPass = password_hash((string)$yt, PASSWORD_DEFAULT);
  $stmt = $conn->prepare("INSERT INTO teachers(t_name,t_pass,t_sub,school_scope) VALUES (?,?,?,?)");
  if (!$stmt) { die("Error is: " . $conn->error); }
  $stmt->bind_param("ssss", $re, $hashedPass, $iu, $schoolScope);
  if ($stmt->execute()) {
    $stmt->close();
    echo "Student Added successfully. &#10004";
  } else {
    $err = $stmt->error; $stmt->close();
    die("Error is: " . $err);
  }
}
function insertSubject($re, $schoolScope = null)
{
  global $conn;
  $schoolScope = resolveTargetSchool($schoolScope);
  $stmt = $conn->prepare("INSERT INTO subjects(sb_name,school_scope) VALUES (?,?)");
  if (!$stmt) { die("Error is: " . $conn->error); }
  $stmt->bind_param("ss", $re, $schoolScope);
  if ($stmt->execute()) {
    $stmt->close();
    echo "Subject Added successfully. &#10004";
  } else {
    $err = $stmt->error; $stmt->close();
    die("Error is: " . $err);
  }
}
function getDh($tr)
{
  global $conn;
  $allowed=['students','users','teachers','lozanstaff','givenclass','staffclasscon','subjects','mark_con','acc_con'];
  if(!in_array($tr,$allowed,true)) return false;
  return $conn->query("SELECT * FROM `$tr` WHERE ".schoolSql('school_scope')." ORDER BY id ASC");
}
function getMarksBySemster($tr,$ytw,$iuy)
{
  global $conn; $sid=(int)$ytw; $sem=mysqli_real_escape_string($conn,$iuy);
  return $conn->query("SELECT m.* FROM `$tr` m INNER JOIN students s ON s.id=m.st_id WHERE m.st_id=$sid AND m.semseter='$sem' AND ".schoolSql('s.school_scope'));
}
function getMarkCon($tr,$yte)
{
  global $conn; $yte=mysqli_real_escape_string($conn,$yte);
  return $conn->query("SELECT * FROM `$tr` WHERE pos_nm='$yte' AND ".schoolSql('school_scope'));
}
function getMarkConById($tr,$yte)
{
  global $conn; $yte=(int)$yte;
  return $conn->query("SELECT * FROM `$tr` WHERE acc_id=$yte AND ".schoolSql('school_scope'));
}
// Is a school-wide switch ('st_stat'=students, 't_stat'=teachers, 'm_stat'=mark entry)
// currently blocking/freezing, for one specific school? Returns the mark_con row (so
// its id is available for unblocking) or null if it's currently allowed. Takes an
// explicit school code rather than relying on ambient scope, since General Admin's
// ambient scope is "every school at once" — this always needs one specific school.
function getSwitchState(string $posName, string $schoolCode): ?array {
    global $conn;
    $safePos = mysqli_real_escape_string($conn, $posName);
    $safeSchool = mysqli_real_escape_string($conn, strtoupper(trim($schoolCode)));
    $r = $conn->query("SELECT * FROM mark_con WHERE pos_nm='$safePos' AND school_scope='$safeSchool' LIMIT 1");
    return ($r && $r->num_rows > 0) ? $r->fetch_assoc() : null;
}
// Every individually banned student for one specific school, with their name/class
// looked up so the Control page never has to show a bare, unrecognisable ID.
function getBannedStudentsDetailed(string $schoolCode) {
    global $conn;
    $safeSchool = mysqli_real_escape_string($conn, strtoupper(trim($schoolCode)));
    return $conn->query("SELECT ac.id AS ban_id, ac.acc_id, ac.acc_st, s.st_name, s.st_class, s.st_group
                          FROM acc_con ac
                          LEFT JOIN students s ON s.id = ac.acc_id
                          WHERE ac.school_scope='$safeSchool'
                          ORDER BY ac.id DESC");
}
function getDhAttenJoinTB($dd, $trw)
{

  global $conn;

  $sql = "SELECT 

                students.st_name,
                students.st_class,
                students.st_group,

                attendance.status,
                attendance.date

            FROM attendance

            INNER JOIN students
            ON students.id = attendance.student_id

            WHERE attendance.date = '$dd' AND " . genderSql('students.st_gender') . " AND " . schoolSql('students.school_scope') . "

            ORDER BY attendance.date DESC";

  return mysqli_query($conn, $sql);
}

function getDhAttenJoinTBAll($dd, $tre)
{
  global $conn;

  $sql = "SELECT 
                students.st_name,
                students.st_class,
                students.st_group,
                COUNT(attendance.id) AS total_absent,
                GROUP_CONCAT(attendance.date ORDER BY attendance.date SEPARATOR ' - ') AS absent_dates
            FROM attendance
            INNER JOIN students
                ON students.id = attendance.student_id
            WHERE attendance.date BETWEEN '$dd' AND '$tre'
              AND ( attendance.status = 'absent' OR attendance.status = 'permit' )
              AND " . schoolSql('students.school_scope') . "
            GROUP BY students.id
            ORDER BY total_absent DESC";

  return mysqli_query($conn, $sql);
}
function getDhAttenJoinTBAllControl($dd, $tre)
{
  global $conn;

  $sql = "SELECT 
                students.st_name,
                students.st_class,
                students.st_group,
                attendance.id,
                attendance.date,
                attendance.status
          FROM attendance
          INNER JOIN students
              ON students.id = attendance.student_id
          WHERE attendance.date BETWEEN '$dd' AND '$tre'
              AND ( attendance.status = 'absent' OR attendance.status = 'permit' )
              AND " . schoolSql('students.school_scope') . "
          ORDER BY attendance.date DESC";

  return mysqli_query($conn, $sql);
}

function getDhAttenJoinTBAllGENDER($dd, $tre, $nba)
{
  global $conn;

  $sql = "SELECT 
                students.st_name,
                students.st_class,
                students.st_group,
                COUNT(attendance.id) AS total_absent,
                GROUP_CONCAT(attendance.date ORDER BY attendance.date SEPARATOR ' - ') AS absent_dates
            FROM attendance
            INNER JOIN students
                ON students.id = attendance.student_id
            WHERE attendance.date BETWEEN '$dd' AND '$tre' AND " . genderSql('students.st_gender') . " AND " . schoolSql('students.school_scope') . "
              AND attendance.status = 'absent'
            GROUP BY students.id
            ORDER BY total_absent DESC";

  return mysqli_query($conn, $sql);
}




function getDhForlogin($tr,$mns)
{
  global $conn; $mns=mysqli_real_escape_string($conn,$mns);
  return $conn->query("SELECT * FROM `$tr` WHERE u_name='$mns' AND ".schoolSql('school_scope'));
}
function getDhByID($tr,$brt)
{
  global $conn; $brt=(int)$brt;
  $allowed=['students','users','teachers','lozanstaff','givenclass','staffclasscon','subjects','mark_con','acc_con','attendance'];
  if(!in_array($tr,$allowed,true)) return false;
  if($tr==='attendance') return $conn->query("SELECT a.* FROM attendance a INNER JOIN students s ON s.id=a.student_id WHERE a.id=$brt AND ".schoolSql('s.school_scope'));
  return $conn->query("SELECT * FROM `$tr` WHERE id=$brt AND ".schoolSql('school_scope'));
}
function DeleteData($er,$yt)
{
  global $conn; $yt=(int)$yt;
  $allowed=['students','users','teachers','lozanstaff','givenclass','staffclasscon','subjects','mark_con','acc_con','attendance'];
  if(!in_array($er,$allowed,true)) return false;
  if($er==='attendance') return $conn->query("DELETE FROM attendance WHERE id=$yt AND EXISTS (SELECT 1 FROM students s WHERE s.id=attendance.student_id AND ".schoolSql('s.school_scope').")");
  return $conn->query("DELETE FROM `$er` WHERE id=$yt AND ".schoolSql('school_scope'));
}
function getDhForAllSchool()
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_stschool FROM students WHERE " . schoolSql('school_scope') . " AND " . genderSql() . " AND st_statue = 'بەردەوام'";
  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_stschool'];
  }
  return 0;
}
function getDhForAllSchooldISC()
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_stschool FROM students WHERE " . schoolSql('school_scope') . " AND " . genderSql() . " AND st_price !='2500000' AND st_class = '12' ";
  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_stschool'];
  }
  return 0;
}
function getDhForAllSchooldISC1()
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_stschool FROM students WHERE " . schoolSql('school_scope') . " AND " . genderSql() . " AND st_price !='2000000' AND st_class = '11' ";
  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_stschool'];
  }
  return 0;
}
function getDhForAllSchooldISC12()
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_stschool FROM students WHERE " . schoolSql('school_scope') . " AND " . genderSql() . " AND st_price !='2000000' AND  st_class='10' ";
  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_stschool'];
  }
  return 0;
}
function getDhForAllStCount()
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_students FROM students WHERE " . schoolSql('school_scope') . " AND " . genderSql() . " AND st_statue = 'بەردەوام' AND st_class='12'";
  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_students'];
  }
  return 0;
}
function getDhForGirlsStCount()
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_g FROM students WHERE " . schoolSql('school_scope') . " AND st_gender = 'female' AND " . genderSql() . " AND st_class='12' AND st_statue = 'بەردەوام'";
  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_g'];
  }
  return 0;
}
function getDhForBoysStCount()
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_b FROM students WHERE " . schoolSql('school_scope') . " AND st_gender = 'male' AND " . genderSql() . " AND st_class='12' AND st_statue = 'بەردەوام'";
  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_b'];
  }
  return 0;
}
function getDhForSciStCount()
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_sic FROM students WHERE " . schoolSql('school_scope') . " AND " . genderSql() . " AND st_faculty = 'زانستی' AND st_statue = 'بەردەوام' AND st_class='12'";
  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_sic'];
  }
  return 0;
}
function getDhForLitStCount()
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_lit FROM students WHERE " . schoolSql('school_scope') . " AND " . genderSql() . " AND st_faculty = 'وێژەیی' AND st_statue = 'بەردەوام' AND st_class='12'";
  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_lit'];
  }
  return 0;
}
function getDhForTDStCount()
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_tds FROM students WHERE " . schoolSql('school_scope') . " AND " . genderSql() . " AND st_statue = 'بەردەوام' AND st_class='12' AND (st_type = 'دەرەکی' OR st_type = 'تێکرای نمرە')";
  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_tds'];
  }
  return 0;
}
function getDhForTDSKtCount()
{
  global $conn;
  $sql = "
        SELECT COUNT(*) AS total_tdl
        FROM students
        WHERE " . schoolSql('school_scope') . " AND " . genderSql() . "
          AND st_statue = 'بەردەوام'
          AND st_class = '12'
          AND st_faculty = 'زانستی'
          AND (st_type = 'دەرەکی' OR st_type = 'تێکرای نمرە')
    ";

  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_tdl'];
  }
  return 0;
}

function getDhForTDSLtCount()
{
  global $conn;
  $sql = "
        SELECT COUNT(*) AS total_tdl
        FROM students
        WHERE " . schoolSql('school_scope') . " AND " . genderSql() . "
          AND st_statue = 'بەردەوام'
          AND st_class = '12'
          AND st_faculty = 'وێژەیی'
          AND (st_type = 'دەرەکی' OR st_type = 'تێکرای نمرە')
    ";

  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_tdl'];
  }
  return 0;
}

function getDhForAllStCountGradeE()
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_studentsE FROM students WHERE " . schoolSql('school_scope') . " AND " . genderSql() . " AND st_statue = 'بەردەوام' AND st_class='11'";
  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsE'];
  }
  return 0;
}
function getDhForAllStCountGradeEG()
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_studentsEG FROM students WHERE " . schoolSql('school_scope') . " AND " . genderSql() . " AND st_statue = 'بەردەوام' AND st_class='11' AND st_gender='Female'";
  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsEG'];
  }
  return 0;
}
function getDhForAllStCountGradeEB()
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_studentsEB FROM students WHERE " . schoolSql('school_scope') . " AND " . genderSql() . " AND st_statue = 'بەردەوام' AND st_class='11' AND st_gender='Male'";
  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsEB'];
  }
  return 0;
}
function getDhForAllStCountGradeT()
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_studentsT FROM students WHERE " . schoolSql('school_scope') . " AND " . genderSql() . " AND st_statue = 'بەردەوام' AND st_class='10'";
  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsT'];
  }
  return 0;
}
function getDhForAllStCountGradeTG()
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_studentsTG FROM students WHERE " . schoolSql('school_scope') . " AND " . genderSql() . " AND st_statue = 'بەردەوام' AND st_class='10' AND st_gender='female'";
  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsTG'];
  }
  return 0;
}
function getDhForAllStCountGradeTB()
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_studentsTB FROM students WHERE " . schoolSql('school_scope') . " AND " . genderSql() . " AND st_statue = 'بەردەوام' AND st_class='10' AND st_gender='male'";
  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsTB'];
  }
  return 0;
}
function updateDisc($uname, $upas,$yutw)
{
  global $conn;
  $idInt = (int)$uname;
  $oldRow = null;
  $oldRes = $conn->query("SELECT st_name, st_price, st_note, school_scope FROM students WHERE id='$idInt' AND " . schoolSql('school_scope') . " LIMIT 1");
  if ($oldRes && $oldRes->num_rows === 1) { $oldRow = $oldRes->fetch_assoc(); }
  $sql = "UPDATE students SET st_price=?, st_note=? WHERE id=? AND " . schoolSql('school_scope');
  $stmt = $conn->prepare($sql);
  if (!$stmt) { die("Error is: " . $conn->error); }
  $stmt->bind_param("ssi", $upas, $yutw, $idInt);
  if ($stmt->execute()) {
    $stmt->close();
    if ($oldRow !== null) {
        $parts = [];
        if (trim((string)$oldRow['st_price']) !== trim((string)$upas)) $parts[] = "Price: '{$oldRow['st_price']}' → '$upas'";
        if (trim((string)$oldRow['st_note']) !== trim((string)$yutw)) $parts[] = "Note: '{$oldRow['st_note']}' → '$yutw'";
        if ($parts) {
            logActivity('student_updated', 'student', $idInt, $oldRow['st_name'], implode('; ', $parts), $oldRow['school_scope']);
        }
    }
    echo "Discount Added successfully. &#10004";
  } else {
    $err = $stmt->error; $stmt->close();
    die("Error is: " . $err);
  }
}
function updateStClass($uname, $upas)
{
  global $conn;
  $idInt = (int)$uname;
  $oldRow = null;
  $oldRes = $conn->query("SELECT st_name, st_group, school_scope FROM students WHERE id='$idInt' AND " . schoolSql('school_scope') . " LIMIT 1");
  if ($oldRes && $oldRes->num_rows === 1) { $oldRow = $oldRes->fetch_assoc(); }
  $sql = "UPDATE students SET st_group=? WHERE id=? AND " . schoolSql('school_scope');
  $stmt = $conn->prepare($sql);
  if (!$stmt) { die("Error is: " . $conn->error); }
  $stmt->bind_param("si", $upas, $idInt);
  if ($stmt->execute()) {
    $stmt->close();
    if ($oldRow !== null && trim((string)$oldRow['st_group']) !== trim((string)$upas)) {
        logActivity('student_updated', 'student', $idInt, $oldRow['st_name'], "Group: '{$oldRow['st_group']}' → '$upas'", $oldRow['school_scope']);
    }
    echo "Class Updated successfully. &#10004";
  } else {
    $err = $stmt->error; $stmt->close();
    die("Error is: " . $err);
  }
}
function updateSTdata($id, $st_name, $st_m_name, $nation, $religion, $citizen, $type_id, $id_number, $st_class, $st_group, $st_type, $st_price, $faculty, $status, $f_tell, $m_tell, $st_tell, $n_bro, $n_sis, $bd_order, $bd_date, $gender, $blood, $home_loc, $yt1, $yt2, $yt3, $mnhgasi, $bnsa, $iuw,$hgaj)
{
  global $conn;
  $idInt = (int)$id;
  $oldRow = null;
  $oldRes = $conn->query("SELECT * FROM students WHERE id='$idInt' AND " . schoolSql('school_scope') . " LIMIT 1");
  if ($oldRes && $oldRes->num_rows === 1) { $oldRow = $oldRes->fetch_assoc(); }
  $sql = "UPDATE students SET
        st_name = ?, st_m_name = ?, st_nation = ?, st_religion = ?, st_citiiz = ?, type_of_id = ?,
        st_id_number = ?, st_class = ?, st_group = ?, st_type = ?, st_price = ?, st_faculty = ?,
        st_statue = ?, f_tell = ?, m_tell = ?, st_tell = ?, n_bro = ?, n_sis = ?, st_bd_order = ?,
        st_bd_date = ?, st_gender = ?, st_b_group = ?, st_home_loc = ?, last_s_name = ?, st_avg_mark = ?,
        st_f_year = ?, st_img = ?, st_size = ?, st_id_file = ?, st_note = ?
        WHERE id = ? AND " . schoolSql('school_scope');
  $stmt = $conn->prepare($sql);
  if (!$stmt) { die("Error is: " . $conn->error); }
  $stmt->bind_param("ssssssssssssssssssssssssssssssi",
    $st_name, $st_m_name, $nation, $religion, $citizen, $type_id, $id_number, $st_class, $st_group, $st_type,
    $st_price, $faculty, $status, $f_tell, $m_tell, $st_tell, $n_bro, $n_sis, $bd_order, $bd_date, $gender,
    $blood, $home_loc, $yt1, $yt2, $yt3, $mnhgasi, $bnsa, $iuw, $hgaj, $idInt
  );
  $ok = $stmt->execute();
  $err = $stmt->error;
  $stmt->close();
  if ($ok) {
    if ($oldRow !== null) {
        $newRow = [
            'st_name' => $st_name, 'st_m_name' => $st_m_name, 'st_nation' => $nation, 'st_religion' => $religion,
            'st_citiiz' => $citizen, 'type_of_id' => $type_id, 'st_id_number' => $id_number, 'st_class' => $st_class,
            'st_group' => $st_group, 'st_type' => $st_type, 'st_price' => $st_price, 'st_faculty' => $faculty, 'st_statue' => $status,
            'f_tell' => $f_tell, 'm_tell' => $m_tell, 'st_tell' => $st_tell, 'n_bro' => $n_bro, 'n_sis' => $n_sis,
            'st_bd_order' => $bd_order, 'st_bd_date' => $bd_date, 'st_gender' => $gender, 'st_b_group' => $blood,
            'st_home_loc' => $home_loc, 'last_s_name' => $yt1, 'st_avg_mark' => $yt2, 'st_f_year' => $yt3,
            'st_size' => $bnsa, 'st_note' => $hgaj,
        ];
        $diff = buildStudentDiffSummary($oldRow, $newRow);
        if ($diff !== '') {
            logActivity('student_updated', 'student', $idInt, $st_name, $diff, $oldRow['school_scope']);
        }
    }
    echo "Data Updated successfully. &#10004";
  } else {
    die("Error is: " . $err);
  }
}
function updateImageAndFilesOfSt2($id, $home_loc)
{
  global $conn;
  $idInt = (int)$id;
  $nameRes = $conn->query("SELECT st_name, school_scope FROM students WHERE id='$idInt' AND " . schoolSql('school_scope') . " LIMIT 1");
  $nameRow = ($nameRes && $nameRes->num_rows === 1) ? $nameRes->fetch_assoc() : null;
  $sql = "UPDATE students SET
        st_img        = '$home_loc'
        WHERE id = '$id' AND " . schoolSql('school_scope') . "
    ";
  if ($conn->query($sql) === TRUE) {
    if ($nameRow !== null) { logActivity('student_updated', 'student', $idInt, $nameRow['st_name'], 'Profile photo updated', $nameRow['school_scope']); }
    echo "ID Updated successfully. &#10004";
  } else {
    die("Error is: " . $conn->connect_error);
  }
}
function updateImageAndFilesOfSt($id, $home_loc)
{
  global $conn;
  $idInt = (int)$id;
  $nameRes = $conn->query("SELECT st_name, school_scope FROM students WHERE id='$idInt' AND " . schoolSql('school_scope') . " LIMIT 1");
  $nameRow = ($nameRes && $nameRes->num_rows === 1) ? $nameRes->fetch_assoc() : null;
  $sql = "UPDATE students SET
        st_id_file        = '$home_loc'
        WHERE id = '$id' AND " . schoolSql('school_scope') . "
    ";
  if ($conn->query($sql) === TRUE) {
    if ($nameRow !== null) { logActivity('student_updated', 'student', $idInt, $nameRow['st_name'], 'ID document updated', $nameRow['school_scope']); }
    echo "ID Updated successfully. &#10004";
  } else {
    die("Error is: " . $conn->connect_error);
  }
}
function getDhForAllSAttCountGradeTB($ewq)
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_studentsAtt FROM attendance a INNER JOIN students s ON s.id=a.student_id WHERE a.date= '$ewq' AND " . schoolSql('s.school_scope') . " AND " . genderSql('s.st_gender') . "";
  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsAtt'];
  }
  return 0;
}
function getDhForAllSAttCountGradeTB1($ewq)
{
  global $conn;

  $sql = "SELECT COUNT(*) AS total_studentsG12
            FROM attendance
            INNER JOIN students 
            ON students.id = attendance.student_id
            WHERE attendance.date = '$ewq'
            AND students.st_class = '12'
            AND " . schoolSql('students.school_scope') . " AND " . genderSql('students.st_gender') . "";

  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsG12'];
  }

  return 0;
}
function getDhForAllSAttCountGradeTB12($ewq)
{
  global $conn;

  $sql = "SELECT COUNT(*) AS total_studentsG12
            FROM attendance
            INNER JOIN students 
            ON students.id = attendance.student_id
            WHERE attendance.date = '$ewq'
            AND students.st_class = '11'
            AND " . schoolSql('students.school_scope') . " AND " . genderSql('students.st_gender') . "";

  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsG12'];
  }

  return 0;
}
function getDhForAllSAttCountGradeTB123($ewq)
{
  global $conn;

  $sql = "SELECT COUNT(*) AS total_studentsG12
            FROM attendance
            INNER JOIN students 
            ON students.id = attendance.student_id
            WHERE attendance.date = '$ewq'
            AND students.st_class = '10'
            AND " . schoolSql('students.school_scope') . " AND " . genderSql('students.st_gender') . "";

  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsG12'];
  }

  return 0;
}
function getDhForAllSAttCountGradeG($ewq)
{
  global $conn;

  $sql = "SELECT COUNT(*) AS total_studentsG
            FROM attendance
            INNER JOIN students 
            ON students.id = attendance.student_id
            WHERE attendance.date = '$ewq'
            AND students.st_gender = 'Female'
            AND " . schoolSql('students.school_scope') . " AND " . genderSql('students.st_gender') . "";

  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsG'];
  }

  return 0;
}
function getDhForAllSAttCountGradeG1($ewq)
{
  global $conn;

  $sql = "SELECT COUNT(*) AS total_studentsG
            FROM attendance
            INNER JOIN students 
            ON students.id = attendance.student_id
            WHERE attendance.date = '$ewq'
            AND students.st_gender = 'Female' AND students.st_class='12'
            AND " . schoolSql('students.school_scope') . " AND " . genderSql('students.st_gender') . "";

  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsG'];
  }

  return 0;
}
function getDhForAllSAttCountGradeG12($ewq)
{
  global $conn;

  $sql = "SELECT COUNT(*) AS total_studentsG
            FROM attendance
            INNER JOIN students 
            ON students.id = attendance.student_id
            WHERE attendance.date = '$ewq'
            AND students.st_gender = 'Female' AND students.st_class='11'
            AND " . schoolSql('students.school_scope') . " AND " . genderSql('students.st_gender') . "";

  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsG'];
  }

  return 0;
}
function getDhForAllSAttCountGradeG123($ewq)
{
  global $conn;

  $sql = "SELECT COUNT(*) AS total_studentsG
            FROM attendance
            INNER JOIN students 
            ON students.id = attendance.student_id
            WHERE attendance.date = '$ewq'
            AND students.st_gender = 'Female' AND students.st_class='10'
            AND " . schoolSql('students.school_scope') . " AND " . genderSql('students.st_gender') . "";

  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsG'];
  }

  return 0;
}
function getDhForAllSAttCountGradeB($ewq)
{
  global $conn;

  $sql = "SELECT COUNT(*) AS total_studentsB
            FROM attendance
            INNER JOIN students 
            ON students.id = attendance.student_id
            WHERE attendance.date = '$ewq'
            AND students.st_gender = 'male'
            AND " . schoolSql('students.school_scope') . " AND " . genderSql('students.st_gender') . "";

  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsB'];
  }

  return 0;
}
function getDhForAllSAttCountGradeB1($ewq)
{
  global $conn;

  $sql = "SELECT COUNT(*) AS total_studentsB
            FROM attendance
            INNER JOIN students 
            ON students.id = attendance.student_id
            WHERE attendance.date = '$ewq'
            AND students.st_gender = 'male' AND students.st_class='12'
            AND " . schoolSql('students.school_scope') . " AND " . genderSql('students.st_gender') . "";

  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsB'];
  }

  return 0;
}
function getDhForAllSAttCountGradeB12($ewq)
{
  global $conn;

  $sql = "SELECT COUNT(*) AS total_studentsB
            FROM attendance
            INNER JOIN students 
            ON students.id = attendance.student_id
            WHERE attendance.date = '$ewq'
            AND students.st_gender = 'male' AND students.st_class='11'
            AND " . schoolSql('students.school_scope') . " AND " . genderSql('students.st_gender') . "";

  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsB'];
  }

  return 0;
}
function getDhForAllSAttCountGradeB123($ewq)
{
  global $conn;

  $sql = "SELECT COUNT(*) AS total_studentsB
            FROM attendance
            INNER JOIN students 
            ON students.id = attendance.student_id
            WHERE attendance.date = '$ewq'
            AND students.st_gender = 'male' AND students.st_class='10'
            AND " . schoolSql('students.school_scope') . " AND " . genderSql('students.st_gender') . "";

  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsB'];
  }

  return 0;
}
function getDhForAllSAttCountGradeSCI($ewq)
{
  global $conn;

  $sql = "SELECT COUNT(*) AS total_studentsSCI
            FROM attendance
            INNER JOIN students 
            ON students.id = attendance.student_id
            WHERE attendance.date = '$ewq'
            AND students.st_faculty = 'زانستی'
            AND " . schoolSql('students.school_scope') . " AND " . genderSql('students.st_gender') . "";

  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsSCI'];
  }

  return 0;
}
function getDhForAllSAttCountGradeL($ewq)
{
  global $conn;

  $sql = "SELECT COUNT(*) AS total_studentsL
            FROM attendance
            INNER JOIN students 
            ON students.id = attendance.student_id
            WHERE attendance.date = '$ewq'
            AND students.st_faculty = 'وێژەیی'
            AND " . schoolSql('students.school_scope') . " AND " . genderSql('students.st_gender') . "";

  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsL'];
  }

  return 0;
}
function getDhForAllSAttCountGradeTD($ewq)
{
  global $conn;

  $sql = "SELECT COUNT(*) AS total_studentsTD
            FROM attendance
            INNER JOIN students 
            ON students.id = attendance.student_id
            WHERE attendance.date = '$ewq'
            AND (students.st_type = 'دەرەکی' OR students.st_type = 'تێکرای نمرە')
            AND " . schoolSql('students.school_scope') . " AND " . genderSql('students.st_gender') . "";

  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsTD'];
  }

  return 0;
}
// Deletes every student row for ONE specific school (never "all schools" — the caller
// must always pass an explicit school code, there is no ambient/whole-system mode here
// on purpose, since this is irreversible). Also cleans up attendance and mark records
// that reference those students, so nothing orphaned is left behind in the database.
// Deliberately does NOT touch uploaded files (st_image/, id_data/) — only the database
// rows are removed, exactly as requested.
// Returns ['ok'=>bool, 'deleted'=>int, 'error'=>string|null].
function deleteAllStudentsForSchool(string $schoolCode): array {
    global $conn;
    $school = getSchoolByCode($schoolCode);
    if (!$school) return ['ok' => false, 'deleted' => 0, 'error' => 'Unknown school.'];
    $safe = $conn->real_escape_string($school['school_code']);

    $conn->begin_transaction();
    try {
        $countRes = $conn->query("SELECT COUNT(*) AS c FROM students WHERE school_scope='$safe'");
        $count = $countRes ? (int)$countRes->fetch_assoc()['c'] : 0;

        $conn->query("DELETE FROM attendance WHERE student_id IN (SELECT id FROM students WHERE school_scope='$safe')");
        $conn->query("DELETE FROM st_marks WHERE st_id IN (SELECT id FROM students WHERE school_scope='$safe')");
        $conn->query("DELETE FROM students WHERE school_scope='$safe'");

        $conn->commit();
        return ['ok' => true, 'deleted' => $count, 'error' => null];
    } catch (Throwable $e) {
        $conn->rollback();
        return ['ok' => false, 'deleted' => 0, 'error' => $e->getMessage()];
    }
}
function upattdata($rew,$nbs,$uye)
{
  global $conn;
  $sql = "UPDATE attendance SET
        status   = '$rew',
        date   = '$nbs'
        WHERE id = '$uye'
    ";
  if ($conn->query($sql) === TRUE) {
    echo "Updated successfully. &#10004";
  } else {
    die("Error is: " . $conn->connect_error);
  }
}