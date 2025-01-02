<?php
include('./includes/authentication.php');

// Handle the deletion request
if (isset($_GET['usesrId']) && !empty($_GET['usesrId'])) {
    $id = $_GET['usesrId'];
    $query = "DELETE FROM itl_extracted_data WHERE usesrId = ?";
    $stmt = $con->prepare($query);

    if ($stmt === false) {
        die("Error preparing query: " . $con->error);
    }

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Record deleted successfully.";
        header("Location: dtr.php?deleted=true");
        exit();
    } else {
        $_SESSION['error_message'] = "Error deleting record: " . $stmt->error;
    }

    $stmt->close();
}

include('./includes/header.php');
include('./includes/sidebar.php');
include('./includes/topbar.php');
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<div class="tabular--wrapper">
    <!-- Success/Error Message Display -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success" id="successMessage">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger" id="errorMessage">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <div class="add">
        <div class="filter">
            <form method="GET" action="">
                <input type="text" name="search_user" placeholder="Search user..." value="<?php echo htmlspecialchars($_GET['search_user'] ?? ''); ?>" style="width: 200px; margin-right: 10px;" onkeydown="if(event.key === 'Enter') this.form.submit();">
                
                <select name="academic_year_id" onchange="this.form.submit()" style="width: 200px; margin-right: 10px;">
                    <option value="" selected>Select Academic Year</option>
                    <?php
                    $academicYearQuery = "SELECT academic_year_id, academic_year FROM academic_years";
                    $academicYearResult = $con->query($academicYearQuery);
                    while ($row = $academicYearResult->fetch_assoc()) {
                        $selected = isset($_GET['academic_year_id']) && $_GET['academic_year_id'] == $row['academic_year_id'] ? 'selected' : '';
                        echo "<option value='{$row['academic_year_id']}' $selected>{$row['academic_year']}</option>";
                    }
                    ?>
                </select>

                <select name="semester_id" onchange="this.form.submit()" style="width: 200px; margin-right: 10px;">
                    <option value="" selected>Select Semester</option>
                    <?php
                    $semesterQuery = "SELECT semester_id, semester_name FROM semesters";
                    $semesterResult = $con->query($semesterQuery);
                    while ($row = $semesterResult->fetch_assoc()) {
                        $selected = isset($_GET['semester_id']) && $_GET['semester_id'] == $row['semester_id'] ? 'selected' : '';
                        echo "<option value='{$row['semester_id']}' $selected>{$row['semester_name']}</option>";
                    }
                    ?>
                </select>
            </form>
        </div>

        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class='bx bxs-file-import'></i>
            <span class="text">Import ITL</span>
        </button>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Designation</th>
                    <th>Academic Year</th>
                    <th>Semester</th>
                    <th>Actual Overload</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $limit = 20;
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $page = max($page, 1);
                $offset = ($page - 1) * $limit;

                $search_user = $_GET['search_user'] ?? '';
                $academic_year_id = $_GET['academic_year_id'] ?? '';
                $semester_id = $_GET['semester_id'] ?? '';

                $whereClauses = ["employee_role.role_id = 2"]; // Faculty only

                if ($search_user) {
                    $whereClauses[] = "(employee.firstName LIKE '%$search_user%' OR employee.lastName LIKE '%$search_user%')";
                }
                if ($academic_year_id) {
                    $whereClauses[] = "itl_extracted_data.academic_year_id = '$academic_year_id'";
                }
                if ($semester_id) {
                    $whereClauses[] = "itl_extracted_data.semester_id = '$semester_id'";
                }

                $whereClause = implode(' AND ', $whereClauses);

                $totalQuery = "
                    SELECT COUNT(*) as total
                    FROM employee
                    INNER JOIN itl_extracted_data ON employee.userId = itl_extracted_data.userId
                    INNER JOIN employee_role ON employee.userId = employee_role.userId
                    WHERE $whereClause";
                $totalResult = $con->query($totalQuery);
                $totalRows = $totalResult->fetch_assoc()['total'] ?? 0;
                $totalPages = ceil($totalRows / $limit);

                $sort = $_GET['sort'] ?? 'name';
                $order = $_GET['order'] ?? 'ASC';
                $sortColumn = $sort === 'name' ? "CONCAT(employee.firstName, ' ', employee.lastName)" : 'itl_extracted_data.totalOverload';

                $sql = "
                    SELECT employee.employeeId, employee.firstName, employee.middleName, employee.lastName, 
                        itl_extracted_data.userId, itl_extracted_data.totalOverload, 
                        itl_extracted_data.designated, academic_years.academic_year, semesters.semester_name, 
                        itl_extracted_data.filePath
                    FROM employee
                    LEFT JOIN itl_extracted_data ON employee.userId = itl_extracted_data.userId
                    LEFT JOIN employee_role ON employee.userId = employee_role.userId
                    LEFT JOIN academic_years ON itl_extracted_data.academic_year_id = academic_years.academic_year_id
                    LEFT JOIN semesters ON itl_extracted_data.semester_id = semesters.semester_id
                    WHERE $whereClause
                    ORDER BY $sortColumn $order
                    LIMIT $limit OFFSET $offset";

                $result = $con->query($sql);

                $totalOverload = '--';

                if ($result && $result->num_rows > 0) {
                    $counter = $offset;
                    while ($row = $result->fetch_assoc()) {
                        $counter++;
                        $fullName = trim($row['firstName'] . ' ' . $row['middleName'] . ' ' . $row['lastName']);
                        
                        $filePath = htmlspecialchars($row['filePath']);
                        $fileUploaded = !empty($filePath); 
                        $deleteDisabled = !$fileUploaded ? 'style="pointer-events: none; color: gray;"' : '';
                        $downloadLink = $fileUploaded ? 'uploads/' . $filePath : '--';
                        $downloadDisabled = !$fileUploaded 
                            ? 'style="pointer-events: none; color: gray;"' 
                            : '';
                
                        if (!$fileUploaded) {
                            $totalOverload = '--'; 
                        } else {
                            $totalOverload = (isset($row['totalOverload']) && $row['totalOverload'] > 0) 
                                ? htmlspecialchars($row['totalOverload']) 
                                : "<span style='color: red;'>No Overload</span>";
                        }
                
                
                        echo "<tr>
                                <td>$counter</td>
                                <td>$fullName</td>
                                <td>" . htmlspecialchars($row['designated']) . "</td>
                                <td>" . htmlspecialchars($row['academic_year']) . "</td>
                                <td>" . htmlspecialchars($row['semester_name']) . "</td>
                                <td>$totalOverload</td>
                                <td>
                                    <a href='$downloadLink' class='action download-link' download $downloadDisabled title='Download the file'>Download</a>
                                    <a href='controller/delete-itl.php?userId=" . $row['userId'] . "' 
                                    onclick=\"return confirm('Are you sure you want to delete the record for $fullName? This action cannot be undone.');\" 
                                    class='action delete-link' delete  $deleteDisabled title='Delete this record' style='color: red;' >
                                    <i class='bx bxs-trash'></i> Delete
                                    </a>
                                </td>
                            </tr>";
                    }
                
                } else {
                    echo "<tr><td colspan='7'>No users found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination" id="pagination">
            <?php if ($totalPages > 1): ?>
                <a href="?page=1" class="pagination-button">&laquo;</a>
                <a href="?page=<?php echo max(1, $page - 1); ?>" class="pagination-button">&lsaquo;</a>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="pagination-button <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <a href="?page=<?php echo min($totalPages, $page + 1); ?>" class="pagination-button">&rsaquo;</a>
                <a href="?page=<?php echo $totalPages; ?>" class="pagination-button">&raquo;</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('./includes/footer.php'); ?>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Import Individual Teacher's Load</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="./controller/import-itl.php" method="POST" enctype="multipart/form-data">
                    <!-- Form fields for Importing ITL -->
                    <div class="mb-3">
                        <label for="userId" class="form-label">Select User</label>
                        <select class="form-control" id="userId" name="userId" required>
                            <option value="" disabled selected>---Select User---</option>
                            <?php
                            $query = "SELECT employee.userId, employee.employeeId, employee.firstName, employee.middleName, employee.lastName FROM employee INNER JOIN employee_role ON employee.userId = employee_role.userId WHERE employee_role.role_id = 2"; // Only Faculty
                            $result = $con->query($query);
                            while ($row = $result->fetch_assoc()) {
                                $fullName = $row['firstName'] . ' ' . $row['middleName'] . ' ' . $row['lastName'];
                                echo "<option value='" . $row['userId'] . "'>" . htmlspecialchars($fullName) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Academic Year Select -->
                    <div class="mb-3">
                        <label for="academicYear" class="form-label">Select Academic Year</label>
                        <select class="form-control" id="academicYear" name="academicYear" required>
                            <option value="" selected>Select Academic Year</option>
                            <option value="1">2024-2025</option>
                            <option value="2">2025-2026</option>
                            <option value="3">2026-2027</option>
                            <option value="4">2027-2028</option>
                            <option value="5">2028-2029</option>
                            <option value="6">2029-2030</option>
                        </select>
                    </div>

                    <!-- Semester Select -->
                    <div class="mb-3">
                        <label for="semester" class="form-label">Select Semester</label>
                        <select class="form-control" id="semester" name="semester" required>
                            <option value="" selected>Select Semester Year</option>
                            <option value="1">1st Semester</option>
                            <option value="2">2nd Semester</option>
                        </select>
                    </div>

                    <!-- File Upload -->
                    <div class="mb-3">
                        <label for="file" class="form-label">Upload Excel File</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".xlsx" required>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Import file</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this item?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteButton" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const deleteButtons = document.querySelectorAll('.delete-button');
        const deleteLink = document.getElementById('deleteButton');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                const userId = button.getAttribute('data-id');
                deleteLink.setAttribute('href', 'controller/delete-itl.php?id=' + userId);
            });
        });
    });
</script>

<?php include('./includes/footer.php'); ?>
