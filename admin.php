<?php include "admin_header.php"; ?>
<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html, body {
    height: 100%;
    font-family: 'Segoe UI', sans-serif;
    background: #1e293b; /* lighter background */
    color: #f1f5f9; /* brighter text */
    overflow: hidden; /* prevent full page scroll */
}

/* Full screen container */
.dashboard-container {
    display: flex;
    height: 100vh;
    width: 100%;
    gap: 20px;
    padding: 15px;
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
    background: #64748b; /* lighter scrollbar */
    border-radius: 10px;
}

/* Cards */
.grade-card {
    background: linear-gradient(145deg, #334155, #1e293b); /* lighter card */
    border-radius: 14px;
    padding: 15px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.3);
    border: 1px solid rgba(255,255,255,0.08);
}

/* Titles */
.grade-title {
    font-size: 16px; /* slightly bigger */
    font-weight: 700; /* stronger */
    margin-bottom: 10px;
    padding-bottom: 6px;
    border-bottom: 1px solid rgba(255,255,255,0.15);
    color: #ffffff; /* full white */
}

.grade-title span {
    color: #60a5fa; /* brighter blue */
    font-weight: 500;
}

/* Stats rows */
.stat-row {
    display: flex;
    justify-content: space-between;
    font-size: 14px; /* slightly bigger */
    padding: 5px 0;
    color: #e2e8f0; /* brighter */
}

.stat-row strong {
    color: #4ade80; /* brighter green numbers */
    font-weight: 600;
}

hr {
    border: none;
    border-top: 1px solid rgba(255,255,255,0.15);
    margin: 6px 0;
}
</style>
<div class="dashboard-container">

    <div class="dashboard">

        <div class="grade-card">
            <div class="grade-title">
                TOTAL <span>Stats</span>
            </div>
            <div class="stat-row">
                TOTAL STUDENTS 
                <strong><?php $total14 = getDhForAllSchool(); echo $total14; ?></strong>
            </div>
                <div class="stat-row">
                TOTAL STUDENTS WITH DISCOUNT
                <strong><?php $total14 = getDhForAllSchooldISC();$total114 = getDhForAllSchooldISC1();$total1114 = getDhForAllSchooldISC12();  echo $total14+$total114+$total1114; ?></strong>
            </div>
                            <div class="stat-row">
                TOTAL GRADE 12 WITH DISCOUNT
                <strong><?php $total14 = getDhForAllSchooldISC();  echo $total14; ?></strong>
            </div>
                            <div class="stat-row">
                TOTAL GRADE 11 WITH DISCOUNT
                <strong><?php $total14 = $total114 = getDhForAllSchooldISC1();  echo $total114; ?></strong>
            </div>
                            <div class="stat-row">
                TOTAL GRADE 10 WITH DISCOUNT
                <strong><?php $total14 = $total1114 = getDhForAllSchooldISC12();  echo $total1114; ?></strong>
            </div>
        </div>

        <div class="grade-card">
            <div class="grade-title">
                Grade 12 <span>Stats</span>
            </div>
            <div class="stat-row">Students <strong><?php $total1 = getDhForAllStCount(); echo $total1; ?></strong></div>
            <div class="stat-row">Girls <strong><?php $total2 = getDhForGirlsStCount(); echo $total2; ?></strong></div>
            <div class="stat-row">Boys <strong><?php $total3 = getDhForBoysStCount(); echo $total3; ?></strong></div>
            <hr>
            <div class="stat-row">Scientific <strong><?php $total4 = getDhForSciStCount(); echo $total4; ?></strong></div>
            <div class="stat-row">Literary <strong><?php $total5 = getDhForLitStCount(); echo $total5; ?></strong></div>
            <hr>
            <div class="stat-row">T D <strong><?php $total6 = getDhForTDStCount(); echo $total6; ?></strong></div>
            <div class="stat-row">T D Scientific <strong><?php $total7 = getDhForTDSKtCount(); echo $total7; ?></strong></div>
            <div class="stat-row">T D Literary <strong><?php $total8 = getDhForTDSLtCount(); echo $total8; ?></strong></div>
        </div>

        <div class="grade-card">
            <div class="grade-title">
                Grade 11 <span>Stats</span>
            </div>
            <div class="stat-row">Students <strong><?php $total11 = getDhForAllStCountGradeE(); echo $total11; ?></strong></div>
            <div class="stat-row">Girls <strong><?php $total12 = getDhForAllStCountGradeEG(); echo $total12; ?></strong></div>
            <div class="stat-row">Boys <strong><?php $total13 = getDhForAllStCountGradeEB(); echo $total13; ?></strong></div>
        </div>

        <div class="grade-card">
            <div class="grade-title">
                Grade 10 <span>Stats</span>
            </div>
            <div class="stat-row">Students <strong><?php $total14 = getDhForAllStCountGradeT(); echo $total14; ?></strong></div>
            <div class="stat-row">Girls <strong><?php $total15 = getDhForAllStCountGradeTG(); echo $total15; ?></strong></div>
            <div class="stat-row">Boys <strong><?php $total16 = getDhForAllStCountGradeTB(); echo $total16; ?></strong></div>
        </div>

    </div>

    <div class="dashboard">

        <div class="grade-card">
            <div class="grade-title">
                Attendance <span>Stats (Today)</span>
            </div>
            <div class="stat-row">Students <strong><?php $total16 = getDhForAllSAttCountGradeTB($currentDateTime2); echo $total16; ?></strong></div>
            <div class="stat-row">Girls <strong><?php $total16 = getDhForAllSAttCountGradeG($currentDateTime2); echo $total16; ?></strong></div>
            <div class="stat-row">Boys <strong><?php $total16 = getDhForAllSAttCountGradeB($currentDateTime2); echo $total16; ?></strong></div>
            <div class="stat-row">Scientific <strong><?php $total16 = getDhForAllSAttCountGradeSCI($currentDateTime2); echo $total16; ?></strong></div>
            <div class="stat-row">Literary <strong><?php $total16 = getDhForAllSAttCountGradeL($currentDateTime2); echo $total16; ?></strong></div>
            <div class="stat-row">T D <strong><?php $total16 = getDhForAllSAttCountGradeTD($currentDateTime2); echo $total16; ?></strong></div>
        </div>

        <div class="grade-card">
            <div class="grade-title">
                Grade 12 <span>Attendance</span>
            </div>
            <div class="stat-row">Students <strong><?php $total16 = getDhForAllSAttCountGradeTB1($currentDateTime2); echo $total16; ?></strong></div>
            <div class="stat-row">Girls <strong><?php $total16 = getDhForAllSAttCountGradeG1($currentDateTime2); echo $total16; ?></strong></div>
            <div class="stat-row">Boys <strong><?php $total16 = getDhForAllSAttCountGradeB1($currentDateTime2); echo $total16; ?></strong></div>
        </div>

        <div class="grade-card">
            <div class="grade-title">
                Grade 11 <span>Attendance</span>
            </div>
            <div class="stat-row">Students <strong><?php $total16 = getDhForAllSAttCountGradeTB12($currentDateTime2); echo $total16; ?></strong></div>
            <div class="stat-row">Girls <strong><?php $total16 = getDhForAllSAttCountGradeG12($currentDateTime2); echo $total16; ?></strong></div>
            <div class="stat-row">Boys <strong><?php $total16 = getDhForAllSAttCountGradeB12($currentDateTime2); echo $total16; ?></strong></div>
        </div>

        <div class="grade-card">
            <div class="grade-title">
                Grade 10 <span>Attendance</span>
            </div>
            <div class="stat-row">Students <strong><?php $total16 = getDhForAllSAttCountGradeTB123($currentDateTime2); echo $total16; ?></strong></div>
            <div class="stat-row">Girls <strong><?php $total16 = getDhForAllSAttCountGradeG123($currentDateTime2); echo $total16; ?></strong></div>
            <div class="stat-row">Boys <strong><?php $total16 = getDhForAllSAttCountGradeB123($currentDateTime2); echo $total16; ?></strong></div>
        </div>

    </div>

</div>

<?php include "admin_footer.php"; ?>