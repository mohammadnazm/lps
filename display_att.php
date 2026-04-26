<?php include "atten_header.php" ?>
<h1 style="text-align: center;"><?php echo $currentDateTime2; ?></h1>
<div class="scrollableBox">
    <table class="table table-striped">
        <thead>
            <tr>
                <th style="background-color:grey; text-align: center;">بەروار</th>
                <th style="background-color:grey; text-align: center;">حالەتی قوتابی</th>
                <th style="background-color:grey; text-align: center;">پۆل</th>
                <th style="background-color:grey; text-align: center;">ناوی قوتابی</th>
                <th style="background-color:grey;">#</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $counter = 1;
            $stdList = getDhAttenJoinTB($currentDateTime2, $_SESSION["useraccess"]);
            if ($stdList->num_rows > 0) {
                while ($row = $stdList->fetch_assoc()) {
                    echo "<tr style='text-align:center'>";
                    echo "<td style='text-align:center'>{$row['date']}</td>";
                    echo "<td style='text-align:center'>{$row['status']}</td>";
                    echo "<td style='text-align:center'><button class='btn btn-warning btn-sm' style='border-radius:80px'>{$row['st_class']}-{$row['st_group']}</button></td>";
                    echo "<td style='text-align:center'>{$row['st_name']}</td>";
                    echo "<td>{$counter}</td>";
                    echo "<tr style='text-align:center'>";
                    $counter++;
                    $totcou = $counter;
                }
            }
            ?>
        </tbody>
    </table>
</div>
<?php include "atten_footer.php" ?>