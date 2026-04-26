<?php
$conn = new mysqli('localhost', 'root', '12345678', 'lozan_tomar');
mysqli_set_charset($conn, "utf8mb4");
if ($conn->connect_error) {
  die("Error is: " . $conn->connect_error);
}

function insertStudents($tv, $as, $yt, $qw, $we, $rt, $cv, $zx, $adas, $xcv, $mcx, $lkoi, $usxa, $bva, $nbc, $mnr, $bcvs, $kjius, $sdrza, $posq, $trqs, $bvfgsd, $mkas, $uyw, $azss, $mnas, $uioc, $kmns, $gtraz)
{
  global $conn;
  $sql = "INSERT INTO students(st_name,st_m_name,st_img,st_bd_date,st_b_group,st_nation,st_religion,st_gender,n_bro,n_sis,
    st_bd_order,st_home_loc,st_avg_mark,last_s_name,st_f_year,f_tell,m_tell,st_tell,st_citiiz,type_of_id,st_id_number,st_id_file,st_class,st_faculty,st_type,st_date,st_price,st_size,st_group) 
            VALUES ('$tv','$as','$yt','$qw','$we','$rt','$cv','$zx','$adas','$xcv','$mcx','$lkoi','$usxa','$bva','$nbc','$mnr',
            '$bcvs','$kjius','$sdrza','$posq','$trqs','$bvfgsd','$mkas','$uyw','$azss','$mnas','$uioc','$kmns','$gtraz')";
  if ($conn->query($sql) === TRUE) {
    echo "Student Added successfully. &#10004";
  } else {
    die("Error is: " . $conn->connect_error);
  }
}
function insertUsers($re, $yt, $iu, $bhs)
{
  global $conn;
  $sql = "INSERT INTO users(u_name,u_pass,u_role,u_access) VALUES ('$re','$yt','$iu','$bhs')";
  if ($conn->query($sql) === TRUE) {
    echo "Student Added successfully. &#10004";
  } else {
    die("Error is: " . $conn->connect_error);
  }
}
function getDh($tr)
{
  global $conn;
  $sql = "SELECT *  FROM $tr";
  return $conn->query($sql);
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

            WHERE attendance.date = '$dd' AND students.st_gender = '$trw'

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
            WHERE attendance.date BETWEEN '$dd' AND '$tre' AND students.st_gender = '$nba'
              AND attendance.status = 'absent'
            GROUP BY students.id
            ORDER BY total_absent DESC";

  return mysqli_query($conn, $sql);
}




function getDhForlogin($tr, $mns)
{
  global $conn;
  $sql = "SELECT *  FROM $tr WHERE u_name = '$mns'";
  return $conn->query($sql);
}
function getDhByID($tr, $brt)
{
  global $conn;
  $sql = "SELECT *  FROM $tr WHERE id = $brt";
  return $conn->query($sql);
}
function DeleteData($er, $yt)
{
  global $conn;
  $sql = "DELETE FROM $er WHERE id=$yt";
  return $conn->query($sql);
}
function getDhForAllSchool()
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_stschool FROM students WHERE st_statue = 'بەردەوام'";
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
  $sql = "SELECT COUNT(*) AS total_stschool FROM students WHERE st_price !='2,500,000' AND st_class = '12' ";
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
  $sql = "SELECT COUNT(*) AS total_stschool FROM students WHERE st_price !='2,000,000' AND st_class = '11' ";
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
  $sql = "SELECT COUNT(*) AS total_stschool FROM students WHERE st_price !='2,000,000' AND  st_class='10' ";
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
  $sql = "SELECT COUNT(*) AS total_students FROM students WHERE st_statue = 'بەردەوام' AND st_class='12'";
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
  $sql = "SELECT COUNT(*) AS total_g FROM students WHERE st_gender = 'female' AND st_class='12' AND st_statue = 'بەردەوام'";
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
  $sql = "SELECT COUNT(*) AS total_b FROM students WHERE st_gender = 'male' AND st_class='12' AND st_statue = 'بەردەوام'";
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
  $sql = "SELECT COUNT(*) AS total_sic FROM students WHERE st_faculty = 'زانستی' AND st_statue = 'بەردەوام' AND st_class='12'";
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
  $sql = "SELECT COUNT(*) AS total_lit FROM students WHERE st_faculty = 'وێژەیی' AND st_statue = 'بەردەوام' AND st_class='12'";
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
  $sql = "SELECT COUNT(*) AS total_tds FROM students WHERE st_statue = 'بەردەوام' AND st_class='12' AND (st_type = 'دەرەکی' OR st_type = 'تێکرای نمرە')";
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
        WHERE st_statue = 'بەردەوام'
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
        WHERE st_statue = 'بەردەوام'
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
  $sql = "SELECT COUNT(*) AS total_studentsE FROM students WHERE st_statue = 'بەردەوام' AND st_class='11'";
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
  $sql = "SELECT COUNT(*) AS total_studentsEG FROM students WHERE st_statue = 'بەردەوام' AND st_class='11' AND st_gender='Female'";
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
  $sql = "SELECT COUNT(*) AS total_studentsEB FROM students WHERE st_statue = 'بەردەوام' AND st_class='11' AND st_gender='Male'";
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
  $sql = "SELECT COUNT(*) AS total_studentsT FROM students WHERE st_statue = 'بەردەوام' AND st_class='10'";
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
  $sql = "SELECT COUNT(*) AS total_studentsTG FROM students WHERE st_statue = 'بەردەوام' AND st_class='10' AND st_gender='female'";
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
  $sql = "SELECT COUNT(*) AS total_studentsTB FROM students WHERE st_statue = 'بەردەوام' AND st_class='10' AND st_gender='male'";
  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsTB'];
  }
  return 0;
}
function updateDisc($uname, $upas)
{
  global $conn;
  $sql = "UPDATE students SET
                  st_price='$upas'
                  WHERE id='$uname'";

  if ($conn->query($sql) === TRUE) {
    echo "Discount Added successfully. &#10004";
  } else {
    die("Error is: " . $conn->connect_error);
  }
}
function updateStClass($uname, $upas)
{
  global $conn;
  $sql = "UPDATE students SET
                  st_group='$upas'
                  WHERE id='$uname'";

  if ($conn->query($sql) === TRUE) {
    echo "Class Updated successfully. &#10004";
  } else {
    die("Error is: " . $conn->connect_error);
  }
}
function updateSTdata($id, $st_name, $st_m_name, $nation, $religion, $citizen, $type_id, $id_number, $st_class, $st_group, $st_type, $st_price, $faculty, $status, $f_tell, $m_tell, $st_tell, $n_bro, $n_sis, $bd_order, $bd_date, $gender, $blood, $home_loc, $yt1, $yt2, $yt3, $mnhgasi, $bnsa, $iuw)
{
  global $conn;
  $sql = "UPDATE students SET
        st_name        = '$st_name',
        st_m_name      = '$st_m_name',
        st_nation      = '$nation',
        st_religion    = '$religion',
        st_citiiz      = '$citizen',
        type_of_id     = '$type_id',
        st_id_number   = '$id_number',
        st_class       = '$st_class',
        st_group       = '$st_group',
        st_type        = '$st_type',
        st_price       = '$st_price',
        st_faculty     = '$faculty',
        st_statue      = '$status',
        f_tell         = '$f_tell',
        m_tell         = '$m_tell',
        st_tell        = '$st_tell',
        n_bro          = '$n_bro',
        n_sis          = '$n_sis',
        st_bd_order    = '$bd_order',
        st_bd_date     = '$bd_date',
        st_gender      = '$gender',
        st_b_group     = '$blood',
        st_home_loc    = '$home_loc',
        last_s_name    = '$yt1',
        st_avg_mark    = '$yt2',
        st_f_year    = '$yt3',
        st_img     ='$mnhgasi',
        st_size = '$bnsa',
        st_id_file = '$iuw'
        WHERE id = '$id'
    ";
  if ($conn->query($sql) === TRUE) {
    echo "Data Updated successfully. &#10004";
  } else {
    die("Error is: " . $conn->connect_error);
  }
}
function updateImageAndFilesOfSt2($id, $home_loc)
{
  global $conn;
  $sql = "UPDATE students SET
        st_img        = '$home_loc'
        WHERE id = '$id'
    ";
  if ($conn->query($sql) === TRUE) {
    echo "ID Updated successfully. &#10004";
  } else {
    die("Error is: " . $conn->connect_error);
  }
}
function updateImageAndFilesOfSt($id, $home_loc)
{
  global $conn;
  $sql = "UPDATE students SET
        st_id_file        = '$home_loc'
        WHERE id = '$id'
    ";
  if ($conn->query($sql) === TRUE) {
    echo "ID Updated successfully. &#10004";
  } else {
    die("Error is: " . $conn->connect_error);
  }
}
function getDhForAllSAttCountGradeTB($ewq)
{
  global $conn;
  $sql = "SELECT COUNT(*) AS total_studentsAtt FROM attendance WHERE date= '$ewq'";
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
            AND students.st_class = '12'";

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
            AND students.st_class = '11'";

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
            AND students.st_class = '10'";

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
            AND students.st_gender = 'Female'";

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
            AND students.st_gender = 'Female' AND students.st_class='12'";

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
            AND students.st_gender = 'Female' AND students.st_class='11'";

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
            AND students.st_gender = 'Female' AND students.st_class='10'";

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
            AND students.st_gender = 'male'";

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
            AND students.st_gender = 'male' AND students.st_class='12'";

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
            AND students.st_gender = 'male' AND students.st_class='11'";

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
            AND students.st_gender = 'male' AND students.st_class='10'";

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
            AND students.st_faculty = 'زانستی'";

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
            AND students.st_faculty = 'وێژەیی'";

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
            AND (students.st_type = 'دەرەکی' OR students.st_type = 'تێکرای نمرە')";

  $result = $conn->query($sql);

  if ($result) {
    $row = $result->fetch_assoc();
    return $row['total_studentsTD'];
  }

  return 0;
}
function DeleteAllStData()
{
  global $conn;

  $sql = "DELETE FROM students";

  $result = $conn->query($sql);

  return $result;
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