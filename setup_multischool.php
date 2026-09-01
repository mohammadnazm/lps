<?php
require_once 'db_connection.php';
if(!isset($_GET['run']) || $_GET['run']!=='YES'){die('<h2>Multi-school setup</h2><p>Open this page with <b>?run=YES</b> once, after taking a database backup.</p>');}

// Add school_scope to legacy tables when it is missing.
$scopedTables=['students','users','teachers','lozanstaff','givenclass','staffclasscon','subjects','mark_con','acc_con'];
foreach($scopedTables as $table){
  $chk=$conn->query("SHOW COLUMNS FROM `$table` LIKE 'school_scope'");
  if($chk && $chk->num_rows===0){
    if(!$conn->query("ALTER TABLE `$table` ADD COLUMN school_scope VARCHAR(30) NOT NULL DEFAULT 'LOZAN'")) die('Could not add school_scope to '.htmlspecialchars($table).': '.htmlspecialchars($conn->error));
  }
}

$queries=[];
$queries[]="CREATE TABLE IF NOT EXISTS schools (id INT UNSIGNED NOT NULL AUTO_INCREMENT,school_code VARCHAR(30) NOT NULL,school_name VARCHAR(150) NOT NULL,status ENUM('active','inactive') NOT NULL DEFAULT 'active',created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(id),UNIQUE KEY uq_school_code(school_code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$queries[]="CREATE TABLE IF NOT EXISTS school_grades (id INT UNSIGNED NOT NULL AUTO_INCREMENT,school_id INT UNSIGNED NOT NULL,grade_name VARCHAR(50) NOT NULL,grade_order INT NOT NULL DEFAULT 0,status ENUM('active','inactive') NOT NULL DEFAULT 'active',PRIMARY KEY(id),UNIQUE KEY uq_school_grade(school_id,grade_name),CONSTRAINT fk_school_grade_school FOREIGN KEY(school_id) REFERENCES schools(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
foreach($queries as $q) if(!$conn->query($q)) die('Setup failed: '.htmlspecialchars($conn->error));
foreach([['LOZAN','LOZAN'],['MAHWI','MAHWI']] as $x){$a=$conn->real_escape_string($x[0]);$b=$conn->real_escape_string($x[1]);$conn->query("INSERT INTO schools(school_code,school_name,status) VALUES('$a','$b','active') ON DUPLICATE KEY UPDATE school_name=VALUES(school_name)");}
$r=$conn->query("SELECT id,school_code FROM schools WHERE school_code IN ('LOZAN','MAHWI')");while($r&&$s=$r->fetch_assoc()){$grades=($s['school_code']==='LOZAN')?['10','11','12']:['Kids','1','2','3','4','5','6','7','8','9'];$st=$conn->prepare("INSERT IGNORE INTO school_grades(school_id,grade_name,grade_order,status) VALUES(?,?,?,'active')");foreach($grades as $i=>$g){$st->bind_param('isi',$s['id'],$g,$i);$st->execute();}$st->close();}
// If a legacy merged database used class-based separation, repair the obvious Mahwi rows.
$conn->query("UPDATE students SET school_scope='MAHWI' WHERE (LOWER(st_class)='kids' OR st_class IN ('1','2','3','4','5','6','7','8','9')) AND (school_scope='LOZAN' OR school_scope='')");
// Keep related legacy rows unchanged when their source database already had a school_scope.

// Create the dedicated platform owner account if it does not already exist.
// Login: generaladmin / Admin@12345
// This account is separate from every school's Admin account.
$gaName = 'generaladmin';
$gaPass = 'Admin@12345';
$gaEsc = $conn->real_escape_string($gaName);
$checkGA = $conn->query("SELECT id FROM users WHERE u_name='$gaEsc' LIMIT 1");
if (!$checkGA || $checkGA->num_rows===0) {
  $gp = $conn->real_escape_string($gaPass);
  $conn->query("INSERT INTO users(u_name,u_pass,u_role,u_access,school_scope) VALUES('$gaEsc','$gp','GeneralAdmin','ALL','ALL')");
} else {
  $conn->query("UPDATE users SET u_role='GeneralAdmin',u_access='ALL',school_scope='ALL' WHERE u_name='$gaEsc'");
}

echo '<h2>Multi-school setup completed.</h2><p>Delete setup_multischool.php from the server now.</p><p><a href="schools.php">Open Schools</a></p>';
