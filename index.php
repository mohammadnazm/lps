<?php session_start();
include "db_connection.php";
error_reporting(0);
ini_set('display_errors', 0);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Lozan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background-color: #0f172a;
            color: #e2e8f0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* ================= LOGIN CARD ================= */
        .login-container {
            background: #1e293b;
            width: 400px;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, .5);
            text-align: center;
        }

        .login-container h2 {
            margin-bottom: 25px;
            color: #22c55e;
            /* Success green header */
            font-weight: 600;
        }

        .error {
            color: #f87171;
            margin-bottom: 15px;
            font-size: 14px;
            background: rgba(248, 113, 113, 0.1);
            padding: 8px 10px;
            border-radius: 6px;
        }

        /* ================= INPUTS ================= */
        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 6px;
            color: #cbd5e1;
            font-size: 14px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #334155;
            border-radius: 8px;
            font-size: 14px;
            background: #0f172a;
            color: #e2e8f0;
        }

        .input-group input::placeholder {
            color: #94a3b8;
        }

        .input-group input:focus {
            border-color: #22c55e;
            /* Success border */
            outline: none;
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.3);
        }

        /* ================= OPTIONS ================= */
        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .options a {
            text-decoration: none;
            color: #22c55e;
            /* Success link */
        }

        /* ================= BUTTON ================= */
        .login-btn {
            width: 100%;
            padding: 12px;
            background: #22c55e;
            /* Success green */
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background: #16a34a;
            /* Darker green on hover */
            color: #fff;
        }

        /* ================= REGISTER ================= */
        .register-link {
            margin-top: 20px;
            font-size: 14px;
            color: #94a3b8;
        }

        .register-link a {
            color: #22c55e;
            text-decoration: none;
        }
    </style>




    <?php

    $stdList = getMarkCon("mark_con", "t_stat");
    if ($stdList->num_rows > 0) {
        while ($row = $stdList->fetch_assoc()) {
            $st_mk_stat = $row["pos_stat"];
        }
    }
    if ($st_mk_stat == "disb") {
        $stt = "acc_ban";
    } else {
        $stt = "teacher_home";
    }
    ?>


    <?php

    $stdList = getMarkCon("mark_con", "st_stat");
    if ($stdList->num_rows > 0) {
        while ($row = $stdList->fetch_assoc()) {
            $st_mk_stat1 = $row["pos_stat"];
        }
    }
    if ($st_mk_stat1 == "disb") {
        $stt1 = "acc_ban";
    } else {
        $stt1 = "student_home";
    }
    ?>
</head>
<?php

$error = "";

if (isset($_POST["log"])) {

    $username = trim($_POST['email']);      // username/id
    $password = trim($_POST['password']);

    // ================= USERS TABLE =================
    $stmt = $conn->prepare("SELECT * FROM users WHERE u_name = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $row = $result->fetch_assoc();

        if ($password == $row['u_pass']) {

            switch ($row['u_role']) {

                case "Admin":
                    $_SESSION['loginadmin'] = true;
                    $_SESSION['userid'] = $row['id'];
                    header("Location: admin.php");
                    exit();

                case "Manager":
                    $_SESSION['loginm'] = true;
                    $_SESSION['userid'] = $row['id'];
                    header("Location: manager_home.php");
                    exit();

                case "Viewer":
                    $_SESSION['loginViewer'] = true;
                    $_SESSION['userid'] = $row['id'];
                    header("Location: viewer_home.php");
                    exit();

                case "Assistante":
                    $_SESSION['loginass'] = true;
                    $_SESSION['userid'] = $row['id'];
                    header("Location: attendance.php");
                    exit();

                case "CEO":
                    $_SESSION['loginaceo'] = true;
                    $_SESSION['userid'] = $row['id'];
                    header("Location: ceo_home.php");
                    exit();
            }
        }
    }

    // ================= TEACHERS TABLE =================
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE t_name = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $teacher = $stmt->get_result();

    if ($teacher->num_rows == 1) {

        $row = $teacher->fetch_assoc();

        if ($password == $row['t_pass']) {

            $_SESSION['teacher_login'] = true;
            $_SESSION['teacher_id'] = $row['id'];
            $_SESSION['teacher_name'] = $row['t_name'];

            header("Location: ".$stt.".php");
            exit();
        }
    }

    // ================= STUDENTS TABLE =================
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $student = $stmt->get_result();

    if ($student->num_rows == 1) {

        $row = $student->fetch_assoc();

        if ($password == $row['st_id_number']) {

            $_SESSION['student_login'] = true;
            $_SESSION['student_id'] = $row['id'];
            $_SESSION['student_number'] = $row['st_id_number'];

            header("Location: ".$stt1.".php");
            exit();
        }
    }

    $error = "Invalid username or password.";
}
?>

<body>

    <div class="login-container">
        <h2>Welcome Back</h2>

        <?php if ($error != "") { ?>
            <div class="error"><?php echo $error; ?></div>
        <?php } ?>

        <form method="POST">
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="email" placeholder="Enter username" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>

            <div class="options">
                <label>
                    <input type="checkbox" name="remember"> Remember me
                </label>
                <a href="#">Forgot password?</a>
            </div>

            <button type="submit" name="log" class="login-btn">Login</button>
        </form>

        <div class="register-link">
            Don’t have an account? <a href="#">Sign Up</a>
        </div>
    </div>

</body>

</html>
