<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "db_connection.php";
?>






<?php
if (isset($_POST["upd"])) {
    echo "<div class='alert alert-success' role='alert'>";
    updateDisc($_GET['did'], $_POST["stdisc"]);
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
        $stfaculty = $row['st_faculty'];
        $stype = $row['st_type'];
        $regdate = $row['st_date'];
        $status = $row['st_statue'];
        $stpay = $row['st_price'];
        $stfaculty = $row['st_faculty'];
    }
}
?>

<body>
    <div style="margin: 0px">
        <div style="display:flex;gap:10px;justify-content: space-between;padding:5px;margin:10px;align-items: flex-start;background-color: blanchedalmond;">
            <img style="width: 100px;height: 100px;" src="images\Lozan Logo.png" alt="">
            <h1 style="text-align:center;color:darkblue">پرۆفایلی قوتابی</h1>
            <img style="width: 100px;height: 100px;" src="images\Lozan Logo.png" alt="">
        </div>
        <div style="display:flex;gap:10px;justify-content: space-between;padding:5px;margin:10px;align-items: flex-start;">
            <div style="border:solid">
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
                ?></div>
            <br>
            <div>
                <h4 style="text-align: center;color:darkblue">زانیاری ناسنامە</h4>
                <hr>
                <div style="display: flex;flex-direction: column;gap:6px">
                    <h6 style="text-align: right;"> نەتەوە : <?php echo "<t style='color:darkblue'>{$nation}</t>" ?></h6>
                    <h6 style="text-align: right;"> ئاین : <?php echo "<t style='color:darkblue'>{$religion}</t>" ?></h6>
                    <h6 style="text-align: right;"> رەگەزنامە : <?php echo "<t style='color:darkblue'>{$citizen}</t>" ?></h6>
                    <h6 style="text-align: right;"> جۆری ناسنامە : <?php echo "<t style='color:darkblue'>{$typeofid} </t>" ?></h6>
                    <h6 style="text-align: right;"> ژمارەی ناسنامە : <?php echo "<t style='color:darkblue'>{$idnum}</t>" ?></h6>
                </div>
            </div>
            <br>
            <div>
                <h4 style="text-align: center;color:darkblue">زانیاری قوتابی</h4>
                <hr>
                <div style="display: flex;flex-direction: column;gap:6px">
                    <h6 style="text-align: right;"> پۆڵ : <?php echo "<t style='color:darkblue'>{$stclass}</t>" ?></h6>
                    <h6 style="text-align: right;"><?php echo "<t style='color:darkblue'>{$stgroup}</t>" ?> : هۆبە </h6>
                    <h6 style="text-align: right;"> جۆری قوتابی : <?php echo "<t style='color:darkblue'>{$stype}</t>" ?></h6>
                    <h6 style="text-align: right;"> قسم : <?php echo "<t style='color:darkblue'>{$stfaculty}</t>" ?></h6>
                    <form action="" method="post" style="margin: 5px;display: flex;flex-direction: row;gap:5px"> <input style="text-align: center;width:100px;" class="form-control" name="stdisc" type="text" value="<?php echo $stpay ?>"> <button class="btn btn-info" name="upd" onclick="return confirm('دلنیایت لە پێدانی داشکان بەم قوتابیە ؟');">کرێی خوێندن</button></form>
                    <h6 style="text-align: right;"> دوا قوتابخانە : <?php echo "<t style='color:darkblue'>{$scnm}</t>" ?></h6>
                    <h6 style="text-align: right;"> کۆنمرەی دواین ساڵ : <?php echo "<t style='color:darkblue'>{$avgm}</t>" ?></h6>
                    <h6 style="text-align: right;"> <?php echo "<t style='color:darkblue'>{$fildyears}</t>" ?> : چەندەمین سالە لەم پۆلە</h6>
                </div>
            </div>
            <br>
            <div>
                <h4 style="text-align: center;color:darkblue">زانیاری کەسی</h4>
                <hr>
                <div style="display: flex;flex-direction: column;gap:10px;padding: 3px;">
                    <h6 style="text-align: right;"> ناوی قوتابی : <?php echo "<t style='color:darkblue'>{$name}</t>" ?></h6>
                    <h6 style="text-align: right;"> <?php echo "<span style='color:darkblue; direction:ltr; unicode-bidi:bidi-override;'>{$mname}</span>" ?> : ناوی سیانی دایك</h6>
                    <h6 style="text-align: right;"> <?php echo "<span style='color:darkblue; direction:ltr; unicode-bidi:bidi-override;'>{$ftell}</span>" ?> : ژمارە مۆبایل باوك</h6>
                    <h6 style="text-align: right;"> <?php echo "<span style='color:darkblue; direction:ltr; unicode-bidi:bidi-override;'>{$mtell}</span>" ?> : ژمارە مۆبایل دایك </h6>
                    <h6 style="text-align: right;"> <?php echo "<span style='color:darkblue; direction:ltr; unicode-bidi:bidi-override;'>{$stellen}</span>" ?> : ژمارە مۆبایل قوتابی</h6>
                    <h6 style="text-align: right;">ژمارەی برا : <?php echo "<t style='color:darkblue'>{$numbro}</t>" ?></h6>
                    <h6 style="text-align: right;"> ژمارەی خوشك : <?php echo "<t style='color:darkblue'>{$numsis}</t>" ?></h6>
                    <h6 style="text-align: right;"> چەندەمین مندالە : <?php echo "<t style='color:darkblue'>{$storder}</t>" ?></h6>
                    <h6 style="text-align: right;"> بەرواری لە دایکبوون : <?php echo "<t style='color:darkblue'>{$bddate}</t>" ?></h6>
                    <h6 style="text-align: right;"> <?php echo "<t style='color:darkblue'>{$gender}</t>" ?> : رەگەز </h6>
                    <h6 style="text-align: right;"> <?php echo "<t style='color:darkblue'>{$bloofgr}</t>" ?> : گرووپی خوێن </h6>
                    <h6 style="text-align: right;"> شوێنی نیشتەجێ بوون : <?php echo "<t style='color:darkblue'>{$locationh}</t>" ?></h6>
                </div>
            </div>
        </div>
        <!-- <div style="width: 560px;border:solid;margin-top:-5%;margin-left: 20px;">
            <img style="height: 150px;width:550PX;" src="id_data\<?PHP echo $idfile ?>" alt="">
        </div>
        <h6 style="padding: 10px;margin-left: 20px;margin-top:-0.5%"><?php echo "<t style='color:darkblue'>{$regdate}</t>" ?></h6> -->
    </div>

</body>

</html>