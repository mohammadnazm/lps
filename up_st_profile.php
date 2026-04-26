<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "db_connection.php";
?>
<?php
if (isset($_POST['up_st_data'])) {
    echo "<div class='alert alert-success' role='alert'>";
    updateSTdata(
        $_GET['did'],
        $_POST['stnmmm'],
        $_POST['stmm'],
        $_POST['nation'],
        $_POST['religion'],
        $_POST['citiz'],
        $_POST['type_id'],
        $_POST['idnum'],
        $_POST['st_class'],
        $_POST['st_gp'],
        $_POST['st_type'],
        $_POST['stprice'],
        $_POST['st_fak'],
        $_POST['st_status'],
        $_POST['ftell'],
        $_POST['mtell'],
        $_POST['stell'],
        $_POST['numbofbro'],
        $_POST['numofsis'],
        $_POST['ord'],
        $_POST['bdst'],
        $_POST['gender'],
        $_POST['bloodg'],
        $_POST['homeloc'],
        $_POST['stprice1'],
        $_POST['stprice2'],
        $_POST['stprice3'],
        $_POST['stimagenm'],
        $_POST['cl_size'],
        $_POST['stfilenm']
    );
    echo "</div>";
}
?>
<?php
if (isset($_POST["upidf"])) {
    $id = intval($_GET['did']);
    $getImg = mysqli_query($conn, "SELECT st_id_file FROM students WHERE id = $id");
    $row = mysqli_fetch_assoc($getImg);
    if (!empty($row['st_id_file'])) {
        $imgPath2 = "id_data/" . $row['st_id_file'];
        if (file_exists($imgPath2)) {
            unlink($imgPath2);
        }
    }
    echo "<div class='alert alert-success' role='alert'>";
    $filename2 = $_FILES['st_id_file_in']['name'];
    $tmpname2  = $_FILES['st_id_file_in']['tmp_name'];
    move_uploaded_file($tmpname2, "id_data/" . $filename2);
    updateImageAndFilesOfSt($_GET['did'], $filename2);
    echo "</div>";
}
?>
<?php
if (isset($_POST["upstpro"])) {
    $id = intval($_GET['did']);
    $getImg = mysqli_query($conn, "SELECT st_img FROM students WHERE id = $id");
    $row = mysqli_fetch_assoc($getImg);
    if (!empty($row['st_img'])) {
        $imgPath2 = "st_image/" . $row['st_img'];
        if (file_exists($imgPath2)) {
            unlink($imgPath2);
        }
    }
    echo "<div class='alert alert-success' role='alert'>";
    $filename2 = $_FILES['st_p_img']['name'];
    $tmpname2  = $_FILES['st_p_img']['tmp_name'];
    move_uploaded_file($tmpname2, "st_image/" . $filename2);
    updateImageAndFilesOfSt2($_GET['did'], $filename2);
    echo "</div>";
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="sorce/css/bootstrap.min.css">
    <title>Student Profile</title>
</head>

<?php
$stdList = getDhByID("students", $_GET['did']);
if ($stdList->num_rows > 0) {
    while ($row = $stdList->fetch_assoc()) {
        $name = $row['st_name'];
        $mname = $row['st_m_name'];
        $stimg = $row['st_img'];
        $bddate = $row['st_bd_date'];
        $bloofgr = $row['st_b_group'];
        $nation = $row['st_nation'];
        $religion = $row['st_religion'];
        $gender = $row['st_gender'];
        $numbro = $row['n_bro'];
        $numsis = $row['n_sis'];
        $storder = $row['st_bd_order'];
        $locationh = $row['st_home_loc'];
        $avgm = $row['st_avg_mark'];
        $scnm = $row['last_s_name'];
        $fildyears = $row['st_f_year'];
        $ftell = $row['f_tell'];
        $mtell = $row['m_tell'];
        $stellen = $row['st_tell'];
        $citizen = $row['st_citiiz'];
        $typeofid = $row['type_of_id'];
        $idnum = $row['st_id_number'];
        $idfile = $row['st_id_file'];
        $stclass = $row['st_class'];
        $stgroup = $row['st_group'];
        $stype = $row['st_type'];
        $regdate = $row['st_date'];
        $stpay = $row['st_price'];
        $status = $row['st_statue'];
        $stfaculty = $row['st_faculty'];
        $stlsc = $row['last_s_name'];
        $stlsc1 = $row['st_avg_mark'];
        $stlsc2 = $row['st_f_year'];
        $imgname = $row['st_img'];
        $stsize = $row['st_size'];
    }
}
?>









<body>
    <div style="margin:0px">
        <div style="display:flex;gap:10px;justify-content:space-between;padding:5px;margin:10px;background-color:blanchedalmond;">
            <img style="width:100px;height:100px;" src="images\Lozan Logo.png">
            <h1 style="text-align:center;color:darkblue">پرۆفایلی قوتابی</h1>
            <img style="width:100px;height:100px;" src="images\Lozan Logo.png">
        </div>
        <div style="display:flex;gap:10px;justify-content:space-between;padding:5px;margin:10px;">
            <!-- IMAGE -->
            <div style="border:none">
                <?php
                $path = "st_image/";
                $extensions = ['jpg', 'jpeg', 'png', 'gif'];
                $imageFile = '';

                // 1️⃣ Check if filename already has extension
                if (pathinfo($stimg, PATHINFO_EXTENSION)) {

                    if (file_exists($path . $stimg)) {
                        $imageFile = $stimg;
                    }
                } else {

                    // 2️⃣ If no extension, try all allowed extensions
                    foreach ($extensions as $ext) {
                        if (file_exists($path . $stimg . '.' . $ext)) {
                            $imageFile = $stimg . '.' . $ext;
                            break;
                        }
                    }
                }

                // 3️⃣ Display image or default
                if ($imageFile) {
                    echo '<img style="width:auto;height:150px;" src="' . $path . $imageFile . '" alt="">';
                } else {
                    echo '<img style="width:auto;height:150px;" src="' . $path . 'default.png" alt="">';
                }
                ?><form method="post" enctype="multipart/form-data">
                    <input type="file" name="st_p_img">
                    <button class="btn btn-warning" name="upstpro">Update</button>
                </form>
            </div>
            <!-- ID INFO -->
            <div>
                <h4 style="text-align:center;color:darkblue">زانیاری ناسنامە</h4>
                <hr>
                <form action="" method="post">
                    <div style="display:flex;flex-direction:column;gap:6px">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <select class="form-control card shadow-sm" name="nation" required>
                                <option value="" <?php if ($nation == null) echo "selected"; ?>>Nation</option>
                                <option value="کورد" <?php if ($nation == "کورد") echo "selected"; ?>>کورد</option>
                                <option value="عەرەب" <?php if ($nation == "عەرەب") echo "selected"; ?>>عەرەب</option>
                                <option value="تورك" <?php if ($nation == "تورك") echo "selected"; ?>>تورك</option>
                                <option value="فارس" <?php if ($nation == "فارس") echo "selected"; ?>>فارس</option>
                                <option value="ئەرمەنی" <?php if ($nation == "ئەرمەنی") echo "selected"; ?>>ئەرمەنی</option>
                                <option value="کلندانی" <?php if ($nation == "کلندانی") echo "selected"; ?>>کلندانی</option>
                                <option value="سریانی" <?php if ($nation == "سریانی") echo "selected"; ?>>سریانی</option>
                                <option value="ئاشوری" <?php if ($nation == "ئاشوری") echo "selected"; ?>>ئاشوری</option>
                                <option value="تورکمانی" <?php if ($nation == "تورکمانی") echo "selected"; ?>>تورکمانی</option>
                                <option value="ئەلمانی" <?php if ($nation == "ئەلمانی") echo "selected"; ?>>ئەلمانی</option>
                                <option value="نەتەوەی تر" <?php if ($nation == "نەتەوەی تر") echo "selected"; ?>>نەتەوەی تر</option>
                            </select> <label style="min-width:120px;text-align:right;">نەتەوە</label>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <select class="form-control card shadow-sm" name="religion" required>
                                <option value="" <?php if ($religion == null) echo "selected"; ?>>Religion</option>
                                <option value="ئیسلام" <?php if ($religion == "ئیسلام") echo "selected"; ?>>ئیسلام</option>
                                <option value="مەسیحی" <?php if ($religion == "مەسیحی") echo "selected"; ?>>مەسیحی</option>
                                <option value="ئیزیدی" <?php if ($religion == "ئیزیدی") echo "selected"; ?>>ئیزیدی</option>
                                <option value="زەردەشتی" <?php if ($religion == "زەردەشتی") echo "selected"; ?>>زەردەشتی</option>
                                <option value="کاکەیی" <?php if ($religion == "کاکەیی") echo "selected"; ?>>کاکەیی</option>
                                <option value="هیتر" <?php if ($religion == "هیتر") echo "selected"; ?>>هیتر</option>
                            </select> <label style="min-width:120px;text-align:right;">ئاین</label>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <select class="form-control card shadow-sm" name="citiz" required>
                                <option value="" value="" <?php if ($citizen == null) echo "selected"; ?>>Citizenship</option>
                                <option value="عێراق" value="" <?php if ($citizen == "عێراق") echo "selected"; ?>>عێراق</option>
                                <option value="سوریا" value="" <?php if ($citizen == "سوریا") echo "selected"; ?>>سوریا</option>
                                <option value="تورکیا" value="" <?php if ($citizen == "تورکیا") echo "selected"; ?>>تورکیا</option>
                                <option value="ئێران" value="" <?php if ($citizen == "ئێران") echo "selected"; ?>>ئێران</option>
                                <option value="هیتر" value="" <?php if ($citizen == "هیتر") echo "selected"; ?>>هیتر</option>
                            </select> <label style="min-width:120px;text-align:right;">رەگەزنامە</label>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <select class="form-control card shadow-sm" name="type_id" required>
                                <option value="" <?php if ($typeofid == null) echo "selected"; ?>>Type of ID</option>
                                <option value="کارتی نیشتیمانی" <?php if ($typeofid == "کارتی نیشتیمانی") echo "selected"; ?>>کارتی نیشتیمانی</option>
                                <option value="ناسنامەی باری شارستانی" <?php if ($typeofid == "ناسنامەی باری شارستانی") echo "selected"; ?>>ناسنامەی باری شارستانی</option>
                                <option value="پاسەپۆرت" <?php if ($typeofid == "پاسەپۆرت") echo "selected"; ?>>پاسەپۆرت</option>
                                <option value="ئیقامە" <?php if ($typeofid == "ئیقامە") echo "selected"; ?>>ئیقامە</option>
                            </select> <label style="min-width:120px;text-align:right;">جۆری ناسنامە</label>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <input class="form-control" style="text-align:center" name="idnum" value="<?php echo $idnum; ?>">
                            <label style="min-width:120px;text-align:right;">ژمارەی ناسنامە</label>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <input class="form-control" style="text-align:center" name="stimagenm" value="<?php echo $imgname; ?>">
                            <label style="min-width:120px;text-align:right;"> وێنە </label>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <input class="form-control" style="text-align:center" name="stfilenm" value="<?php echo $idfile; ?>">
                            <label style="min-width:120px;text-align:right;"> ناسنامە </label>
                        </div>
                    </div>
            </div>

            <!-- STUDENT INFO -->
            <div>
                <h4 style="text-align:center;color:darkblue">زانیاری قوتابی</h4>
                <hr>
                <div method="post" style="display:flex;flex-direction:column;gap:6px">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <select class="form-control card shadow-sm" name="st_class">
                            <option value="" <?php if ($stclass == null) echo "selected"; ?>>Class</option>
                            <option value="10" <?php if ($stclass == "10") echo "selected"; ?>>10</option>
                            <option value="11" <?php if ($stclass == "11") echo "selected"; ?>>11</option>
                            <option value="12" <?php if ($stclass == "12") echo "selected"; ?>>12</option>
                        </select>
                        <label style="min-width:120px;text-align:right;">پۆل</label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <select <?php if ($stgroup == null) echo " style='border-color:red;border-width:5px'"; ?> class="form-control card shadow-sm" name="st_gp">
                            <option value="" <?php if ($stgroup == null) echo "selected"; ?> disabled>Group</option>
                            <option value="A" <?php if ($stgroup == "A") echo "selected"; ?>>A</option>
                            <option value="B" <?php if ($stgroup == "B") echo "selected"; ?>>B</option>
                            <option value="C" <?php if ($stgroup == "C") echo "selected"; ?>>C</option>
                            <option value="D" <?php if ($stgroup == "D") echo "selected"; ?>>D</option>
                            <option value="E" <?php if ($stgroup == "E") echo "selected"; ?>>E</option>
                            <option value="F" <?php if ($stgroup == "F") echo "selected"; ?>>F</option>
                            <option value="G" <?php if ($stgroup == "G") echo "selected"; ?>>G</option>
                            <option value="H" <?php if ($stgroup == "H") echo "selected"; ?>>H</option>
                            <option value="I" <?php if ($stgroup == "I") echo "selected"; ?>>I</option>
                            <option value="J" <?php if ($stgroup == "J") echo "selected"; ?>>J</option>
                            <option value="K" <?php if ($stgroup == "K") echo "selected"; ?>>K</option>
                            <option value="L" <?php if ($stgroup == "L") echo "selected"; ?>>L</option>
                            <option value="M" <?php if ($stgroup == "M") echo "selected"; ?>>M</option>
                            <option value="N" <?php if ($stgroup == "N") echo "selected"; ?>>N</option>
                            <option value="O" <?php if ($stgroup == "O") echo "selected"; ?>>O</option>
                            <option value="P" <?php if ($stgroup == "P") echo "selected"; ?>>P</option>
                            <option value="Q" <?php if ($stgroup == "Q") echo "selected"; ?>>Q</option>
                            <option value="R" <?php if ($stgroup == "R") echo "selected"; ?>>R</option>
                            <option value="S" <?php if ($stgroup == "S") echo "selected"; ?>>S</option>
                            <option value="T" <?php if ($stgroup == "T") echo "selected"; ?>>T</option>
                            <option value="U" <?php if ($stgroup == "U") echo "selected"; ?>>U</option>
                            <option value="V" <?php if ($stgroup == "V") echo "selected"; ?>>V</option>
                            <option value="W" <?php if ($stgroup == "W") echo "selected"; ?>>W</option>
                            <option value="X" <?php if ($stgroup == "X") echo "selected"; ?>>X</option>
                            <option value="Y" <?php if ($stgroup == "Y") echo "selected"; ?>>Y</option>
                            <option value="Z" <?php if ($stgroup == "Z") echo "selected"; ?>>Z</option>
                        </select> <label style="min-width:120px;text-align:right;">هۆبە</label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <select class="form-control card shadow-sm" name="st_type">
                            <option value="ئاسایی" <?php if ($stype == "ئاسایی") echo "selected"; ?>>ئاسایی</option>
                            <option value="دەرەکی" <?php if ($stype == "دەرەکی") echo "selected"; ?>>دەرەکی</option>
                            <option value="تێکرای نمرە" <?php if ($stype == "تێکرای نمرە") echo "selected"; ?>>تێکرای نمرە</option>
                        </select>
                        <label style="min-width:120px;text-align:right;">جۆری قوتابی</label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input class="form-control" style="text-align:center" name="stprice" value="<?php echo $stpay; ?>">
                        <label style="min-width:120px;text-align:right;">کرێی خوێندن</label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <select class="form-control card shadow-sm" name="st_fak">
                            <option value="" <?php if ($stfaculty == null) echo "selected"; ?>disabled>Faculty</option>
                            <option value="زانستی" <?php if ($stfaculty == "زانستی") echo "selected style='border-color:red;border-width:5px'"; ?>>زانستی</option>
                            <option value="وێژەیی" <?php if ($stfaculty == "وێژەیی") echo "selected"; ?>>وێژەیی</option>
                        </select>
                        <label style="min-width:120px;text-align:right;">قسم</label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <select <?php if ($status == "بەجێهێشتوو") echo "selected style='border-color:red;border-width:5px'"; ?> class="form-control card shadow-sm" name="st_status">
                            <option value="" <?php if ($status == null) echo "selected"; ?>disabled>Status</option>
                            <option value="بەردەوام" <?php if ($status == "بەردەوام") echo "selected style='border-color:red;border-width:5px'"; ?>>بەردەوام</option>
                            <option value="بەجێهێشتوو" <?php if ($status == "بەجێهێشتوو") echo "selected"; ?>>بەجێهێشتوو</option>
                        </select>
                        <label style="min-width:120px;text-align:right;">حالەتی قوتابی</label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input class="form-control" style="text-align:center" name="stprice1" value="<?php echo $stlsc; ?>">
                        <label style="min-width:120px;text-align:right;">دوا قوتابخانە </label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input class="form-control" style="text-align:center" name="stprice2" value="<?php echo $stlsc1; ?>">
                        <label style="min-width:120px;text-align:right;">کۆنمرەی دواین سال </label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input class="form-control" style="text-align:center" name="stprice3" value="<?php echo $stlsc2; ?>">
                        <label style="min-width:120px;text-align:right;">چەندەمین سالە لەم پۆلە </label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <select class="form-control card shadow-sm" name="cl_size">
                            <option value="" <?php if ($stsize == "") echo "selected"; ?>>Size</option>
                            <option value="XS" <?php if ($stsize == "XS") echo "selected style='border-color:red;border-width:5px'"; ?>>XS</option>
                            <option value="Small" <?php if ($stsize == "Small") echo "selected style='border-color:red;border-width:5px'"; ?>>Small</option>
                            <option value="Medium" <?php if ($stsize == "Medium") echo "selected style='border-color:red;border-width:5px'"; ?>>Medium</option>
                            <option value="XL" <?php if ($stsize == "XL") echo "selected style='border-color:red;border-width:5px'"; ?>>XL</option>
                            <option value="2XL" <?php if ($stsize == "2XL") echo "selected style='border-color:red;border-width:5px'"; ?>>2XL</option>
                            <option value="3XL" <?php if ($stsize == "3XL") echo "selected style='border-color:red;border-width:5px'"; ?>>3XL</option>
                            <option value="4XL" <?php if ($stsize == "4XL") echo "selected style='border-color:red;border-width:5px'"; ?>>4XL</option>
                        </select>
                        <label style="min-width:120px;text-align:right;">جۆری قوتابی</label>
                    </div>
                </div>
            </div>
            <!-- PERSONAL INFO -->
            <div>
                <h4 style="text-align:center;color:darkblue">زانیاری کەسی</h4>
                <hr>

                <div style="display:flex;flex-direction:column;gap:6px">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input class="form-control" style="text-align:center" name="stnmmm" value="<?php echo $name; ?>">
                        <label style="min-width:120px;text-align:right;">ناوی قوتابی</label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input class="form-control" style="text-align:center" name="stmm" value="<?php echo $mname; ?>">
                        <label style="min-width:120px;text-align:right;">ناوی دایك</label>
                    </div>

                    <div style="display:flex;align-items:center;gap:10px;">
                        <input class="form-control" style="text-align:center" name="ftell" value="<?php echo $ftell; ?>">
                        <label style="min-width:120px;text-align:right;">مۆبایلی باوك</label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input class="form-control" style="text-align:center" name="mtell" value="<?php echo $mtell; ?>">
                        <label style="min-width:120px;text-align:right;">مۆبایلی دایك</label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input class="form-control" style="text-align:center" name="stell" value="<?php echo $stellen; ?>">
                        <label style="min-width:120px;text-align:right;">مۆبایلی قوتابی</label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input class="form-control" style="text-align:center" name="numbofbro" value="<?php echo $numbro; ?>">
                        <label style="min-width:120px;text-align:right;">ژمارەی برا </label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input class="form-control" style="text-align:center" name="numofsis" value="<?php echo $numsis; ?>">
                        <label style="min-width:120px;text-align:right;">ژمارەی خوشك </label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input class="form-control" style="text-align:center" name="ord" value="<?php echo $storder; ?>">
                        <label style="min-width:120px;text-align:right;">چەندەمین مندالە </label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input class="form-control" style="text-align:center" name="bdst" value="<?php echo $bddate; ?>">
                        <label style="min-width:120px;text-align:right;">بەرواری لە دایكبوون </label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <select class="form-control card shadow-sm" name="gender">
                            <option value="" <?php if ($gender == null) echo "selected style='border-color:red;border-width:5px'"; ?>>Gender</option>
                            <option value="male" <?php if ($gender == "Male") echo "selected"; ?>>Male</option>
                            <option value="female" <?php if ($gender == "Female") echo "selected"; ?>>Female</option>
                        </select> <label style="min-width:120px;text-align:right;">رەگەز </label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <select class="form-control card shadow-sm" name="bloodg">
                            <option value="" <?php if ($bloofgr == null) echo "selected style='border-color:red;border-width:5px'"; ?>>Blood Group</option>
                            <option value="A+" <?php if ($bloofgr == "A+") echo "selected"; ?>>A+</option>
                            <option value="A-" <?php if ($bloofgr == "A-") echo "selected"; ?>>A-</option>
                            <option value="AB+ <?php if ($bloofgr == "AB+") echo "selected"; ?>">AB+</option>
                            <option value="AB- <?php if ($bloofgr == "AB-") echo "selected"; ?>">AB-</option>
                            <option value="B+" <?php if ($bloofgr == "B+") echo "selected"; ?>>B+</option>
                            <option value="B-" <?php if ($bloofgr == "B-") echo "selected"; ?>>B-</option>
                            <option value="O+" <?php if ($bloofgr == "O+") echo "selected"; ?>>O+</option>
                            <option value="O-" <?php if ($bloofgr == "O-") echo "selected"; ?>>O-</option>
                        </select> <label style="min-width:120px;text-align:right;">گروپی خوێن </label>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input class="form-control" style="text-align:center" name="homeloc" value="<?php echo $locationh; ?>">
                        <label style="min-width:120px;text-align:right;">ناونیشان</label>
                    </div>
                    <button class="btn btn-warning col-12" type="submit" name="up_st_data">Update</button>
                </div>
                </form>
            </div>
        </div>








        <div style="width:560px;border:none;margin:20px;">
            <?php
            $path = "id_data/";
            $extensions = ['jpg', 'jpeg', 'png', 'gif', 'avif'];
            $imageFile = '';

            // 1️⃣ Check if filename already has extension
            if (pathinfo($idfile, PATHINFO_EXTENSION)) {

                if (file_exists($path . $idfile)) {
                    $imageFile = $idfile;
                }
            } else {

                // 2️⃣ If no extension, try all allowed extensions
                foreach ($extensions as $ext) {
                    if (file_exists($path . $idfile . '.' . $ext)) {
                        $imageFile = $idfile . '.' . $ext;
                        break;
                    }
                }
            }

            // 3️⃣ Display image or default
            if ($imageFile) {
                echo '<img style="width:auto;height:180px;" src="' . $path . $imageFile . '" alt="">';
            } else {
                echo '<img style="width:auto;height:180px;" src="' . $path . 'default.png" alt="">';
            }
            ?>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="st_id_file_in">
                <button class="btn btn-warning" name="upidf">Update</button>
            </form>
        </div>











        <h6 style="padding:10px;color:darkblue;"><?php echo $regdate; ?></h6>

    </div>
</body>

</html>