<?php
include('./includes/authentication.php');
include('./includes/header.php');
include('./includes/sidebar.php');
include('./includes/topbar.php');
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<div class="tabular--wrapper">
    <h3 class="main--title">Request DTR</h3>
    <div class="add">
    <div class="filter">
            <form method="GET" action="" class="d-flex align-items-center">
        
            <select name="academic_year" onchange="this.form.submit()" style="height: 43px; margin-right: 10px; width: 220px;">
                <option value="" selected>Select Request Year</option>
                <?php
                $academicYearQuery = "SELECT requestDate FROM request";
                $academicYearResult = $con->query($academicYearQuery);
                while ($academicYear = $academicYearResult->fetch_assoc()):
                ?>
                <option value="<?php echo $academicYear['academic_year_id']; ?>" 
                    <?php echo (isset($_GET['academic_year']) && $_GET['academic_year'] == $academicYear['academic_year_id']) ? 'selected' : ''; ?>>
                    <?php echo $academicYear['academic_year']; ?>
                </option>
                <?php endwhile; ?>
            </select>

            <select name="semester" onchange="this.form.submit()" style="height: 43px; margin-right: 10px; width: 220px;">
                <option value="" selected>Select Request Month</option>
                <?php
                $semesterQuery = "SELECT * FROM request";
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
        <table class="table">
            <thead>
                <tr>
                    <th>Request Date</th>
                    <th>Request Type</th>
                    <th>Name</th> 
                    <th>Start Month</th>
                    <th>End Month</th>
                    <th>Approved Date</th> <!-- Correct column for Approved Date -->
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
            <?php
              
                $recordsPerPage = 10;

                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $offset = ($page - 1) * $recordsPerPage; 

                $totalRecordsQuery = "SELECT COUNT(*) as total FROM request";
                $totalRecordsResult = $con->query($totalRecordsQuery);
                $totalRecords = $totalRecordsResult->fetch_assoc()['total'];

                $totalPages = ceil($totalRecords / $recordsPerPage);

                $query = "SELECT r.requestId, r.requestDate, e.employeeId, e.firstName, e.middleName, e.lastName, 
                        r.startMonth, r.endMonth, r.status, r.dateApproved, r.requestType
                        FROM request r
                        JOIN employee e ON r.userId = e.userId 
                        ORDER BY r.requestDate DESC
                        LIMIT $recordsPerPage OFFSET $offset"; 

                $result = $con->query($query); 
                ?>

                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $fullName = htmlspecialchars($row['firstName']) . ' ' . htmlspecialchars($row['middleName']) . ' ' . htmlspecialchars($row['lastName']);
                            $approveLink = $row['status'] != 'Approved' ? "<a href='#' class='btn btn-link' onclick='approveRequest(" . $row['requestId'] . ")'>Approve</a>" : "";

                            // Check if dateApproved exists and is valid
                            if (!empty($row['dateApproved'])) {
                                $timestamp = strtotime($row['dateApproved']); // Convert to UNIX timestamp
                                $dateApproved = $timestamp ? date("F j, Y, g:i a", $timestamp) : '--';
                            } else {
                                $dateApproved = '--';
                            }

                            echo "<tr id='request-" . htmlspecialchars($row['requestId']) . "'>
                                    <td>" . date("F j, Y, g:i a", strtotime($row['requestDate'])) . "</td>
                                    <td>" . htmlspecialchars($row['requestType']) . "</td> 
                                    <td>" . $fullName . "</td> 
                                    <td>" . htmlspecialchars($row['startMonth']) . "</td>
                                    <td>" . htmlspecialchars($row['endMonth']) . "</td>
                                    <td>" . htmlspecialchars($dateApproved) . "</td>
                                    <td>" . htmlspecialchars($row['status']) . "</td>
                                    <td>" . $approveLink . "</td> 
                                </tr>";
                        }
                    } else {
                        echo "<tr>
                                <td colspan='8' class='text-center'>No requests found</td> <!-- Ensure the colspan includes all columns -->
                            </tr>";
                    }
                    ?>
                </tbody>

                </table>

                <!-- Pagination Controls -->
                <div class="pagination" id="pagination">
                    <?php
                    if ($totalPages > 1) {
                   
                        echo '<a href="?page=1" class="pagination-button">&laquo;</a>';
                        
                        $prevPage = max(1, $page - 1);
                        echo '<a href="?page=' . $prevPage . '" class="pagination-button">&lsaquo;</a>';

                        // Pagination links
                        for ($i = 1; $i <= $totalPages; $i++) {
                            $activeClass = ($i == $page) ? 'active' : ''; 
                            echo '<a href="?page=' . $i . '" class="pagination-button ' . $activeClass . '">' . $i . '</a>';
                        }

                        $nextPage = min($totalPages, $page + 1);
                        echo '<a href="?page=' . $nextPage . '" class="pagination-button">&rsaquo;</a>';

                        echo '<a href="?page=' . $totalPages . '" class="pagination-button">&raquo;</a>';
                    }
                    ?>
                </div>


    </div>
</div>

<?php
include('./includes/footer.php');
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<script>
function approveRequest(requestId) {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "./controller/approve_request.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            var response = JSON.parse(xhr.responseText);
            if (response.success) {
                var row = document.getElementById('request-' + requestId);
                row.querySelector('td:nth-child(6)').textContent = new Date(response.dateApproved).toLocaleString(); // Correct cell for Approved Date
                row.querySelector('td:nth-child(7)').textContent = 'Approved'; // Update Status cell
                row.querySelector('td:nth-child(8)').innerHTML = ''; // Remove Action link
            } else {
                alert('Error: ' + response.error);
            }
        }
    };
    xhr.send("requestId=" + requestId);
}


</script>