<?php
ob_start();
include "admin_header.php";
if(!isGeneralAdmin()) { http_response_code(403); echo '<div class="alert alert-danger">Only General Administration can manage schools.</div>'; include "admin_footer.php"; exit; }
$msg=''; $err='';
function adminScalar($sql){global $conn;$r=$conn->query($sql);if(!$r)return 0;$x=$r->fetch_row();return (int)($x[0]??0);}

// Validates and saves an uploaded logo for a given school code. Returns the stored
// filename on success, or null if no file was submitted for this field at all.
// Throws RuntimeException on a real problem (wrong type, too big, upload error).
function saveSchoolLogo(string $fieldName, string $schoolCode): ?string {
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Logo upload failed (error code ' . $_FILES[$fieldName]['error'] . ').');
    }
    if ($_FILES[$fieldName]['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Logo must be 2 MB or smaller.');
    }
    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
        throw new RuntimeException('Logo must be a PNG, JPG, or WEBP image.');
    }
    // A quick sanity check that this is really an image, not just a renamed file.
    if (@getimagesize($_FILES[$fieldName]['tmp_name']) === false) {
        throw new RuntimeException('That file does not look like a valid image.');
    }
    $dir = __DIR__ . '/school_logos';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    // One fixed filename per school (school code), so re-uploading always replaces the
    // old logo cleanly instead of littering the folder with old versions.
    $filename = preg_replace('/[^A-Z0-9_]+/', '', strtoupper($schoolCode)) . '_' . time() . '.' . $ext;
    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $dir . '/' . $filename)) {
        throw new RuntimeException('Could not save the uploaded logo. Check folder permissions.');
    }
    return $filename;
}

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_school'])){
  $name=trim($_POST['school_name']??'');
  $code=strtoupper(trim($_POST['school_code']??''));
  $code=preg_replace('/[^A-Z0-9_]+/','_', $code);
  if($code==='') $code=strtoupper(substr(preg_replace('/[^A-Za-z0-9]+/','', $name),0,12));
  if($name==='') $err='School name is required.';
  elseif(!preg_match('/^[A-Z0-9_]{2,30}$/',$code)) $err='School code must contain 2-30 letters, numbers or underscores.';
  elseif(getSchoolByCode($code)) $err='That school code already exists.';
  else {
    try {
        $logoFile = saveSchoolLogo('school_logo', $code);
    } catch (RuntimeException $e) {
        $err = $e->getMessage();
    }
    if ($err === '') {
        $stmt = $conn->prepare("INSERT INTO schools(school_code,school_name,status,logo) VALUES(?,?,'active',?)");
        $stmt->bind_param('sss', $code, $name, $logoFile);
        if ($stmt->execute()) {
            $sid=$conn->insert_id;
            $grades=['Kids','1','2','3','4','5','6','7','8','9','10','11','12'];
            $gstmt=$conn->prepare("INSERT INTO school_grades(school_id,grade_name,grade_order,status) VALUES(?,?,?,'active')");
            foreach($grades as $i=>$g){$order=$i; $gstmt->bind_param('isi',$sid,$g,$order); $gstmt->execute();}
            $gstmt->close(); $msg='School created successfully. It is now available in user, student, teacher and import dropdowns.';
        } else $err=$stmt->error;
        $stmt->close();
    }
  }
}

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_logo'])){
  $code = strtoupper(trim($_POST['school_code_for_logo'] ?? ''));
  $school = getSchoolByCode($code);
  if (!$school) { $err = 'Unknown school.'; }
  else {
    try {
        $logoFile = saveSchoolLogo('existing_logo', $code);
        if ($logoFile === null) { $err = 'Choose an image file first.'; }
        else {
            $stmt = $conn->prepare("UPDATE schools SET logo=? WHERE id=?");
            $stmt->bind_param('si', $logoFile, $school['id']);
            $stmt->execute();
            $stmt->close();
            $msg = 'Logo updated for ' . htmlspecialchars($school['school_name']) . '.';
        }
    } catch (RuntimeException $e) {
        $err = $e->getMessage();
    }
  }
}

if(isset($_GET['toggle'])){
  $id=(int)$_GET['toggle']; $conn->query("UPDATE schools SET status=IF(status='active','inactive','active') WHERE id=$id"); header('Location: schools.php'); exit;
}
$schools=getSchools(false);
?>
<style>
.sm-wrap{padding:10px 5px 30px;color:#e2e8f0}.sm-card{background:#111f33;border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:20px;margin-bottom:16px}.sm-grid{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end}.sm-card input{background:#0f172a!important;color:#fff!important;border:1px solid #334155!important}.sm-table{width:100%;border-collapse:collapse}.sm-table th,.sm-table td{padding:12px;border-bottom:1px solid rgba(255,255,255,.08);text-align:center}.sm-table th{color:#93c5fd}.badge-on{background:#166534;padding:5px 10px;border-radius:999px}.badge-off{background:#7f1d1d;padding:5px 10px;border-radius:999px}.sm-logo{width:40px;height:40px;object-fit:contain;border-radius:8px;background:#0f172a;padding:3px}.logo-form{display:flex;gap:6px;align-items:center;justify-content:center}.logo-form input[type=file]{font-size:11px;max-width:130px}@media(max-width:700px){.sm-grid{grid-template-columns:1fr}}
</style>
<div class="sm-wrap">
<div class="sm-card">
    <h2>General Administration — Schools</h2>
    <p>Create and manage any number of schools. Every new school automatically gets Kids and Grades 1–12.</p>
    <?php if($msg)echo '<div class="alert alert-success">'.htmlspecialchars($msg).'</div>'; if($err)echo '<div class="alert alert-danger">'.htmlspecialchars($err).'</div>'; ?>
    <form method="post" enctype="multipart/form-data">
        <div class="sm-grid">
            <div><label>School Name</label><input class="form-control" name="school_name" placeholder="e.g. Future International School" required></div>
            <div><label>School Code</label><input class="form-control" name="school_code" placeholder="e.g. FUTURE" required></div>
            <div><label>Logo (optional, PNG/JPG/WEBP, max 2MB)</label><input class="form-control" type="file" name="school_logo" accept=".png,.jpg,.jpeg,.webp"></div>
            <button class="btn btn-success" name="add_school" type="submit">＋ Add School</button>
        </div>
    </form>
</div>
<div class="sm-card">
    <h3>Schools using the system</h3>
    <table class="sm-table">
        <thead><tr><th>Logo</th><th>#</th><th>School</th><th>Code</th><th>Students</th><th>Teachers</th><th>Users</th><th>Grades</th><th>Status</th><th>Action</th><th>Update Logo</th></tr></thead>
        <tbody>
        <?php foreach($schools as $i=>$s){
            $code=$conn->real_escape_string($s['school_code']);
            $students=adminScalar("SELECT COUNT(*) FROM students WHERE school_scope='$code' AND st_statue='بەردەوام'");
            $teachers=adminScalar("SELECT COUNT(*) FROM teachers WHERE school_scope='$code'");
            $users=adminScalar("SELECT COUNT(*) FROM users WHERE school_scope='$code'");
            $grades=getSchoolGrades($s['school_code']);
            echo '<tr>';
            echo '<td><img class="sm-logo" src="'.htmlspecialchars(schoolLogoUrl($s['school_code'])).'" alt="Logo"></td>';
            echo '<td>'.($i+1).'</td><td>'.htmlspecialchars($s['school_name']).'</td><td>'.htmlspecialchars($s['school_code']).'</td><td>'.number_format($students).'</td><td>'.number_format($teachers).'</td><td>'.number_format($users).'</td><td>'.count($grades).'</td><td><span class="'.($s['status']==='active'?'badge-on':'badge-off').'">'.htmlspecialchars($s['status']).'</span></td>';
            echo '<td><a class="btn btn-sm btn-warning" href="schools.php?toggle='.$s['id'].'">'.($s['status']==='active'?'Disable':'Enable').'</a>
                    <a class="btn btn-sm btn-primary" href="grades.php?school='.urlencode($s['school_code']).'">Grades</a></td>';
            echo '<td><form class="logo-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="school_code_for_logo" value="'.htmlspecialchars($s['school_code'],ENT_QUOTES).'">
                    <input type="file" name="existing_logo" accept=".png,.jpg,.jpeg,.webp" required>
                    <button class="btn btn-sm btn-outline-info" type="submit" name="update_logo">Save</button>
                  </form></td>';
            echo '</tr>';
        } ?>
        </tbody>
    </table>
</div>
</div>
<?php include "admin_footer.php"; ?>
