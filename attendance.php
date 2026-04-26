<?php
session_start();
include "atten_header.php";


if (isset($_SESSION['msg'])) {
    echo "{$_SESSION['msg']}";
    unset($_SESSION['msg']);
}


/* ================= SAVE ATTENDANCE ================= */

if (isset($_POST["sub_atten"])) {

    if (isset($_POST['st_id']) && isset($_POST['st_atten'])) {

        $ids     = $_POST['st_id'];
        $status  = $_POST['st_atten'];

        $date = date("Y-m-d");

        foreach ($ids as $key => $sid) {

            $atten = $status[$key];

            // Skip present students
            if ($atten == "present") {
                continue;
            }

            // Prevent duplicate for same day
            $check = mysqli_query(
                $conn,
                "SELECT id FROM attendance 
                 WHERE student_id='$sid' AND date='$date'"
            );

            if (mysqli_num_rows($check) == 0) {

                mysqli_query(
                    $conn,
                    "INSERT INTO attendance
                    (student_id,status,date)
                    VALUES
                    ('$sid','$atten','$date')"
                );
            }
        }
    }
    $_SESSION['msg'] = "<script>alert('بە سەرکەوتوویی تۆمار کرا');</script>";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}


/* ================= GET STUDENTS ================= */

function getDhAttendance($table, $class, $group)
{
    global $conn;

    $sql = "SELECT * FROM $table 
            WHERE st_class='$class'
            AND st_group='$group'
            AND st_gender = '{$_SESSION['useraccess']}'
            ORDER BY st_name ASC";

    return mysqli_query($conn, $sql);
}

?>

<!-- ================= SEARCH FORM ================= -->

<div class="student-form-card" style="margin:10px">

    <form action="" method="post">

        <div style="display:flex; gap:10px;">

            <select class="form-control card shadow-sm"
                name="st_group" required>

                <option value="" disabled selected>هۆبە</option>

                <?php
                foreach (range('A', 'Z') as $l) {
                    echo "<option value='$l'>$l</option>";
                }
                ?>

            </select>


            <select class="form-control card shadow-sm"
                name="st_class" required>

                <option value="" disabled selected>پۆل</option>
                <option value="10">10</option>
                <option value="11">11</option>
                <option value="12">12</option>

            </select>


            <button class="btn btn-outline-success col-2"
                name="st_se" type="submit">

                گەران

            </button>

        </div>

    </form>
</div>

<br>

<!-- ================= ATTENDANCE TABLE ================= -->

<form action="" method="post">

    <div class="scrollableBox">

        <table class="table table-striped">

            <thead>

                <tr>
                    <th style="background-color:grey;">Action</th>
                    <th style="background-color:grey;text-align:center;">پۆل</th>
                    <th style="background-color:grey;text-align:center;">ناوی قوتابی</th>
                    <th style="background-color:grey;text-align:center;">#</th>
                </tr>

            </thead>

            <tbody>

                <?php

                if (isset($_POST["st_se"])) {

                    $counter = 1;

                    $stdList = getDhAttendance(
                        "students",
                        $_POST['st_class'],
                        $_POST['st_group']
                    );

                    if ($stdList->num_rows > 0) {

                        while ($row = $stdList->fetch_assoc()) {
                ?>

                            <tr style="text-align:center">

                                <!-- Student ID -->
                                <input type="hidden"
                                    name="st_id[]"
                                    value="<?= $row['id']; ?>">


                                <td>

                                    <select class="form-control card shadow-sm"
                                        name="st_atten[]" required>

                                        <option value="present" selected>هاتووە</option>
                                        <option value="absent">نەهاتووە</option>
                                    </select>

                                </td>


                                <td>

                                    <button type="button"
                                        class="btn btn-info btn-sm"
                                        style="border-radius:80px">

                                        <?= $row['st_class'] . "-" . $row['st_group']; ?>

                                    </button>

                                </td>


                                <td><?= $row['st_name']; ?></td>


                                <td><?= $counter; ?></td>

                            </tr>

                <?php
                            $counter++;
                        }
                    } else {

                        echo "<tr><td colspan='4' class='text-center'>
              No Students Found
              </td></tr>";
                    }
                }
                ?>

            </tbody>

        </table>

    </div>


    <?php if (isset($_POST["st_se"])) { ?>

        <button type="submit"
            name="sub_atten"
            class="btn btn-info col-12 mt-2"
            onclick="return confirm('دلنیایت لە تۆمارکردنی ئامادەنەبوونی ئەم قوتابیانە؟');">
            Done

        </button>

    <?php } ?>

</form>


<?php include "atten_footer.php"; ?>