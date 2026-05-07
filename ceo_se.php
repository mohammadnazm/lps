<?php include "ceo_header.php"; ?>

<?php
/* ================= NORMALIZE ================= */
function normalizeName($text)
{
    $search  = ['ي', 'ك', 'ة', 'ۀ', 'ئ', 'أ', 'إ', 'آ'];
    $replace = ['ی', 'ک', 'ە', 'ە', 'ئ', 'ا', 'ا', 'ا'];
    $text = str_replace($search, $replace, $text);
    return trim(preg_replace('/\s+/u', ' ', $text));
}

/* ================= SEARCH ================= */
function getDhySearch($table)
{
    if (!isset($_POST['st_se'])) return false;

    global $conn;
    $conditions = [];

    if (!empty($_POST['st_name'])) {
        $name = mysqli_real_escape_string(
            $conn,
            normalizeName(trim($_POST['st_name']))
        );

        $words = explode(" ", $name);
        $nameConditions = [];

        foreach ($words as $word) {
            if (!empty($word))
                $nameConditions[] = "TRIM(st_name) LIKE '%$word%'";
        }

        if ($nameConditions)
            $conditions[] = "(" . implode(" AND ", $nameConditions) . ")";
    }

    if (!empty($_POST['st_group']))
        $conditions[] = "st_group='" . mysqli_real_escape_string($conn, $_POST['st_group']) . "'";

    if (!empty($_POST['st_class']))
        $conditions[] = "st_class='" . mysqli_real_escape_string($conn, $_POST['st_class']) . "'";

    if (empty($conditions)) return false;

    $sql = "SELECT * FROM `$table` WHERE " . implode(" AND ", $conditions);
    return mysqli_query($conn, $sql);
}
?>

<style>
    /* ================= GENERAL ================= */
    html,
    body {
        height: 100%;
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
        background: #0f172a;
        color: #e2e8f0;
        overflow: hidden;
    }

    /* ================= DASHBOARD ================= */
    .dashboard-container {
        display: flex;
        flex-direction: column;
        height: 100vh;
        padding: 15px;
        gap: 15px;
    }

    /* ================= SEARCH CARD ================= */
    .search-card {
        background: #1e293b;
        border-radius: 12px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, .4);
        padding: 15px;
    }

    .search-card input,
    .search-card select {
        background: #0f172a;
        border: 1px solid #334155;
        color: #e2e8f0;
        border-radius: 8px;
        padding: 8px;
    }

    .search-card ::placeholder {
        color: #cbd5e1;
    }

    .search-card select option {
        background: #1e293b;
        color: #e2e8f0;
    }

    .search-card input:focus,
    .search-card select:focus {
        border-color: #22c55e;
        background: rgba(34, 197, 94, .1);
        outline: none;
    }

    /* ================= TABLE ================= */
    .table {
        color: #f1f5f9 !important;
    }

    .table thead th {
        background: #475569 !important;
        color: #fff !important;
        text-align: center;
        border: none !important;
    }

    .table tbody tr {
        background: #1e293b;
        text-align: center;
        transition: .2s;
    }

    .table tbody tr:hover {
        background: #334155;
    }

    /* ONLY TABLE SCROLLS */
    .scrollableBox {
        flex: 1;
        overflow-y: auto;
    }

    .scrollableBox table {
        width: 100%;
        border-collapse: collapse;
    }
</style>


<div class="dashboard-container">

    <!-- ================= SEARCH ================= -->
    <div class="search-card">
        <form method="post">

            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">

                <input class="form-control"
                    type="text"
                    name="st_name"
                    placeholder="ناوی قوتابی"
                    style="flex:1;min-width:150px;text-align:right">

                <select class="form-control"
                    name="st_group"
                    style="flex:1;min-width:80px">
                    <option disabled selected>هۆبە</option>
                    <option value="newst">قوتابی نوێ</option>
                    <?php foreach (range('A', 'Z') as $l)
                        echo "<option value='$l'>$l</option>"; ?>
                </select>

                <select class="form-control"
                    name="st_class"
                    style="flex:1;min-width:80px">
                    <option disabled selected>پۆل</option>
                    <option value="10">10</option>
                    <option value="11">11</option>
                    <option value="12">12</option>
                </select>

                <button class="btn btn-success"
                    name="st_se"
                    type="submit">
                    گەران
                </button>

            </div>

        </form>
    </div>


    <!-- ================= TABLE ================= -->
    <div class="table-card">

        <div class="scrollableBox">

            <table class="table table-striped">

                <thead>
                    <tr>
                        <th>Action</th>
                        <th>جۆری قوتابی</th>
                        <th>جۆری بەش</th>
                        <th>پۆل</th>
                        <th>ناوی قوتابی</th>
                        <th>#</th>
                    </tr>
                </thead>

                <tbody>

                    <?php
                    $counter = 1;
                    $stdList = getDhySearch("students");

                    if ($stdList && $stdList->num_rows > 0) {

                        while ($row = $stdList->fetch_assoc()) {

                            echo "<tr>";

                            echo "<td style='text-align:left'>
<a class='btn btn-info btn-sm'
href='ceo_dis.php?did={$row['id']}&nmn={$row['st_name']}'
onclick=\"window.open(this.href,'PopupWindow','width=1000,height=800,scrollbars=yes');return false;\">
Profile
</a>
</td>";

                            echo "<td>{$row['st_type']}</td>";

                            echo "<td>
<button class='btn btn-warning btn-sm'
style='border-radius:60px;width:100px'>
{$row['st_faculty']}
</button>
</td>";

                            echo "<td>{$row['st_class']}-{$row['st_group']}</td>";
                            echo "<td>{$row['st_name']}</td>";
                            echo "<td>{$counter}</td>";

                            echo "</tr>";

                            $counter++;
                            $totcou = $counter;
                        }
                    }
                    ?>

                </tbody>
            </table>

        </div>

        <h6 style="text-align:center;margin-top:10px;">
            کۆی گشتی قوتابیان :
            <?php echo isset($totcou) ? $totcou - 1 : 0; ?>
        </h6>

    </div>

</div>

<?php include "ceo_footer.php"; ?>
