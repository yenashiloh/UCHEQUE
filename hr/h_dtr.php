<?php
include('./includes/authentication.php');

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    $query = "DELETE FROM dtr_extracted_data WHERE id = ?";
    $stmt = $con->prepare($query);

    if ($stmt === false) {
        die("Error preparing query: " . $con->error);
    }   

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: dtr.php?deleted=true");
        exit();
    } else {
        echo "Error deleting record: " . $stmt->error;
    }

    $stmt->close();
}


include('./includes/header.php');
include('./includes/sidebar.php');
include('./includes/topbar.php');
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <div class="tabular--wrapper">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success" id="successMessage" style="opacity: 1; transition: opacity 1s;">
                <?php echo $_SESSION['success_message']; ?>
            </div>
            <?php unset($_SESSION['success_message']);?>
            <script>
                setTimeout(function() {
                    var successMessage = document.getElementById('successMessage');
                    successMessage.style.opacity = 0;
                    setTimeout(function() {
                        successMessage.style.display = 'none';
                    }, 1000); 
                }, 3000);
            </script>
        <?php endif; ?>

        <div class="add">
        <div class="filter">
            <form method="GET" action="" class="d-flex align-items-center">
            <input type="text" name="search_user" placeholder="Search user..." 
                    value="<?php echo isset($_GET['search_user']) ? $_GET['search_user'] : ''; ?>" 
                    style="width: 200px; margin-right: 10px; height: 43px;">

            <select name="academic_year" onchange="this.form.submit()" style="height: 43px; margin-right: 10px; width: 220px;">
                <option value="" selected>Select Academic Year</option>
                <?php
                $academicYearQuery = "SELECT * FROM academic_years";
                $academicYearResult = $con->query($academicYearQuery);
                while ($academicYear = $academicYearResult->fetch_assoc()):
                ?>
                <option value="<?php echo $academicYear['academic_year_id']; ?>" 
                    <?php echo (isset($_GET['academic_year']) && $_GET['academic_year'] == $academicYear['academic_year_id']) ? 'selected' : ''; ?>>
                    <?php echo $academicYear['academic_year']; ?>
                </option>
                <?php endwhile; ?>
            </select>

            <select name="semester" onchange="this.form.submit()" style="height: 43px; margin-right: 10px; width: 180px;">
                <option value="" selected>Select Semester</option>
                <?php
                $semesterQuery = "SELECT * FROM semesters";
                $semesterResult = $con->query($semesterQuery);
                while ($semester = $semesterResult->fetch_assoc()):
                ?>
                <option value="<?php echo $semester['semester_id']; ?>" 
                    <?php echo (isset($_GET['semester']) && $_GET['semester'] == $semester['semester_id']) ? 'selected' : ''; ?>>
                    <?php echo $semester['semester_name']; ?>
                </option>
                <?php endwhile; ?>
            </select>
            </form>
        </div>

        </div>
        <div class="table-container">
            <?php
            $search_user = isset($_GET['search_user']) ? $_GET['search_user'] : '';
            $academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';
            $semester = isset($_GET['semester']) ? $_GET['semester'] : '';

            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'name'; 
            $order = isset($_GET['order']) ? $_GET['order'] : 'asc';

            $maxHours = 40; // REGULAR HRS
            $creditThreshold = 12;  // MAXIMUM ALLOWED POLICY
            
            $query = "SELECT d.id, d.userId, d.academic_year_id, d.semester_id, 
                d.week1, d.week2, d.week3, d.week4, d.week5, d.overall_total, 
                d.filePath, d.month_year, 
                e.firstName, e.middleName, e.lastName, e.employeeId,
                a.academic_year, s.semester_name, 
                COALESCE(itl.totalOverload, 0) AS totalOverload,
                itl.designated,
                d.week1_overload, d.week2_overload, d.week3_overload, d.week4_overload
            FROM dtr_extracted_data d
            JOIN employee e ON d.userId = e.userId
            JOIN academic_years a ON d.academic_year_id = a.academic_year_id
            JOIN semesters s ON d.semester_id = s.semester_id
            LEFT JOIN itl_extracted_data itl ON d.userId = itl.userId
            WHERE 1=1";

            if (!empty($search_user)) {
                $search_user = $con->real_escape_string($search_user);
                $query .= " AND (e.firstName LIKE '%$search_user%' 
                                OR e.middleName LIKE '%$search_user%' 
                                OR e.lastName LIKE '%$search_user%' 
                                OR e.employeeId LIKE '%$search_user%')";
            }

            if (!empty($academic_year)) {
                $query .= " AND d.academic_year_id = $academic_year";
            }

            if (!empty($semester)) {
                $query .= " AND d.semester_id = $semester";
            }

            $result = $con->query($query);

            if (!$result) {
                die("Error fetching data: " . $con->error);
            }

            $query .= " ORDER BY ";

            switch ($sort) {
                case 'name':
                    $query .= "e.firstName " . $order . ", e.middleName " . $order . ", e.lastName " . $order;
                    break;
                case 'totalOverload':
                    $query .= "COALESCE(itl.totalOverload, 0) " . $order;
                    break;
                case 'designated':
                    $query .= "itl.designated " . $order;
                    break;
                default:
                    $query .= "e.firstName " . $order;
            }
                $result = $con->query($query);

                if (!$result) {
                    die("Error fetching data: " . $con->error);
                }
            ?>

            <table class="table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th> Name
                            <a href="?sort=name&order=asc" class="sort-arrow <?php echo $sort === 'name' && $order === 'asc' ? 'active' : ''; ?>">▲</a>
                            <a href="?sort=name&order=desc" class="sort-arrow <?php echo $sort === 'name' && $order === 'desc' ? 'active' : ''; ?>">▼</a>
                        </th>

                        <th>Designation</th>
                        <th>Semester/A.Y</th>
                        <th>Month/Year</th>
                        
                        <th>A.O</th>
                        
                        <th>Week 1</th>
                        <th>Week 2</th>
                        <th>Week 3</th>
                        <th>Week 4</th>
                        <th>SC/CTO
                            <a href="?sort=total_credits&order=asc" class="sort-arrow <?php echo $sort === 'total_credits' && $order === 'asc' ? 'active' : ''; ?>">▲</a>
                            <a href="?sort=total_credits&order=desc" class="sort-arrow <?php echo $sort === 'total_credits' && $order === 'desc' ? 'active' : ''; ?>">▼</a>
                        </th>
                        <th>Overload</th> 
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()):
                        $weeks = [                        //WEEK HRS
                            'week1' => $row['week1'],
                            'week2' => $row['week2'],
                            'week3' => $row['week3'],
                            'week4' => $row['week4'],
                            'week5' => $row['week5'],
                        ];

                        $totalOverload = $row['totalOverload'];
                        $excess = []; 
                        $overload = []; 

                        foreach ($weeks as $key => $weekHours) {
                            if ($weekHours > $maxHours) {
                                $overload[$key] = round($weekHours - $maxHours, 2);    // RETRIEVED HRS
                                $excess[$key] = round($weekHours - $maxHours - $totalOverload, 2); // WEEKLY OVERLOAD
                            } else {
                                $overload[$key] = 0;
                                $excess[$key] = 0;
                            }
                        }

                        $totalCredits = 0;
                        $weekOverloads = 0;
                        $totalCreditsPerWeek = [];

                        foreach (['week1_overload', 'week2_overload', 'week3_overload', 'week4_overload'] as $week) {
                            $weekOverloads += $row[$week];
                            $totalCreditsForWeek = 0;

                            if ($row[$week] > 12) {
                                $totalCreditsForWeek = $row[$week] - 12;
                                $totalCredits += $totalCreditsForWeek;
                            }

                            // Store the total credits for the current week
                            $totalCreditsPerWeek[$week] = $totalCreditsForWeek;
                        }

                        if ($totalCredits > 0) {
                            $weekOverloads -= $totalCredits;
                            if ($weekOverloads < 0) {
                                $weekOverloads = 0;
                            }
                        }

                        // // Display total credits for each week
                        // foreach ($totalCreditsPerWeek as $week => $credits) {
                        //     echo "Total credits for {$week}: {$credits}<br>";
                        // }

                    ?>

                    <tr>
                        <td><?php echo htmlspecialchars($row['employeeId']); ?></td>
                        <td><?php echo htmlspecialchars($row['firstName'] . ' ' . $row['middleName'] . ' ' . $row['lastName']); ?></td>
                        <td><?php echo htmlspecialchars($row['designated']); ?></td>
                        <td><?php echo htmlspecialchars($row['semester_name'] . ' ' . $row['academic_year']); ?></td>
                        <td><?php echo htmlspecialchars($row['month_year']); ?></td>
                        <td><?php echo htmlspecialchars($row['totalOverload']); ?></td>
                        <td>
                            <strong>OL:</strong> <br>
                            <?php echo htmlspecialchars($row['week1_overload']); ?> <br>
                            <strong>SC/CTO:</strong> <br>
                            <?php echo htmlspecialchars($totalCreditsPerWeek['week1_overload']); ?>
                        </td>
                        <td>
                            <strong>OL:</strong> <br>
                            <?php echo htmlspecialchars($row['week2_overload']); ?> <br>
                            <strong>SC/CTO:</strong> <br>
                            <?php echo htmlspecialchars($totalCreditsPerWeek['week2_overload']); ?>
                        </td>
                        <td>
                            <strong>OL:</strong> <br>
                            <?php echo htmlspecialchars($row['week3_overload']); ?> <br>
                            <strong>SC/CTO:</strong> <br>
                            <?php echo htmlspecialchars($totalCreditsPerWeek['week3_overload']); ?>
                        </td>
                        <td>
                            <strong>OL:</strong> <br>
                            <?php echo htmlspecialchars($row['week4_overload']); ?> <br>
                            <strong>SC/CTO:</strong> <br>
                            <?php echo htmlspecialchars($totalCreditsPerWeek['week4_overload']); ?>
                        </td>
                        <td>
                            <?php echo ($totalCredits > 0) ? htmlspecialchars($totalCredits) : '0'; ?>
                        </td>
                        <td>
                            <?php echo ($weekOverloads > 0) ? htmlspecialchars($weekOverloads) : '0'; ?>
                        </td>
                    
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
    </div>

<?php
include('./includes/footer.php');
?>
