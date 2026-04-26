<!DOCTYPE html>
<html lang="en">
<?php include "db_connection.php"; ?>







<?php
$stdList = getDhByID("attendance", $_GET['didup']);
if ($stdList->num_rows > 0) {
    while ($row = $stdList->fetch_assoc()) {
        $attdt = $row['date'];
    }
}
?>





<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Attendance</title>
    <link rel="stylesheet" href="sorce/css/bootstrap.min.css">
</head>



<?php

if(isset($_POST['upst']))
    {
        echo "<div class='alert alert-success' role='alert'>";
        upattdata($_POST['sttu'],$_POST['newdt'],$_GET['didup']);
        echo "</div>";
    }
?>


<body style="padding:10px">
    <h1 style="text-align: center;">Update Attendance</h1>
    <hr>
    <form action="" method="post">
        <div style="display: flex;flex-direction: column;gap:6px;width:50%" required>
            <input class="form-control card shadow-sm" name="newdt" value="<?php echo $attdt ?>" type="date">
            <select class="form-control card shadow-sm" name="sttu" required>
                <option value="absent" selected>نەهاتووە</option>
                <option value="permit">مۆلەت</option>
            </select>
            <button class="btn btn-info" name="upst" type="submit">Update</button>
        </div>
    </form>
</body>
</html>