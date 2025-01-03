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
                <select name="month" onchange="this.form.submit()" style="height: 43px; margin-right: 10px; width: 220px;">
                    <option value="">Select Request Month</option>
                    <?php
                        $existingMonthsQuery = "SELECT DISTINCT startMonth FROM request WHERE startMonth IS NOT NULL";
                        $existingMonthsResult = $con->query($existingMonthsQuery);
                        $existingMonths = [];

                        if ($existingMonthsResult) {
                            while ($row = $existingMonthsResult->fetch_assoc()) {
                                $existingMonths[] = $row['startMonth'];
                            }
                        }

                        $allMonths = [
                            'January', 'February', 'March', 'April',
                            'May', 'June', 'July', 'August',
                            'September', 'October', 'November', 'December'
                        ];

                        foreach ($allMonths as $month) {
                            $selected = (isset($_GET['month']) && $_GET['month'] == $month) ? 'selected' : '';
                            $displayText = $month . (in_array($month, $existingMonths) ? ' ' : '');
                            echo "<option value='" . htmlspecialchars($month) . "' $selected>" .
                                htmlspecialchars($displayText) . "</option>";
                        }
                    ?>
                </select>

                <select name="request_year" onchange="this.form.submit()" style="height: 43px; margin-right: 10px; width: 220px;">
                    <option value="" selected>Select Request Year</option>
                    <?php
                        $requestYearQuery = "SELECT DISTINCT YEAR(requestDate) AS year FROM request WHERE requestDate IS NOT NULL";
                        $requestYearResult = $con->query($requestYearQuery);
                        while ($requestYear = $requestYearResult->fetch_assoc()):
                            $selected = (isset($_GET['request_year']) && $_GET['request_year'] == $requestYear['year']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($requestYear['year']) . "' {$selected}>" .
                                htmlspecialchars($requestYear['year']) . "</option>";
                        endwhile;
                    ?>
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
                    <th>Approved Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <?php
                date_default_timezone_set('Asia/Manila');

                $recordsPerPage = 10;
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $offset = ($page - 1) * $recordsPerPage;

                $monthFilter = isset($_GET['month']) && $_GET['month'] !== '' ? 
                    "AND startMonth = '" . $con->real_escape_string($_GET['month']) . "'" : '';
                $yearFilter = isset($_GET['request_year']) && $_GET['request_year'] !== '' ? 
                    "AND YEAR(requestDate) = " . (int)$_GET['request_year'] : '';

                $totalRecordsQuery = "SELECT COUNT(*) as total FROM request WHERE 1 $monthFilter $yearFilter";
                $totalRecordsResult = $con->query($totalRecordsQuery);
                $totalRecords = $totalRecordsResult->fetch_assoc()['total'];
                $totalPages = ceil($totalRecords / $recordsPerPage);

                $query = "SELECT r.requestId, r.requestDate, e.employeeId, e.firstName, e.middleName, e.lastName, 
                            r.startMonth, r.startYear, r.endMonth, r.endYear, r.status, r.dateApproved, r.requestType
                        FROM request r
                        JOIN employee e ON r.userId = e.userId 
                        WHERE 1 $monthFilter $yearFilter
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

                    $requestDate = new DateTime($row['requestDate']);
                    $requestDate->setTimezone(new DateTimeZone('Asia/Manila'));

                    if (!empty($row['dateApproved'])) {
                        $dateApproved = new DateTime($row['dateApproved']);
                        $dateApproved->setTimezone(new DateTimeZone('Asia/Manila'));

                        $formattedDate = $dateApproved->format('F j, Y, g:i');
                        $amPm = strtoupper($dateApproved->format('a')); 
                        $dateApprovedFormatted = $formattedDate . ' ' . $amPm;
                    } else {
                        $dateApprovedFormatted = '--';
                    }

                    $startMonthYear = htmlspecialchars($row['startMonth']) . ' ' . htmlspecialchars($row['startYear']);
                    
                    if (empty($row['endMonth']) || empty($row['endYear'])) {
                        $endMonthYear = '--';
                    } else {
                        $endMonthYear = htmlspecialchars($row['endMonth']) . ' ' . htmlspecialchars($row['endYear']);
                    }

                    echo "<tr id='request-" . htmlspecialchars($row['requestId']) . "'>
                            <td>" . $requestDate->format('F j, Y, g:i a') . "</td>
                            <td>" . htmlspecialchars($row['requestType']) . "</td> 
                            <td>" . $fullName . "</td> 
                            <td>" . $startMonthYear . "</td>
                            <td>" . $endMonthYear . "</td>
                            <td>" . htmlspecialchars($dateApprovedFormatted) . "</td>
                            <td>" . htmlspecialchars($row['status']) . "</td>
                            <td>" . $approveLink . "</td> 
                        </tr>";
                }
            } else {
                echo "<tr>
                        <td colspan='8' class='text-center'>No requests found</td>
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

                    var dateApproved = new Date(response.dateApproved);

                    var optionsDate = { year: 'numeric', month: 'long', day: 'numeric', timeZone: 'Asia/Manila' };
                    var optionsTime = { hour: 'numeric', minute: 'numeric', hour12: true, timeZone: 'Asia/Manila' };
                    var formattedDate = new Intl.DateTimeFormat('en-PH', optionsDate).format(dateApproved);
                    var formattedTime = new Intl.DateTimeFormat('en-PH', optionsTime).format(dateApproved);

                    var finalFormattedDate = `${formattedDate}, ${formattedTime}`;

                    row.querySelector('td:nth-child(6)').textContent = finalFormattedDate; 
                    row.querySelector('td:nth-child(7)').textContent = 'Approved'; 
                    row.querySelector('td:nth-child(8)').innerHTML = ''; 
                } else {
                    alert('Error: ' + response.error);
                }
            }
        };
        xhr.send("requestId=" + requestId);
    }
</script>