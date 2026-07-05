<?php include "student_header.php" ?>

<?php
$stdList = getDhByID("students", $_SESSION['student_id']);

$stnm = $stimg = $stgr = $stclss = $stftell = "";

if ($stdList->num_rows > 0) {
    $row = $stdList->fetch_assoc();

    $stnm = $row['st_name'];
    $stimg = $row['st_img'];
    $stgr = $row['st_group'];
    $stclss = $row['st_class'];
    $stftell = $row['f_tell'];
}
?>



<div class="page-wrapper">

    <div class="profile-card">

        <!-- Header -->
        <div class="profile-header">
            <img src="st_image/<?php echo $stimg; ?>" alt="Student Image">
            <h2><?php echo $stnm; ?></h2>
            <p>Student Profile</p>
        </div>

        <!-- Body -->
        <div class="profile-body">

            <div class="info-grid">

                <div class="info-box">
                    <span>Class</span>
                    <div class="value"><?php echo $stclss; ?></div>
                </div>

                <div class="info-box">
                    <span>Group</span>
                    <div class="value"><?php echo $stgr; ?></div>
                </div>

                <a href="st_marks_view.php" class="theme-btn">نمرەکان</a>
            </div>

        </div>

        <!-- Footer -->
        <div class="profile-footer">
            AsoArshad © <?php echo date("Y"); ?>
        </div>

    </div>

</div>

<?php include "student_footer.php" ?>
