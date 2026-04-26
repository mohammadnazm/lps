<?php include "viewer_header.php" ?>

<!-- ================= STYLE ================= -->
<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    html,
    body {
        height: 100%;
        font-family: 'Segoe UI', sans-serif;
        background: #1e293b;
        /* dark background */
        color: #f1f5f9;
        /* bright text */
        overflow: hidden;
        /* prevent full page scroll */
    }

    /* Full screen container */
    .dashboard-container {
        display: flex;
        height: 100vh;
        width: 100%;
        gap: 20px;
        padding: 15px;
        flex-wrap: wrap;
    }

    /* Each column */
    .dashboard {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 15px;
        overflow-y: auto;
        padding-right: 5px;
    }

    /* Modern scrollbar */
    .dashboard::-webkit-scrollbar {
        width: 6px;
    }

    .dashboard::-webkit-scrollbar-thumb {
        background: #64748b;
        /* lighter scrollbar */
        border-radius: 10px;
    }

    /* Cards */
    .grade-card {
        background: linear-gradient(145deg, #334155, #1e293b);
        border-radius: 14px;
        padding: 15px;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    /* Titles */
    .grade-title {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 10px;
        padding-bottom: 6px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        color: #ffffff;
    }

    .grade-title span {
        color: #60a5fa;
        font-weight: 500;
    }

    /* Stats rows */
    .stat-row {
        display: flex;
        justify-content: space-between;
        font-size: 14px;
        padding: 5px 0;
        color: #e2e8f0;
    }

    .stat-row strong {
        color: #4ade80;
        font-weight: 600;
    }

    hr {
        border: none;
        border-top: 1px solid rgba(255, 255, 255, 0.15);
        margin: 6px 0;
    }
</style>

<!-- ================= DASHBOARD ================= -->
<div class="dashboard-container">

    <div class="dashboard">

        <div class="grade-card">
            <div class="grade-title">
                تۆماری نەهاتووی قوتابیان <span>داتا</span>
            </div>
            <div class="stat-row">قوتابی <strong><?php $total16 = getDhForAllSAttCountGradeTB($currentDateTime2);
                                                    echo $total16; ?></strong></div>
            <div class="stat-row">کچ <strong><?php $total16 = getDhForAllSAttCountGradeG($currentDateTime2);
                                                echo $total16; ?></strong></div>
            <div class="stat-row">کور <strong><?php $total16 = getDhForAllSAttCountGradeB($currentDateTime2);
                                                echo $total16; ?></strong></div>
            <div class="stat-row">زانستی <strong><?php $total16 = getDhForAllSAttCountGradeSCI($currentDateTime2);
                                                    echo $total16; ?></strong></div>
            <div class="stat-row">وێژەیی <strong><?php $total16 = getDhForAllSAttCountGradeL($currentDateTime2);
                                                    echo $total16; ?></strong></div>
            <div class="stat-row">دەرەکی / تێکرای نمرە <strong><?php $total16 = getDhForAllSAttCountGradeTD($currentDateTime2);
                                                                echo $total16; ?></strong></div>
        </div>

        <div class="grade-card">
            <div class="grade-title">
                پۆلی دوازدەهەم <span>Stats</span>
            </div>
            <div class="stat-row">قوتابی <strong><?php $total16 = getDhForAllSAttCountGradeTB1($currentDateTime2);
                                                    echo $total16; ?></strong></div>
            <div class="stat-row">کچ <strong><?php $total16 = getDhForAllSAttCountGradeG1($currentDateTime2);
                                                echo $total16; ?></strong></div>
            <div class="stat-row">کور <strong><?php $total16 = getDhForAllSAttCountGradeB1($currentDateTime2);
                                                echo $total16; ?></strong></div>
        </div>

        <div class="grade-card">
            <div class="grade-title">
                پۆلی یازدەهەم <span>Stats</span>
            </div>
            <div class="stat-row">قوتابی <strong><?php $total16 = getDhForAllSAttCountGradeTB12($currentDateTime2);
                                                    echo $total16; ?></strong></div>
            <div class="stat-row">کچ <strong><?php $total16 = getDhForAllSAttCountGradeG12($currentDateTime2);
                                                echo $total16; ?></strong></div>
            <div class="stat-row">کور <strong><?php $total16 = getDhForAllSAttCountGradeB12($currentDateTime2);
                                                echo $total16; ?></strong></div>
        </div>

        <div class="grade-card">
            <div class="grade-title">
                پۆلی دەهەم <span>Stats</span>
            </div>
            <div class="stat-row">قوتابی <strong><?php $total16 = getDhForAllSAttCountGradeTB123($currentDateTime2);
                                                    echo $total16; ?></strong></div>
            <div class="stat-row">کچ <strong><?php $total16 = getDhForAllSAttCountGradeG123($currentDateTime2);
                                                echo $total16; ?></strong></div>
            <div class="stat-row">کور <strong><?php $total16 = getDhForAllSAttCountGradeB123($currentDateTime2);
                                                echo $total16; ?></strong></div>
        </div>

    </div>

</div>

<?php include "viewer_footer.php" ?>