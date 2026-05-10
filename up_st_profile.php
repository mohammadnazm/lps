<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "db_connection.php";
?>

<?php
/* ================= UPDATE DATA ================= */
if (isset($_POST['up_st_data'])) {

    echo "<div class='alert alert-success'>";

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
        $_POST['stfilenm'],
        $_POST['extrainfo']
    );

    echo "Updated Successfully";
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

<body>

<div style="margin:0px">

<!-- ================= HEADER ================= -->
<div style="display:flex;justify-content:space-between;padding:5px;margin:10px;background-color:blanchedalmond;">
    <img style="width:100px;height:100px;" src="images/Lozan Logo.png">
    <h1 style="text-align:center;color:darkblue">پرۆفایلی قوتابی</h1>
    <img style="width:100px;height:100px;" src="images/Lozan Logo.png">
</div>

<?php
$stdList = getDhByID("students", $_GET['did']);
$row = $stdList->fetch_assoc();
?>

<!-- ========================================================= -->
<!-- ===================== MAIN UPDATE FORM ================== -->
<!-- ========================================================= -->

<form action="?did=<?php echo $_GET['did']; ?>" method="post">

<div style="display:flex;gap:10px;justify-content:space-between;padding:5px;margin:10px;">

<!-- ================= IMAGE (SEPARATE FORM) ================= -->
<div>

<?php
$path="st_image/";
if(file_exists($path.$row['st_img']))
    echo '<img style="height:150px" src="'.$path.$row['st_img'].'">';
else
    echo '<img style="height:150px" src="'.$path.'default.png">';
?>

<!-- IMAGE UPDATE FORM (KEEP SEPARATE) -->
<form method="post" enctype="multipart/form-data">
<input type="file" name="st_p_img">
<button class="btn btn-warning" name="upstpro">Update</button>
</form>

</div>

<!-- ================= ID INFO ================= -->
<div>

<h4 style="text-align:center;color:darkblue">زانیاری ناسنامە</h4>
<hr>

<select class="form-control" name="nation">
<option value="کورد" <?=($row['st_nation']=="کورد")?'selected':''?>>کورد</option>
<option value="عەرەب" <?=($row['st_nation']=="عەرەب")?'selected':''?>>عەرەب</option>
</select>

<input class="form-control" name="idnum"
value="<?php echo $row['st_id_number']; ?>">

<input class="form-control" name="stimagenm"
value="<?php echo $row['st_img']; ?>">

<input class="form-control" name="stfilenm"
value="<?php echo $row['st_id_file']; ?>">

<textarea class="form-control"
name="extrainfo"><?php echo $row['st_note']; ?></textarea>

</div>

<!-- ================= STUDENT INFO ================= -->
<div>

<h4 style="text-align:center;color:darkblue">زانیاری قوتابی</h4>
<hr>

<select class="form-control" name="st_class">
<option value="10" <?=($row['st_class']=="10")?'selected':''?>>10</option>
<option value="11" <?=($row['st_class']=="11")?'selected':''?>>11</option>
<option value="12" <?=($row['st_class']=="12")?'selected':''?>>12</option>
</select>

<select class="form-control" name="st_gp">
<option value="A" <?=($row['st_group']=="A")?'selected':''?>>A</option>
<option value="B" <?=($row['st_group']=="B")?'selected':''?>>B</option>
</select>

<select class="form-control" name="st_type">
<option value="ئاسایی" <?=($row['st_type']=="ئاسایی")?'selected':''?>>ئاسایی</option>
<option value="دەرەکی" <?=($row['st_type']=="دەرەکی")?'selected':''?>>دەرەکی</option>
</select>

<input class="form-control"
name="stprice"
value="<?php echo $row['st_price']; ?>">

</div>

</div>

<!-- ================= UPDATE BUTTON ================= -->
<div style="text-align:center;margin:20px;">
<button class="btn btn-success btn-lg" name="up_st_data">
Update Student Data
</button>
</div>

</form>
<!-- ================= END FORM ================= -->

</div>

</body>
</html>
