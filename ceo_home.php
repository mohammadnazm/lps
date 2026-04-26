<?php include "ceo_header.php"; ?>

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
        color: #f1f5f9;
        overflow: hidden;
    }

    /* FULL SCREEN */
    .dashboard-container {
        display: flex;
        height: 100vh;
        width: 100%;
        gap: 20px;
        padding: 15px;
    }

    /* COLUMN */
    .dashboard {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 15px;
        overflow-y: auto;
        padding-right: 5px;
    }

    /* SCROLLBAR */
    .dashboard::-webkit-scrollbar {
        width: 6px;
    }

    .dashboard::-webkit-scrollbar-thumb {
        background: #64748b;
        border-radius: 10px;
    }

    /* CARD */
    .grade-card {
        background: linear-gradient(145deg, #334155, #1e293b);
        border-radius: 14px;
        padding: 15px;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    /* TITLE */
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

    /* ROW */
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


<div class="dashboard-container">

    <!-- ================= LEFT COLUMN ================= -->
    <div class="dashboard">

        <div class="grade-card">
            <div class="grade-title">گشتی <span>داتا</span></div>
            <div class="stat-row">
                کۆی گشتی قوتابیان
                <strong><?= getDhForAllSchool(); ?></strong>
            </div>
                            <div class="stat-row">
                کۆی گشتی ژمارەی داشکاندنەکان
                <strong><?php $total14 = getDhForAllSchooldISC();$total114 = getDhForAllSchooldISC1();$total1114 = getDhForAllSchooldISC12();  echo $total14+$total114+$total1114; ?></strong>
            </div>
                            <div class="stat-row">
                  داشکاندنەکانی پۆلی دوازدەهەم 
                <strong><?php $total14 = getDhForAllSchooldISC();  echo $total14; ?></strong>
            </div>
                            <div class="stat-row">
                  داشکاندنەکانی پۆلی یازدەهەم 
                <strong><?php $total14 = $total114 = getDhForAllSchooldISC1();  echo $total114; ?></strong>
            </div>
                            <div class="stat-row">
                  داشکاندنەکانی پۆلی دەهەم 
                <strong><?php $total14 = $total1114 = getDhForAllSchooldISC12();  echo $total1114; ?></strong>
            </div>
        </div>
        

        <div class="grade-card">
            <div class="grade-title">پۆلی دوازدەهەم <span>داتا</span></div>

            <div class="stat-row">قوتابی <strong><?= getDhForAllStCount(); ?></strong></div>
            <div class="stat-row">کچ <strong><?= getDhForGirlsStCount(); ?></strong></div>
            <div class="stat-row">کور <strong><?= getDhForBoysStCount(); ?></strong></div>

            <hr>

            <div class="stat-row">زانستی <strong><?= getDhForSciStCount(); ?></strong></div>
            <div class="stat-row">وێژەیی <strong><?= getDhForLitStCount(); ?></strong></div>

            <hr>

            <div class="stat-row">دەرەکی / تێکرای نمرە <strong><?= getDhForTDStCount(); ?></strong></div>
            <div class="stat-row">T D زانستی <strong><?= getDhForTDSKtCount(); ?></strong></div>
            <div class="stat-row">T D وێژەیی <strong><?= getDhForTDSLtCount(); ?></strong></div>

        </div>

        <div class="grade-card">
            <div class="grade-title">پۆلی یازدەهەم <span>داتا</span></div>

            <div class="stat-row">قوتابی <strong><?= getDhForAllStCountGradeE(); ?></strong></div>
            <div class="stat-row">کچ <strong><?= getDhForAllStCountGradeEG(); ?></strong></div>
            <div class="stat-row">کور <strong><?= getDhForAllStCountGradeEB(); ?></strong></div>

        </div>

        <div class="grade-card">
            <div class="grade-title">پۆلی دەهەم <span>داتا</span></div>

            <div class="stat-row">قوتابی <strong><?= getDhForAllStCountGradeT(); ?></strong></div>
            <div class="stat-row">کچ <strong><?= getDhForAllStCountGradeTG(); ?></strong></div>
            <div class="stat-row">کور <strong><?= getDhForAllStCountGradeTB(); ?></strong></div>

        </div>

    </div>


    <!-- ================= RIGHT COLUMN ================= -->
    <div class="dashboard">

        <div class="grade-card">
            <div class="grade-title">تۆماری نەهاتووی قوتابیان <span>داتا</span></div>

            <div class="stat-row">قوتابی <strong><?= getDhForAllSAttCountGradeTB($currentDateTime2); ?></strong></div>
            <div class="stat-row">کچ <strong><?= getDhForAllSAttCountGradeG($currentDateTime2); ?></strong></div>
            <div class="stat-row">کور <strong><?= getDhForAllSAttCountGradeB($currentDateTime2); ?></strong></div>
            <div class="stat-row">زانستی <strong><?= getDhForAllSAttCountGradeSCI($currentDateTime2); ?></strong></div>
            <div class="stat-row">وێژەیی <strong><?= getDhForAllSAttCountGradeL($currentDateTime2); ?></strong></div>
            <div class="stat-row">دەرەکی / تێکرای نمرە <strong><?= getDhForAllSAttCountGradeTD($currentDateTime2); ?></strong></div>

        </div>

        <div class="grade-card">
            <div class="grade-title">پۆلی دوازدەهەم <span>Stats</span></div>

            <div class="stat-row">قوتابی <strong><?= getDhForAllSAttCountGradeTB1($currentDateTime2); ?></strong></div>
            <div class="stat-row">کچ <strong><?= getDhForAllSAttCountGradeG1($currentDateTime2); ?></strong></div>
            <div class="stat-row">کور <strong><?= getDhForAllSAttCountGradeB1($currentDateTime2); ?></strong></div>

        </div>

        <div class="grade-card">
            <div class="grade-title">پۆلی یازدەهەم <span>Stats</span></div>

            <div class="stat-row">قوتابی <strong><?= getDhForAllSAttCountGradeTB12($currentDateTime2); ?></strong></div>
            <div class="stat-row">کچ <strong><?= getDhForAllSAttCountGradeG12($currentDateTime2); ?></strong></div>
            <div class="stat-row">کور <strong><?= getDhForAllSAttCountGradeB12($currentDateTime2); ?></strong></div>

        </div>

        <div class="grade-card">
            <div class="grade-title">پۆلی دەهەم <span>Stats</span></div>

            <div class="stat-row">قوتابی <strong><?= getDhForAllSAttCountGradeTB123($currentDateTime2); ?></strong></div>
            <div class="stat-row">کچ <strong><?= getDhForAllSAttCountGradeG123($currentDateTime2); ?></strong></div>
            <div class="stat-row">کور <strong><?= getDhForAllSAttCountGradeB123($currentDateTime2); ?></strong></div>

        </div>

    </div>

</div>

<?php include "ceo_footer.php"; ?>