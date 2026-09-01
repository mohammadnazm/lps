<?php
include "admin_header.php";

$allowed = [
    'user'    => ['table' => 'users',    'pass_col' => 'u_pass', 'name_col' => 'u_name'],
    'teacher' => ['table' => 'teachers', 'pass_col' => 't_pass', 'name_col' => 't_name'],
];

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if (!isset($allowed[$type]) || $id <= 0) {
    die('Invalid request.');
}

$cfg = $allowed[$type];
$table = $cfg['table'];
$passCol = $cfg['pass_col'];
$nameCol = $cfg['name_col'];

$row = null;
$res = $conn->query("SELECT * FROM `$table` WHERE id=$id AND " . schoolSql('school_scope') . " LIMIT 1");
if ($res && $res->num_rows === 1) { $row = $res->fetch_assoc(); }
if (!$row) { die('Record not found, or you do not have access to it.'); }

$msg = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck($_POST['csrf_token'] ?? null)) {
        $msg = 'Your session expired — please reopen this window and try again.';
    } else {
        $newPass = trim($_POST['new_password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');
        if (strlen($newPass) < 6) {
            $msg = 'Password must be at least 6 characters.';
        } elseif ($newPass !== $confirm) {
            $msg = 'Passwords do not match.';
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE `$table` SET `$passCol` = ? WHERE id = ?");
            $stmt->bind_param("si", $hash, $id);
            if ($stmt->execute()) {
                $stmt->close();
                logActivity($type . '_password_reset', $type, $id, $row[$nameCol], 'Password reset by admin', $row['school_scope'] ?? null);
                $done = true;
            } else {
                $msg = 'Could not update the password: ' . $stmt->error;
                $stmt->close();
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Password</title>
    <style>
        body{font-family:'Segoe UI',sans-serif;background:#0f172a;color:#e2e8f0;padding:28px;margin:0}
        h3{margin:0 0 18px}
        label{display:block;font-size:13px;color:#93a4b8;margin:14px 0 6px}
        input[type=password]{width:100%;box-sizing:border-box;padding:10px 12px;border-radius:8px;border:1px solid #334155;background:#1e293b;color:#fff;font-size:14px}
        button{margin-top:18px;padding:10px 22px;background:#2563eb;border:0;border-radius:8px;color:#fff;font-weight:700;cursor:pointer;font-size:14px}
        .err{background:rgba(248,113,113,.12);color:#f87171;border:1px solid rgba(248,113,113,.3);padding:10px 12px;border-radius:8px;font-size:13px;margin-bottom:6px}
        .ok{background:rgba(74,222,128,.12);color:#4ade80;border:1px solid rgba(74,222,128,.3);padding:14px;border-radius:8px;font-size:14px}
    </style>
</head>
<body>
    <h3>Reset Password — <?=htmlspecialchars($row[$nameCol])?></h3>

    <?php if ($done): ?>
        <div class="ok">✅ Password updated successfully. You can close this window.</div>
        <script>
            if (window.opener) { try { window.opener.location.reload(); } catch (e) {} }
        </script>
    <?php else: ?>
        <?php if ($msg): ?><div class="err"><?=htmlspecialchars($msg)?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(csrfToken())?>">
            <label>New Password (min. 6 characters)</label>
            <input type="password" name="new_password" minlength="6" required>
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" minlength="6" required>
            <button type="submit">Update Password</button>
        </form>
    <?php endif; ?>
</body>
</html>
