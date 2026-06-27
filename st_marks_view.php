<?php include "student_header.php" ?>

<style>
    * {
        box-sizing: border-box;
        font-family: "Segoe UI", Tahoma, sans-serif;
    }

    body {
        margin: 0;
        background: #f4f6fb;
    }

    /* Page wrapper */
    .page {
        max-width: 1000px;
        margin: auto;
        padding: 20px;
    }

    /* Semester title */
    .sem-title {
        margin: 25px 0 10px;
        font-size: 20px;
        font-weight: 700;
        color: #142a52;
        border-left: 5px solid #d4af37;
        padding-left: 10px;
    }

    /* Card */
    .card {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        margin-bottom: 25px;
    }

    /* Table */
    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background: linear-gradient(135deg, #0a1a33, #142f5f);
        color: #fff;
        padding: 12px;
        text-align: left;
        font-size: 14px;
    }

    td {
        padding: 12px;
        border-bottom: 1px solid #eee;
        font-size: 14px;
    }

    tr:hover {
        background: #f8f9fc;
    }

    /* subject column */
    .subject {
        font-weight: 600;
        color: #222;
    }

    /* marks style */
    .mark {
        text-align: center;
        font-weight: 600;
    }

    /* responsive */
    @media (max-width: 600px) {

        th,
        td {
            font-size: 12px;
            padding: 10px;
        }
    }
</style>

<div class="page">
    <a href="student_home.php" class="theme-btn">گەرانەوە</a>

    <!-- ================= FIRST SEMESTER ================= -->
    <div class="sem-title">وەرزی یەکەم</div>

    <div class="card">
        <table>
            <tr>
                <th>20%</th>
                <th>10%</th>
                <th>5%</th>
                <th>بابەت</th>

            </tr>

            <?php
            $stdList = getMarksBySemster("st_marks", $_SESSION['student_id'], "1");

            if ($stdList->num_rows > 0) {
                while ($row = $stdList->fetch_assoc()) {
            ?>
                    <tr>
                        <td class="mark"><?php echo $row['mark1']; ?></td>
                        <td class="mark"><?php echo $row['mark2']; ?></td>
                        <td class="mark"><?php echo $row['mark3']; ?></td>
                        <td class="subject"><?php echo $row['subject']; ?></td>

                    </tr>
            <?php
                }
            } else {
                echo "<tr><td colspan='4' style='text-align:center;padding:15px;'>No data found</td></tr>";
            }
            ?>

        </table>
    </div>

    <!-- ================= SECOND SEMESTER ================= -->
    <div class="sem-title">وەرزی دووەم</div>

    <div class="card">
        <table>
            <tr>
                <th>20%</th>
                <th>10%</th>
                <th>5%</th>
                <th>بابەت</th>
            </tr>

            <?php
            $stdList = getMarksBySemster("st_marks", $_SESSION['student_id'], "2");

            if ($stdList->num_rows > 0) {
                while ($row = $stdList->fetch_assoc()) {
            ?>
                    <tr>
                        <td class="mark"><?php echo $row['mark1']; ?></td>
                        <td class="mark"><?php echo $row['mark2']; ?></td>
                        <td class="mark"><?php echo $row['mark3']; ?></td>
                        <td class="subject"><?php echo $row['subject']; ?></td>
                    </tr>
            <?php
                }
            } else {
                echo "<tr><td colspan='4' style='text-align:center;padding:15px;'>No data found</td></tr>";
            }
            ?>

        </table>
    </div>

</div>

<?php include "student_footer.php" ?>