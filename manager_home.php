<?php include "manager_header.php"; ?>

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
        /* admin page dark background */
        color: #f1f5f9;
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
    }

    /* Each column (stack cards vertically) */
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
        border-radius: 10px;
    }

    /* Cards */
    .grade-card {
        background: linear-gradient(145deg, #334155, #1e293b);
        border-radius: 14px;
        padding: 15px;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: 0.2s;
    }

    .grade-card:hover {
        background: linear-gradient(145deg, #3b4b63, #1f2a3b);
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

    /* Divider */
    hr {
        border: none;
        border-top: 1px solid rgba(255, 255, 255, 0.15);
        margin: 6px 0;
    }
</style>

<div class="dashboard-container">

    <div class="dashboard">

        <div class="grade-card">
            <div class="grade-title">
                تۆماری نەهاتووی قوتابیان <span>داتا</span>
            </div>
            <div class="stat-row">قوتابی <strong><?php echo getDhForAllSAttCountGradeTB($currentDateTime2); ?></strong></div>
            <div class="stat-row">کچ <strong><?php echo getDhForAllSAttCountGradeG($currentDateTime2); ?></strong></div>
            <div class="stat-row">کور <strong><?php echo getDhForAllSAttCountGradeB($currentDateTime2); ?></strong></div>
            <div class="stat-row">زانستی <strong><?php echo getDhForAllSAttCountGradeSCI($currentDateTime2); ?></strong></div>
            <div class="stat-row">وێژەیی <strong><?php echo getDhForAllSAttCountGradeL($currentDateTime2); ?></strong></div>
            <div class="stat-row">دەرەکی / تێکرای نمرە <strong><?php echo getDhForAllSAttCountGradeTD($currentDateTime2); ?></strong></div>
        </div>

        <div class="grade-card">
            <div class="grade-title">
                پۆلی دوازدەهەم <span>Stats</span>
            </div>
            <div class="stat-row">قوتابی <strong><?php echo getDhForAllSAttCountGradeTB1($currentDateTime2); ?></strong></div>
            <div class="stat-row">کچ <strong><?php echo getDhForAllSAttCountGradeG1($currentDateTime2); ?></strong></div>
            <div class="stat-row">کور <strong><?php echo getDhForAllSAttCountGradeB1($currentDateTime2); ?></strong></div>
        </div>

        <div class="grade-card">
            <div class="grade-title">
                پۆلی یازدەهەم <span>Stats</span>
            </div>
            <div class="stat-row">قوتابی <strong><?php echo getDhForAllSAttCountGradeTB12($currentDateTime2); ?></strong></div>
            <div class="stat-row">کچ <strong><?php echo getDhForAllSAttCountGradeG12($currentDateTime2); ?></strong></div>
            <div class="stat-row">کور <strong><?php echo getDhForAllSAttCountGradeB12($currentDateTime2); ?></strong></div>
        </div>

        <div class="grade-card">
            <div class="grade-title">
                پۆلی دەهەم <span>Stats</span>
            </div>
            <div class="stat-row">قوتابی <strong><?php echo getDhForAllSAttCountGradeTB123($currentDateTime2); ?></strong></div>
            <div class="stat-row">کچ <strong><?php echo getDhForAllSAttCountGradeG123($currentDateTime2); ?></strong></div>
            <div class="stat-row">کور <strong><?php echo getDhForAllSAttCountGradeB123($currentDateTime2); ?></strong></div>
        </div>

    </div>

</div>

<?php include "manager_footer.php"; ?>