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
        header("Location: f_dtr.php?deleted=true");
        exit();
    } else {
        echo "Error deleting record: " . $stmt->error;
    }

    $stmt->close();
}


include('./includes/header.php');
include('./includes/sidebar.php');
include('./includes/topbar.php');

$loggedInUserId = $_SESSION['auth_user']['userId']; 
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<div class="tabular--wrapper">

<h3 class="main--title">Daily Time Record</h3>
    <div class="add">
        </div>
        <div class="table-container">
            <?php
                $search_user = isset($_GET['search_user']) ? $_GET['search_user'] : '';
                $academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';
                $semester = isset($_GET['semester']) ? $_GET['semester'] : '';

                $maxHours = 40; 
                $creditThreshold = 12;

                $query = "SELECT d.id, d.userId, d.academic_year_id, d.semester_id, 
                            d.week1, d.week2, d.week3, d.week4, d.week5, d.overall_total, 
                            d.fileName, d.month_year, 
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
                        WHERE d.userId = ?"; 

                if (!empty($academic_year)) {
                    $query .= " AND d.academic_year_id = ?";
                }
                if (!empty($semester)) {
                    $query .= " AND d.semester_id = ?";
                }

                $stmt = $con->prepare($query);
                if (!$stmt) {
                    die("Error preparing statement: " . $con->error);
                }

                $params = [$loggedInUserId];
                $types = "i";

                if (!empty($academic_year)) {
                    $params[] = $academic_year;
                    $types .= "i";
                }
                if (!empty($semester)) {
                    $params[] = $semester;
                    $types .= "i";
                }

                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();

                if (!$result) {
                    die("Error fetching data: " . $con->error);
                }
            ?>
        <?php if (isset($_SESSION['status'])): ?>
            <div id="statusAlert" class="alert alert-<?php echo $_SESSION['status_code'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['status']; ?>
            </div>
            <?php unset($_SESSION['status']); ?>
        <?php endif; ?>

        <script>
            setTimeout(() => {
                const alert = document.getElementById('statusAlert');
                if (alert) {
                    alert.classList.remove('show');
                    alert.classList.add('fade');   
                    setTimeout(() => alert.remove(), 500); 
                }
            }, 3000);
        </script>

        <table>
            <thead>
                <tr>
                    <th>Academic Year</th>
                    <th>Semester</th>
                    <th>Month</th>
                    <th>Total Credits</th>
                    <th>Overload Pay</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()):
                    $weeks = [
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
                            $overload[$key] = round($weekHours - $maxHours, 2);
                            $excess[$key] = round($weekHours - $maxHours - $totalOverload, 2);
                        } else {
                            $overload[$key] = 0;
                            $excess[$key] = 0;
                        }
                    }

                    $totalCredits = 0;
                    $weekOverloads = 0;

                    foreach (['week1_overload', 'week2_overload', 'week3_overload', 'week4_overload'] as $week) {
                        $weekOverloads += $row[$week];
                        if ($row[$week] > 12) {
                            $totalCredits += ($row[$week] - 12);
                        }
                    }

                    if ($totalCredits > 0) {
                        $weekOverloads -= $totalCredits;
                        if ($weekOverloads < 0) {
                            $weekOverloads = 0;
                        }
                    }
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                        <td><?php echo htmlspecialchars($row['semester_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['month_year']); ?></td>
                        <td><?php echo ($totalCredits > 0) ? $totalCredits : '0'; ?></td>
                        <td><?php echo ($weekOverloads > 0) ? $weekOverloads : '0'; ?></td>
                        <td>
                        <a href="./controller/download-itl.php?employee_id=' . htmlspecialchars($row['userId']) . '" class="action">Download</a>
                            
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No records found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>


        </div>
        </div>

    <?php
    include('./includes/footer.php');
    ?>
 <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
 <script>
    document.getElementById('requestForm').addEventListener('submit', function (e) {
        const startMonth = document.getElementById('startMonth').value;
        const endMonth = document.getElementById('endMonth').value;

        const monthOrder = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];

        if (monthOrder.indexOf(startMonth) > monthOrder.indexOf(endMonth)) {
            e.preventDefault();
            alert("Error: Starting month must be before or the same as the ending month.");
        } else {
            Swal.fire({
                title: 'Success!',
                text: 'The request was submitted successfully.',
                icon: 'success',
                confirmButtonColor: '#3085d6',
            });
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('deleted') && urlParams.get('deleted') === 'true') {
        Swal.fire({
            title: 'Deleted!',
            text: 'The record has been deleted successfully.',
            icon: 'success',
            confirmButtonColor: '#3085d6',
        });
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "f_dtr.php?id=" + id + "&deleted=true";
            }
        });
    }
</script>