<?php include "manager_header.php"; ?>

<style>
body{
    background:#0f172a;
    color:white;
}

/* ================= CLASS GRID ================= */

.class-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(90px,1fr));
    gap:12px;
    padding:20px;
    max-width:900px;
    margin:auto;
}

.class-box{
    padding:12px;
    border-radius:12px;
    text-align:center;
    font-weight:bold;
    text-decoration:none;
    color:white;
    background:#2563eb;
}

.disabled-class{
    background:#ef4444;
    opacity:0.6;
    pointer-events:none;
}

/* ================= CAROUSEL ================= */

.carousel-wrapper{
    position:relative;
    max-width:100%;
    margin:40px auto;
    padding:0 50px;
}

.arrow{
    position:absolute;
    top:50%;
    transform:translateY(-50%);
    background:#1e293b;
    border:none;
    color:white;
    font-size:22px;
    width:40px;
    height:40px;
    border-radius:50%;
    cursor:pointer;
}

.arrow.left{ left:10px; }
.arrow.right{ right:10px; }

.teacher-row{
    display:flex;
    gap:16px;
    overflow-x:auto;
    padding:20px;
    scroll-behavior:smooth;
}

.teacher-row::-webkit-scrollbar{
    display:none;
}

.teacher-card{
    flex:0 0 auto;
    width:220px;
    background:#1e293b;
    border-radius:16px;
    padding:12px;
    text-align:center;
}

.teacher-card img{
    width:100%;
    max-height:180px;
    object-fit:contain;
    border-radius:12px;
}

.teacher-name{
    margin-top:10px;
    font-weight:bold;
}
</style>

<!-- ================= CLASS GRID ================= -->

<div class="class-grid">

<?php
foreach(range('A','Z') as $letter){

    $className = "12-$letter";

    $check = $conn->query("SELECT class_status FROM staffclasscon WHERE class_name='$className'");
    $disabled = ($check && $check->num_rows>0 && $check->fetch_assoc()['class_status']=='disabled');

    $class = $disabled ? "class-box disabled-class" : "class-box";

    echo "<a class='$class' href='?class=$className'>$className</a>";
}
?>

</div>

<?php
$classFilter = isset($_GET['class']) ? trim($_GET['class']) : "";
?>

<!-- ================= EMPTY STATE ================= -->

<?php if ($classFilter == "") { ?>

    <div style="text-align:center;margin-top:50px;color:#94a3b8;">
        👆 Please select a class to view teachers
    </div>

<?php } else { ?>

<!-- ================= LOAD TEACHERS ONLY WHEN CLASS CLICKED ================= -->

<?php
$stmt = $conn->query("SELECT * FROM lozanstaff");

$teachers = [];

while($row = $stmt->fetch_assoc()){
    if(trim($row['class']) == $classFilter){
        $teachers[] = $row;
    }
}
?>

<div class="carousel-wrapper">

<button class="arrow left" onclick="scrollLeft()">‹</button>
<button class="arrow right" onclick="scrollRight()">›</button>

<div class="teacher-row" id="row">

<?php if (count($teachers) == 0) { ?>

    <div style="padding:20px;color:#f87171;">
        No teachers found in <?php echo $classFilter; ?>
    </div>

<?php } ?>

<?php foreach($teachers as $t){ ?>

    <div class="teacher-card">

        <img src="teachers_img/<?php echo $t['teacher_img']; ?>">

        <div class="teacher-name"><?php echo $t['name']; ?></div>

        <div style="font-size:12px;color:#60a5fa;">
            <?php echo $t['class']; ?>
        </div>

    </div>

<?php } ?>

</div>
</div>

<?php } ?>

<script>
const row = document.getElementById("row");

function scrollLeft(){
    if(row) row.scrollBy({left:-300, behavior:"smooth"});
}

function scrollRight(){
    if(row) row.scrollBy({left:300, behavior:"smooth"});
}
</script>

<?php include "manager_footer.php"; ?>
