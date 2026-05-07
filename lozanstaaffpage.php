<?php include "manager_header.php"?>
 <style>
        /* ================= CLASS BUTTONS ================= */

        .letters {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
            padding: 30px;
        }

        .letters button {
            width: 90px;
            height: 50px;
            border: none;
            border-radius: 12px;
            background: #2563eb;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .letters button:hover {
            background: #1d4ed8;
            transform: scale(1.05);
        }

        /* ================= POPUP ================= */

        .popup {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.88);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 999;
            padding: 20px;

            opacity: 0;
            visibility: hidden;
            transform: scale(1.1);
            transition: 0.35s ease;
        }

        .popup.active {
            opacity: 1;
            visibility: visible;
            transform: scale(1);
        }

        /* ================= CIRCLE ================= */

        .circle-container {
            position: relative;
            width: min(92vw, 760px);
            height: min(92vw, 760px);
        }

        /* ================= CLOSE BUTTON ================= */

        .close-btn {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 110px;
            height: 110px;
            border: none;
            border-radius: 50%;
            background: red;
            color: #fff;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            z-index: 9999;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ================= TEACHERS ================= */

        .item {
            position: absolute;
            width: 170px;
            text-align: center;

            left: 50%;
            top: 50%;

            opacity: 0;
            transform: translate(-50%, -50%) scale(0.1);
            transition: 0.6s cubic-bezier(0.25, 1, 0.5, 1);
        }

        .popup.active .item {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }

        .item img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
        }

        .title {
            margin-top: 10px;
            font-size: 18px;
            font-weight: bold;
        }

        .subtitle {
            margin-top: 5px;
            font-size: 13px;
            color: #d1d5db;
        }

        /* ================= RESPONSIVE ================= */

        @media(max-width:700px) {

            .item img {
                width: 70px;
                height: 70px;
            }

            .title {
                font-size: 11px;
            }

            .subtitle {
                font-size: 9px;
            }

            .close-btn {
                width: 70px;
                height: 70px;
                font-size: 12px;
            }
        }
    </style>
    <div class="letters">

        <?php
        // database connection
        $conn = new mysqli('localhost', 'root', '12345678', 'lozan_tomar');

        foreach (range('A', 'Z') as $letter) {

            $className = "12-$letter";

            // get class status from database
            $sql = "SELECT class_status 
            FROM staffclasscon 
            WHERE class_name = '$className'";

            $result = $conn->query($sql);

            $isDisabled = false;

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();

                if ($row['class_status'] == 'disabled') {
                    $isDisabled = true;
                }
            }

            // button styles
            $bgColor = $isDisabled ? 'red' : '#0d6efd';
            $disabledAttr = $isDisabled ? 'disabled' : '';
            $onclick = $isDisabled ? '' : "onclick=\"openPopup('$className')\"";

            echo "
        <button 
            $onclick
            $disabledAttr
            style='
                background:$bgColor;
                color:white;
                border:none;
                padding:10px 15px;
                margin:5px;
                border-radius:5px;
                cursor:pointer;
            '
        >
            $className
        </button>
    ";
        }
        ?>

    </div>

    <!-- ================= POPUP ================= -->

    <div class="popup" id="popup">

        <div class="circle-container">

            <button class="close-btn" onclick="closePopup()">Close</button>

            <?php
            $sql = "SELECT * FROM lozanstaff";
            $result = $conn->query($sql);

            $teachers = [];

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $teachers[] = $row;
                }
            }

            $total = count($teachers);

            for ($x = 0; $x < $total; $x++) {

                $angle = $x * (360 / $total);
                $radius = 30;

                $left = 50 + $radius * cos(deg2rad($angle));
                $top  = 50 + $radius * sin(deg2rad($angle));

                $class = $teachers[$x]['class'];
                $name = $teachers[$x]['name'];
                $edu = $teachers[$x]['education'];
                $img = $teachers[$x]['teacher_img'];
            ?>

                <div class="item teacher-item"
                    data-class="<?php echo $class; ?>"
                    style="
            left:<?php echo $left; ?>%;
            top:<?php echo $top; ?>%;
            transition-delay:<?php echo $x * 0.08; ?>s
         ">

                    <img src="teachers_img/<?php echo $img; ?>">

                    <div class="subtitle">
                        <button class="btn btn-info col-6" style="font-weight: bold; font-size: larger;"> <?php echo $edu; ?></button><br> <span style="font-weight: bold; font-size: larger;"><?php echo $name; ?></span>
                    </div>
                    <div class="title">
                    </div>



                </div>

            <?php } ?>

        </div>
    </div>

    <script>
        const popup = document.getElementById("popup");

        function openPopup(className) {

            popup.classList.add("active");

            let items = document.querySelectorAll(".teacher-item");

            items.forEach(item => {

                if (item.dataset.class === className) {
                    item.style.display = "block";
                } else {
                    item.style.display = "none";
                }

            });
        }

        function closePopup() {
            popup.classList.remove("active");
        }

        /* close when clicking background */
        popup.addEventListener("click", function(e) {
            if (e.target === popup) {
                closePopup();
            }
        });
    </script>
<?php include "manager_footer.php"?>