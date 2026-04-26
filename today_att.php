<?php include "manager_header.php" ?>

<!-- ================= STYLES ================= -->
<style>
    /* ================= GENERAL ================= */
    html,
    body {
        height: 100%;
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
        background-color: #0f172a;
        color: #e2e8f0;
    }

    /* ================= SEARCH FORM ================= */
    .search-card {
        background: #1e293b;
        border-radius: 12px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
        padding: 15px;
        margin-bottom: 15px;
    }

    .search-card input.form-control,
    .search-card select.form-control {
        border-radius: 8px;
        padding: 8px;
        background-color: #0f172a;
        border: 1px solid #334155;
        color: #e2e8f0;
    }

    .search-card ::placeholder {
        color: #cbd5e1;
        opacity: 1;
    }

    .search-card select option {
        background-color: #1e293b;
        color: #e2e8f0;
    }

    .search-card input:focus,
    .search-card select:focus {
        border-color: #2563eb;
        background-color: rgba(37, 99, 235, 0.15);
        outline: none;
        box-shadow: 0 0 0 0.15rem rgba(37, 99, 235, 0.25);
    }

    .btn-outline-success {
        color: #22c55e;
        border-color: #22c55e;
    }

    .btn-outline-success:hover {
        background-color: #22c55e;
        color: #0f172a;
    }

    /* ================= TABLE ================= */
    .table {
        color: #f1f5f9 !important;
    }

    .table thead th {
        background: #475569 !important;
        color: #ffffff !important;
        text-align: center;
        border: none !important;
    }

    .table tbody tr {
        background: #1e293b;
        transition: 0.2s;
        text-align: center;
    }

    .table tbody tr:hover {
        background: #334155;
    }

    .table td {
        vertical-align: middle !important;
    }

    .scrollableBox {
        overflow-y: auto;
        max-height: 65vh;
        border-radius: 12px;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        background: linear-gradient(145deg, #334155, #1e293b);
        padding: 10px;
    }

    .scrollableBox table {
        width: 100%;
        border-collapse: collapse;
        text-align: center;
    }

    .scrollableBox th,
    .scrollableBox td {
        padding: 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .scrollableBox th {
        background-color: #334155;
        color: #f1f5f9;
        font-weight: 600;
    }

    .scrollableBox tr:nth-child(even) {
        background-color: rgba(255, 255, 255, 0.02);
    }

    .scrollableBox tr:hover {
        background-color: rgba(56, 189, 248, 0.1);
    }

    .scrollableBox button {
        font-size: 0.75rem;
        border-radius: 6px;
    }
</style>

<!-- ================= SEARCH FORM ================= -->
<div class="search-card">
    <form action="" method="post" style="display: flex; flex-wrap: wrap; gap:10px; align-items:center;">
        <input type="date" required name="atten_date" class="form-control" style="flex:1; min-width:150px;" placeholder="From Date">
        <input type="date" required name="atten_date1" class="form-control" style="flex:1; min-width:150px;" placeholder="To Date">
        <button type="submit" name="att_se" class="btn btn-success" style="min-width:100px;">گەران</button>
    </form>
</div>

<!-- ================= TABLE ================= -->
<div class="table-card">
    <div class="scrollableBox">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>بەروار</th>
                    <th>تۆماری نەهاتوو</th>
                    <th>حالەتی قوتابی</th>
                    <th>پۆل</th>
                    <th>ناوی قوتابی</th>
                    <th>#</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (isset($_POST["att_se"])) {
                    $counter = 1;
                    $stdList = getDhAttenJoinTBAllGENDER($_POST['atten_date'], $_POST['atten_date1'], $_SESSION['useraccess']);
                    if ($stdList->num_rows > 0) {
                        while ($row = $stdList->fetch_assoc()) {
                            $dates = str_replace(', ', '<br>', $row['absent_dates']);
                            echo "<tr>";
                            echo "<td style='white-space:normal; line-height:1.6;'>$dates</td>";
                            echo "<td>{$row['total_absent']}</td>";
                            echo "<td>نەهاتوو</td>";
                            echo "<td><button class='btn btn-warning btn-sm'>{$row['st_class']}-{$row['st_group']}</button></td>";
                            echo "<td>{$row['st_name']}</td>";
                            echo "<td>{$counter}</td>";
                            echo "</tr>";
                            $counter++;
                        }
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "manager_footer.php" ?>