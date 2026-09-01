<?php include "admin_header.php"; ?>

<?php
function normalizeName($text)
{
    $search  = ['ي', 'ك', 'ة', 'ۀ', 'ئ', 'أ', 'إ', 'آ'];
    $replace = ['ی', 'ک', 'ە', 'ە', 'ئ', 'ا', 'ا', 'ا'];
    $text = str_replace($search, $replace, $text);
    $text = trim(preg_replace('/\s+/u', ' ', $text));
    return $text;
}

// School+grade -> standard price, for every school (cheap, single query, used to show the
// Discount badge without needing to join school_grades into the main search query at all).
function getGradePriceMap(): array {
    global $conn;
    $map = [];
    $r = $conn->query("SELECT sc.school_code, sg.grade_name, sg.standard_price FROM school_grades sg INNER JOIN schools sc ON sc.id = sg.school_id WHERE sg.standard_price > 0");
    if ($r) while ($row = $r->fetch_assoc()) {
        $map[strtoupper($row['school_code']) . '|' . $row['grade_name']] = (float)$row['standard_price'];
    }
    return $map;
}

function getStudentSearch()
{
    global $conn;

    if (!isset($_POST['st_se'])) {
        return false;
    }

    $conditions = [];

    if (!empty($_POST['st_name'])) {
        $name = mysqli_real_escape_string($conn, normalizeName(trim($_POST['st_name'])));
        $words = explode(" ", $name);
        $nameConditions = [];
        foreach ($words as $word) {
            if ($word !== '') $nameConditions[] = "TRIM(st_name) LIKE '%$word%'";
        }
        if ($nameConditions) $conditions[] = "(" . implode(" AND ", $nameConditions) . ")";
    }
    if (!empty($_POST['st_group']))
        $conditions[] = "st_group='" . mysqli_real_escape_string($conn, $_POST['st_group']) . "'";
    if (!empty($_POST['st_class']))
        $conditions[] = "st_class='" . mysqli_real_escape_string($conn, $_POST['st_class']) . "'";
    if (!empty($_POST['st_faculty']))
        $conditions[] = "st_faculty='" . mysqli_real_escape_string($conn, $_POST['st_faculty']) . "'";
    if (!empty($_POST['st_statue']))
        $conditions[] = "st_statue='" . mysqli_real_escape_string($conn, $_POST['st_statue']) . "'";
    // The discount checkbox is deliberately NOT applied here in SQL. Whether a student
    // counts as discounted is decided in PHP after fetching (see the render loop below),
    // the same way the per-row Discount badge is decided — so the checkbox can never
    // disagree with what the badge already shows.

    $sql = "SELECT * FROM students WHERE ";
    $sql .= $conditions ? (implode(" AND ", $conditions) . " AND ") : "";
    $sql .= schoolSql('school_scope') . " ORDER BY st_name ASC";

    return mysqli_query($conn, $sql);
}
?>
<style>
:root{--bg:#07111f;--card:#101d30;--card2:#16263d;--line:rgba(255,255,255,.08);--text:#e8f0f8;--muted:#93a4b8;--accent:#38bdf8;--good:#4ade80;--warn:#fbbf24;--bad:#f87171}
html,body{height:100%;margin:0;font-family:'Segoe UI',sans-serif;background:var(--bg);color:var(--text);overflow:hidden}
.sd-page{padding:10px 5px 16px;height:100vh;box-sizing:border-box;overflow-y:auto}
.sd-hero{background:linear-gradient(135deg,#13263d,#0b1626);border:1px solid var(--line);border-radius:22px;padding:22px 24px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
.sd-hero h1{font-size:24px;margin:0 0 4px;font-weight:800}
.sd-hero p{margin:0;color:var(--muted);font-size:14px}
.sd-count{background:var(--card2);border-radius:12px;padding:10px 18px;text-align:center}
.sd-count b{display:block;font-size:22px}
.sd-count span{font-size:12px;color:var(--muted)}

.sd-search{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:18px;margin-bottom:18px}
.sd-search .row-fields{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px}
.sd-search .form-control{flex:1;min-width:130px;background:#0b1626!important;color:var(--text)!important;border:1px solid #24374f!important;border-radius:10px}
.sd-search select option{background:#0b1626;color:var(--text)}
.sd-toolbar{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.sd-check{display:flex;align-items:center;gap:8px;background:#0b1626;border:1px solid #24374f;border-radius:10px;padding:9px 14px;cursor:pointer;user-select:none}
.sd-check input{width:16px;height:16px;accent-color:var(--warn)}
.sd-search button[type=submit]{border-radius:10px;padding:10px 26px;font-weight:600}

.sd-table-card{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:6px;overflow:hidden}
.sd-scroll{max-height:48vh;overflow-y:auto}
table.sd-table{width:100%;border-collapse:collapse}
.sd-table thead th{position:sticky;top:0;background:#16263d;color:#93c5fd;padding:12px 10px;text-align:center;font-size:13px;border-bottom:1px solid var(--line);z-index:1}
.sd-table tbody td{padding:10px;text-align:center;border-bottom:1px solid var(--line);font-size:14px}
.sd-table tbody tr:hover{background:rgba(56,189,248,.06)}
.badge-pill{display:inline-block;padding:5px 12px;border-radius:999px;font-size:12px;font-weight:600}
.badge-active{background:#14532d;color:#dcfce7}
.badge-left{background:#7f1d1d;color:#fee2e2}
.badge-grad{background:#78350f;color:#fef3c7}
.badge-discount{background:#78350f;color:#fef3c7}
.badge-none{color:var(--muted);font-size:12px}
.action-group{display:inline-flex;gap:6px;flex-wrap:wrap;justify-content:center}
.action-group a{border:0;border-radius:8px;padding:6px 10px;font-size:12px;font-weight:600;text-decoration:none;color:#fff}
.act-update{background:#2563eb}
.act-marks{background:#475569}
.act-profile{background:#0ea5e9}
.act-delete{background:#dc2626}
@media(max-width:900px){.sd-search .row-fields{flex-direction:column;align-items:stretch}}
</style>

<?php
if (isset($_SESSION['msg2'])) {
    echo "<div class='alert alert-danger' style='margin:10px 5px'>{$_SESSION['msg2']}</div>";
    unset($_SESSION['msg2']);
}
?>
<?php
if (isset($_GET['did'])) {
    $id = intval($_GET['did']);
    $getImg = mysqli_query($conn, "SELECT st_name,st_img,st_id_file,school_scope FROM students WHERE id = $id AND " . schoolSql('school_scope') . "");
    $row = mysqli_fetch_assoc($getImg);
    if (!empty($row['st_img']) && !empty($row['st_id_file'])) {
        $imgPath1 = "st_image/" . $row['st_img'];
        $imgPath2 = "id_data/" . $row['st_id_file'];
        if (file_exists($imgPath1) && file_exists($imgPath2)) {
            unlink($imgPath1);
            unlink($imgPath2);
        }
    }
    DeleteData("students", $_GET['did']);
    if ($row) { logActivity('student_deleted', 'student', $id, $row['st_name'], 'Student record deleted', $row['school_scope']); }
    $_SESSION['msg2'] = "Student deleted successfully!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<div class="sd-page">
    <div class="sd-hero">
        <div>
            <h1>Students Data</h1>
            <p>Search, filter, and manage every student in your school.</p>
        </div>
    </div>

    <div class="sd-search">
        <form action="" method="post">
            <div class="row-fields">
                <input class="form-control" type="text" name="st_name" placeholder="Student Name" value="<?= htmlspecialchars($_POST['st_name'] ?? '') ?>">

                <select class="form-control" name="st_group">
                    <option value="" selected>Group</option>
                    <option value="newst">قوتابی نوێ</option>
                    <?php foreach (range('A', 'Z') as $letter): ?>
                        <option value="<?= $letter ?>" <?= (($_POST['st_group'] ?? '') === $letter) ? 'selected' : '' ?>><?= $letter ?></option>
                    <?php endforeach; ?>
                </select>

                <select class="form-control" name="st_class">
                    <option value="" selected>Grade</option>
                    <option value="Kids" <?= (($_POST['st_class'] ?? '') === 'Kids') ? 'selected' : '' ?>>Kids</option>
                    <?php for ($g = 1; $g <= 12; $g++): ?>
                        <option value="<?= $g ?>" <?= (($_POST['st_class'] ?? '') === (string)$g) ? 'selected' : '' ?>><?= $g ?></option>
                    <?php endfor; ?>
                </select>

                <select class="form-control" name="st_faculty">
                    <option value="" selected>Faculty</option>
                    <option value="زانستی" <?= (($_POST['st_faculty'] ?? '') === 'زانستی') ? 'selected' : '' ?>>زانستی</option>
                    <option value="وێژەیی" <?= (($_POST['st_faculty'] ?? '') === 'وێژەیی') ? 'selected' : '' ?>>وێژەیی</option>
                    <option value="تایبەت" <?= (($_POST['st_faculty'] ?? '') === 'تایبەت') ? 'selected' : '' ?>>تایبەت</option>
                </select>

                <select class="form-control" name="st_statue">
                    <option value="" selected>Status</option>
                    <option value="بەردەوام" <?= (($_POST['st_statue'] ?? '') === 'بەردەوام') ? 'selected' : '' ?>>بەردەوام</option>
                    <option value="بەجێهێشتوو" <?= (($_POST['st_statue'] ?? '') === 'بەجێهێشتوو') ? 'selected' : '' ?>>بەجێهێشتوو</option>
                    <option value="Graduated" <?= (($_POST['st_statue'] ?? '') === 'Graduated') ? 'selected' : '' ?>>Graduated</option>
                </select>
            </div>
            <div class="sd-toolbar">
                <label class="sd-check">
                    <input type="checkbox" name="dis" value="1" <?= !empty($_POST['dis']) ? 'checked' : '' ?>>
                    Only students with a discount
                </label>
                <button class="btn btn-success" name="st_se" type="submit">Search</button>
            </div>
        </form>
    </div>

    <div class="sd-table-card">
        <div class="sd-scroll">
            <table class="sd-table">
                <thead>
                    <tr>
                        <th>Actions</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Type</th>
                        <th>Faculty</th>
                        <th>Class</th>
                        <th>Student Name</th>
                        <th>#</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $counter = 0;
                    $gradePriceMap = getGradePriceMap();
                    $onlyDiscounted = !empty($_POST['dis']);
                    $stdList = getStudentSearch();
                    if ($stdList && $stdList->num_rows > 0) {
                        while ($row = $stdList->fetch_assoc()) {
                            $priceKey = strtoupper($row['school_scope'] ?? '') . '|' . $row['st_class'];
                            $standardPrice = $gradePriceMap[$priceKey] ?? 0;
                            $isDiscounted = $standardPrice > 0
                                && (float)str_replace([',', ' '], '', (string)$row['st_price']) < $standardPrice;

                            // Filtered here in PHP, using the exact same check as the badge just
                            // above — so "only discounted" can never disagree with what the
                            // Discount badge shows on an unfiltered search.
                            if ($onlyDiscounted && !$isDiscounted) continue;

                            $counter++;
                            $statusBadge = ($row['st_statue'] === 'بەردەوام')
                                ? "<span class='badge-pill badge-active'>{$row['st_statue']}</span>"
                                : (($row['st_statue'] === 'Graduated')
                                    ? "<span class='badge-pill badge-grad'>Graduated</span>"
                                    : "<span class='badge-pill badge-left'>{$row['st_statue']}</span>");
                            echo "<tr>";
                            echo "<td><div class='action-group'>
                                    <a class='act-update' href='up_st_profile.php?did=" . $row['id'] . "&nmn=" . urlencode($row['st_name']) . "' onclick=\"window.open(this.href,'PopupWindow','width=1000,height=800,scrollbars=yes'); return false;\">Update</a>
                                    <a class='act-marks' href='ad_st_marks_view.php?did=" . $row['id'] . "' onclick=\"window.open(this.href,'PopupWindow','width=1000,height=800,scrollbars=yes'); return false;\">Marks</a>
                                    <a class='act-profile' href='st_profile.php?did=" . $row['id'] . "&nmn=" . urlencode($row['st_name']) . "' onclick=\"window.open(this.href,'PopupWindow','width=1000,height=800,scrollbars=yes'); return false;\">Profile</a>
                                    <a class='act-delete' href='st_data.php?did=" . $row['id'] . "' onclick=\"return confirm('Are you sure you want to delete this?');\">Delete</a>
                                  </div></td>";
                            echo "<td>" . htmlspecialchars($row['st_price']) . ($isDiscounted ? " <span class='badge-pill badge-discount'>Discount</span>" : "") . "</td>";
                            echo "<td>$statusBadge</td>";
                            echo "<td>" . htmlspecialchars($row['st_type']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['st_faculty']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['st_class']) . "-" . htmlspecialchars($row['st_group']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['st_name']) . "</td>";
                            echo "<td>{$counter}</td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
            <?php if ($counter === 0 && isset($_POST['st_se'])): ?>
                <p style="text-align:center;color:var(--muted);padding:30px">No students match this search.</p>
            <?php endif; ?>
        </div>
        <h6 style="text-align:center; margin:12px 0 6px;color:var(--muted)">Total Number Of Students: <?= $counter ?? 0 ?></h6>
    </div>
</div>

<?php include "admin_footer.php"; ?>
