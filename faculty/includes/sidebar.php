<?php
    $userId = $_SESSION['auth_user']['userId'];

    $query = "SELECT role_id FROM employee_role WHERE userId = '$userId'";
    $query_run = mysqli_query($con, $query);

    $userRoles = [];
    while($row = mysqli_fetch_assoc($query_run)) {
        $userRoles[] = $row['role_id'];
    }

    sort($userRoles);

    $hasSpecificRoles = ($userRoles === ['2', '4'] || $userRoles === [2, 4]);

?>
<body>
    <div class="sidebar">
        <div class="logo"><img src="./assets/images/logoall-light.png" alt=""></div>
        <ul class="menu">
            <li><a href="f_dash.php"><i class="bx bxs-dashboard"></i><span>faculty dashboard</span></a></li>
            <li><a href="f_request.php"><i class='bx bxs-file-plus'></i><span>Request Overload</span></a></li>
            <li><a href="f_itl.php"><i class='bx bxs-doughnut-chart'></i><span>faculty ITL</span></a></li>
            <li><a href="f_dtr.php"><i class='bx bxs-time'></i><span>faculty DTR</span></a></li>
            <li><a href="f_overload.php"><i class="bx bxs-user-check"></i><span>faculty Overload</span></a></li>

            <?php if ($hasSpecificRoles): ?>
                <li class="switch"> <a href="../staff/s_dash.php"><i class='bx bx-code'></i><span>switch</span></a></li>
            <?php endif; ?>
        </ul>
    </div>
</body>
