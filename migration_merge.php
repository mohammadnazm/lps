<?php
/**
 * ONE-TIME DATABASE MERGER
 *
 * This utility merges the existing lozan_tomar (grades 10-12) and
 * mahwi_lozan (Kids / grades 1-9) databases into one database.
 *
 * SECURITY: Run this file only on a private/local server, then DELETE it.
 */
set_time_limit(0);
ini_set('display_errors','1');

$tables = ['students','users','acc_con','mark_con','attendance','givenclass','lozanstaff','staffclasscon','teachers','subjects','st_marks'];
$message=''; $error='';

function h($v){ return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function connectDb($host,$user,$pass,$name){
    $c=@new mysqli($host,$user,$pass,$name);
    if($c->connect_error) throw new Exception('Database connection failed for '.h($name).': '.$c->connect_error);
    $c->set_charset('utf8mb4'); return $c;
}
function tableExists($c,$table){
    $t=$c->real_escape_string($table); $r=$c->query("SHOW TABLES LIKE '$t'"); return $r&&$r->num_rows>0;
}
function columns($c,$table){
    $out=[]; $r=$c->query("SHOW COLUMNS FROM `".$c->real_escape_string($table)."`"); if(!$r) throw new Exception($c->error); while($x=$r->fetch_assoc()) $out[]=$x['Field']; return $out;
}
function ensureScopeColumn($c,$table){
    $tables=['students','users','teachers','lozanstaff','givenclass','staffclasscon','subjects','mark_con','acc_con'];
    if(!in_array($table,$tables,true)) return;
    $cols=columns($c,$table);
    if(!in_array('school_scope',$cols,true)) $c->query("ALTER TABLE `$table` ADD `school_scope` VARCHAR(20) NOT NULL DEFAULT 'LOZAN'");
}
function cloneTable($src,$dst,$table){
    if(!tableExists($src,$table)) return false;
    if(!tableExists($dst,$table)){
        $r=$src->query("SHOW CREATE TABLE `$table`"); if(!$r) throw new Exception($src->error);
        $row=$r->fetch_assoc(); $create=$row['Create Table']??array_values($row)[1];
        if(!$dst->query($create)) throw new Exception('Create '.$table.' failed: '.$dst->error);
    }
    ensureScopeColumn($dst,$table); return true;
}
function insertRow($dst,$table,$row,$forcedScope,$maps){
    if(!$row) return null;
    $cols=columns($dst,$table); $data=[];
    foreach($cols as $col){
        if($col==='id' && array_key_exists('id',$row)) continue;
        if($col==='school_scope') { $data[$col]=$forcedScope; continue; }
        if(!array_key_exists($col,$row)) { $data[$col]=null; continue; }
        $v=$row[$col];
        if($table==='attendance' && $col==='student_id' && isset($maps['students'][(string)$v])) $v=$maps['students'][(string)$v];
        if($table==='st_marks' && $col==='st_id' && isset($maps['students'][(string)$v])) $v=$maps['students'][(string)$v];
        if($table==='givenclass' && $col==='t_id' && isset($maps['teachers'][(string)$v])) $v=$maps['teachers'][(string)$v];
        if($table==='acc_con' && $col==='acc_id' && isset($maps['users'][(string)$v])) $v=$maps['users'][(string)$v];
        $data[$col]=$v;
    }
    // Avoid duplicate reference/config rows where the two original systems used the same record.
    if($table==='subjects' && isset($data['sb_name'])){
        $n=$dst->real_escape_string((string)$data['sb_name']);
        $q=$dst->query("SELECT id FROM subjects WHERE sb_name='$n' AND school_scope='".$dst->real_escape_string($forcedScope)."' LIMIT 1");
        if($q && $q->num_rows) return (int)$q->fetch_assoc()['id'];
    }
    if($table==='staffclasscon' && isset($data['class_name'])){
        $n=$dst->real_escape_string((string)$data['class_name']);
        $q=$dst->query("SELECT id FROM staffclasscon WHERE class_name='$n' AND school_scope='".$dst->real_escape_string($forcedScope)."' LIMIT 1");
        if($q && $q->num_rows) return (int)$q->fetch_assoc()['id'];
    }
    // Login names must be unique inside the unified application. Preserve the original name whenever possible.
    if($table==='users' && isset($data['u_name'])){
        $n=$dst->real_escape_string((string)$data['u_name']);
        $q=$dst->query("SELECT id FROM users WHERE u_name='$n' LIMIT 1");
        if($q && $q->num_rows){
            $base=(string)$data['u_name']; $candidate=$base.'_'.$forcedScope; $i=2;
            while(true){ $cn=$dst->real_escape_string($candidate); $qq=$dst->query("SELECT id FROM users WHERE u_name='$cn' LIMIT 1"); if(!$qq||!$qq->num_rows) break; $candidate=$base.'_'.$forcedScope.$i++; }
            $data['u_name']=$candidate;
        }
    }
    if($table==='teachers' && isset($data['t_name'])){
        $n=$dst->real_escape_string((string)$data['t_name']);
        $q=$dst->query("SELECT id FROM teachers WHERE t_name='$n' LIMIT 1");
        if($q && $q->num_rows){
            $base=(string)$data['t_name']; $candidate=$base.'_'.$forcedScope; $i=2;
            while(true){ $cn=$dst->real_escape_string($candidate); $qq=$dst->query("SELECT id FROM teachers WHERE t_name='$cn' LIMIT 1"); if(!$qq||!$qq->num_rows) break; $candidate=$base.'_'.$forcedScope.$i++; }
            $data['t_name']=$candidate;
        }
    }
    $names=array_keys($data); $vals=[];
    foreach($data as $v){ $vals[] = is_null($v)?'NULL':"'".$dst->real_escape_string((string)$v)."'"; }
    $sql="INSERT INTO `$table` (`".implode('`,`',$names)."`) VALUES (".implode(',',$vals).")";
    if(!$dst->query($sql)) throw new Exception('Insert '.$table.' failed: '.$dst->error);
    return $dst->insert_id;
}
function migrateSource($src,$dst,$scope,$tables){
    $maps=['students'=>[],'users'=>[],'teachers'=>[]];
    foreach($tables as $table){ if(tableExists($src,$table)) cloneTable($src,$dst,$table); }
    // First copy independent identity tables so foreign keys can be remapped.
    foreach(['students','users','teachers','lozanstaff','subjects','mark_con','staffclasscon'] as $table){
        if(!tableExists($src,$table)) continue;
        $r=$src->query("SELECT * FROM `$table`"); if(!$r) throw new Exception($src->error);
        while($row=$r->fetch_assoc()){
            $old=$row['id']??null; $new=insertRow($dst,$table,$row,$scope,$maps);
            if($old!==null && array_key_exists($table,$maps)) $maps[$table][(string)$old]=$new;
        }
    }
    foreach(['givenclass','acc_con','attendance','st_marks'] as $table){
        if(!tableExists($src,$table)) continue;
        $r=$src->query("SELECT * FROM `$table`"); if(!$r) throw new Exception($src->error);
        while($row=$r->fetch_assoc()) insertRow($dst,$table,$row,$scope,$maps);
    }
    return $maps;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        if(($_POST['confirm']??'')!=='MERGE-LOZAN-MAHWI') throw new Exception('Type MERGE-LOZAN-MAHWI exactly to start.');
        $dh=trim($_POST['target_host']); $du=trim($_POST['target_user']); $dp=$_POST['target_pass']; $dn=trim($_POST['target_db']);
        $lh=trim($_POST['lozan_host']); $lu=trim($_POST['lozan_user']); $lp=$_POST['lozan_pass']; $ln=trim($_POST['lozan_db']);
        $mh=trim($_POST['mahwi_host']); $mu=trim($_POST['mahwi_user']); $mp=$_POST['mahwi_pass']; $mn=trim($_POST['mahwi_db']);
        $dst=connectDb($dh,$du,$dp,$dn); $lozan=connectDb($lh,$lu,$lp,$ln); $mahwi=connectDb($mh,$mu,$mp,$mn);
        $dst->query('SET FOREIGN_KEY_CHECKS=0');
        foreach($tables as $table) cloneTable($lozan,$dst,$table);
        $dst->query('SET FOREIGN_KEY_CHECKS=1');
        // Existing target rows are treated as LOZAN. If this is a fresh cloned target, this copies them below.
        $lozanRows=[];
        foreach($tables as $table){
            if(!tableExists($lozan,$table)) continue;
            // Only copy LOZAN once; duplicate execution should NOT be repeated.
            $r=$lozan->query("SELECT * FROM `$table`"); if(!$r) throw new Exception($lozan->error);
            while($row=$r->fetch_assoc()) $lozanRows[$table][]=$row;
        }
        $maps=['students'=>[],'users'=>[],'teachers'=>[]];
        foreach(['students','users','teachers','lozanstaff','subjects','mark_con','staffclasscon'] as $table){
            foreach(($lozanRows[$table]??[]) as $row){ $old=$row['id']??null; $new=insertRow($dst,$table,$row,'LOZAN',$maps); if($old!==null&&isset($maps[$table])) $maps[$table][(string)$old]=$new; }
        }
        foreach(['givenclass','acc_con','attendance','st_marks'] as $table){ foreach(($lozanRows[$table]??[]) as $row) insertRow($dst,$table,$row,'LOZAN',$maps); }
        // Mahwi rows receive new IDs; dependent records are remapped automatically.
        migrateSource($mahwi,$dst,'MAHWI',$tables);
        $dst->query("ALTER TABLE students MODIFY school_scope VARCHAR(20) NOT NULL DEFAULT 'LOZAN'");
        $message='Merge completed successfully. Verify the counts, then delete migration_merge.php and configure config.php for the target database.';
        $dst->close(); $lozan->close(); $mahwi->close();
    }catch(Throwable $e){ $error=$e->getMessage(); }
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>LOZAN Database Merger</title><style>body{font-family:Arial;background:#0f172a;color:#e2e8f0;margin:0;padding:30px}.box{max-width:1000px;margin:auto;background:#1e293b;padding:25px;border-radius:18px}.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}fieldset{border:1px solid #475569;border-radius:12px;padding:15px}legend{font-weight:700;color:#7dd3fc}input{width:100%;box-sizing:border-box;padding:10px;margin:6px 0;border-radius:8px;border:1px solid #475569;background:#0f172a;color:#fff}.btn{background:#22c55e;border:0;color:#fff;padding:13px 20px;border-radius:9px;font-weight:700;cursor:pointer}.warn{background:#451a03;padding:12px;border-radius:10px}.ok{background:#14532d;padding:12px;border-radius:10px}@media(max-width:800px){.grid{grid-template-columns:1fr}}</style></head><body><div class="box"><h1>LOZAN Unified Database Merger</h1><p>Copies your existing <b>LOZAN 10–12</b> and <b>MAHWI Kids/1–9</b> databases into one database and remaps student/teacher/user IDs for dependent records.</p><?php if($error)echo '<div class="warn">'.h($error).'</div>'; if($message)echo '<div class="ok">'.h($message).'</div>'; ?><form method="post"><div class="grid"><fieldset><legend>Target / New Database</legend><input name="target_host" placeholder="Host" value="localhost" required><input name="target_user" placeholder="User" required><input name="target_pass" type="password" placeholder="Password"><input name="target_db" placeholder="Database name" required></fieldset><fieldset><legend>LOZAN Source</legend><input name="lozan_host" placeholder="Host" value="localhost" required><input name="lozan_user" placeholder="User" required><input name="lozan_pass" type="password" placeholder="Password"><input name="lozan_db" placeholder="lozan_tomar" value="lozan_tomar" required></fieldset><fieldset><legend>MAHWI Source</legend><input name="mahwi_host" placeholder="Host" value="localhost" required><input name="mahwi_user" placeholder="User" required><input name="mahwi_pass" type="password" placeholder="Password"><input name="mahwi_db" placeholder="mahwi_lozan" value="mahwi_lozan" required></fieldset></div><p class="warn"><b>Important:</b> Take backups first. Run this only once against an empty/new target database. When finished, delete this file.</p><input name="confirm" placeholder="Type MERGE-LOZAN-MAHWI" required><button class="btn" type="submit">Start One-Time Merge</button></form></div></body></html>
