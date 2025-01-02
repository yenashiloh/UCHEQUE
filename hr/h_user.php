<?php
// Include necessary files
include('./includes/authentication.php');
include('./includes/header.php');
include('./includes/sidebar.php');
include('./includes/topbar.php');

// Check if there is a success or error message from the redirection
if (isset($_GET['message'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_GET['message']) . '</div>';
} elseif (isset($_GET['error'])) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['error']) . '</div>';
}
?>

<div class="tabular--wrapper">
    <div class="add">
        <div class="filter">
            <form method="GET" action="">
                <!-- Search User Field -->
                <input type="text" name="search_user" placeholder="Search user..." value="<?php echo isset($_GET['search_user']) ? htmlspecialchars($_GET['search_user']) : ''; ?>" style="width: 200px; margin-right: 10px;">
                
                <!-- Select Role Dropdown -->
                <select name="role_filter" onchange="this.form.submit()" style="height: 43px; margin-right: 10px; width: 150px;">
                    <option value="" disabled selected>Select Role</option>
                    <option value="ALL" <?php if (isset($_GET['role_filter']) && $_GET['role_filter'] == 'ALL') echo 'selected'; ?>>All</option>
                    <option value="4" <?php if (isset($_GET['role_filter']) && $_GET['role_filter'] == '4') echo 'selected'; ?>>Staff</option>
                    <option value="2" <?php if (isset($_GET['role_filter']) && $_GET['role_filter'] == '2') echo 'selected'; ?>>Faculty</option>
                    <option value="3" <?php if (isset($_GET['role_filter']) && $_GET['role_filter'] == '3') echo 'selected'; ?>>HR</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Table for Users -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>

                    <th>Name
                        <a href="?sort=name&order=asc" class="sort-arrow <?php echo $sort === 'name' && $order === 'asc' ? 'active' : ''; ?>">▲</a>
                        <a href="?sort=name&order=desc" class="sort-arrow <?php echo $sort === 'name' && $order === 'desc' ? 'active' : ''; ?>">▼</a>
                    </th>

                    <th>Email</th>
                    <th>Contact</th>
                    <th>Role</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php
                $userId = $_SESSION['auth_user']['userId'];
                $limit = 10;
                $roleFilter = isset($_GET['role_filter']) ? $_GET['role_filter'] : null;
                $searchTerm = isset($_GET['search_user']) ? "%" . $con->real_escape_string($_GET['search_user']) . "%" : "";

                // Build role condition
                $roleCondition = "";
                if ($roleFilter && $roleFilter != 'ALL') {
                    $roleCondition = "AND employee.userId IN (
                        SELECT userId 
                        FROM employee_role 
                        WHERE role_id = $roleFilter
                    )";
                } else {
                    $roleCondition = "AND (employee.userId NOT IN (
                        SELECT userId 
                        FROM employee_role 
                        WHERE role_id = 1
                    ) OR employee.userId NOT IN (
                        SELECT userId 
                        FROM employee_role
                    ))";
                }

                // Build search condition
                $searchCondition = $searchTerm ? "AND (employee.firstName LIKE '$searchTerm' OR employee.middleName LIKE '$searchTerm' OR employee.lastName LIKE '$searchTerm' OR employee.emailAddress LIKE '$searchTerm')" : "";

                // Get total rows count
                $totalResult = $con->query("SELECT COUNT(DISTINCT employee.userId) AS total
                    FROM employee
                    LEFT JOIN employee_role ON employee.userId = employee_role.userId
                    WHERE 1 $roleCondition $searchCondition");

                if (!$totalResult) {
                    die("Error fetching total count: " . $con->error);
                }

                $totalRows = $totalResult->fetch_assoc()['total'];
                $totalPages = ceil($totalRows / $limit);

                // Pagination logic
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $page = max($page, 1);
                $offset = ($page - 1) * $limit;

                $sort = isset($_GET['sort']) && in_array($_GET['sort'], ['name', 'totalOverload']) ? $_GET['sort'] : 'name';
                $order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC';

                $sortColumn = $sort === 'name' ? "CONCAT(employee.firstName, ' ', employee.lastName)" : 'totalOverload';

                // Get filtered user data
                $sql = "
                    SELECT 
                        employee.userId, 
                        employee.employeeId, 
                        employee.firstName, 
                        employee.middleName, 
                        employee.lastName, 
                        employee.phoneNumber, 
                        employee.emailAddress, 
                        GROUP_CONCAT(employee_role.role_id) AS roles, 
                        employee.status
                    FROM 
                        employee
                    LEFT JOIN 
                        employee_role ON employee.userId = employee_role.userId
                    WHERE 
                        1 $roleCondition $searchCondition AND employee.userId != $userId
                    GROUP BY 
                        employee.userId 
                    ORDER BY 
                        $sortColumn $order
                    LIMIT $limit OFFSET $offset
                ";
                $result = $con->query($sql);

                if (!$result) {
                    die("Error executing query: " . $con->error);
                }

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Get role names based on role_id
                        $roleNames = [];
                        $roles = explode(',', $row['roles']);
                        foreach ($roles as $role) {
                            switch ($role) {
                                case '2':
                                    $roleNames[] = 'Faculty';
                                    break;
                                case '3':
                                    $roleNames[] = 'HR';
                                    break;
                                case '4':
                                    $roleNames[] = 'Staff';
                                    break;
                                default:
                                    $roleNames[] = 'No Assigned Role';
                                    break;
                            }
                        }
                        $roleList = implode(', ', $roleNames);

                        // Display user information
                        $fullName = trim($row['firstName'] . ' ' . $row['middleName'] . ' ' . $row['lastName']);
                        echo '<tr>
                                <td>' . htmlspecialchars($row['userId']) . '</td>
                                <td>' . htmlspecialchars($fullName) . '</td>
                                <td>' . htmlspecialchars($row['emailAddress']) . '</td>
                                <td>' . htmlspecialchars($row['phoneNumber']) . '</td>
                                <td>' . htmlspecialchars($roleList) . '</td>
                                <td>' . htmlspecialchars($row['status']) . '</td>
                                
                            </tr>';
                    }
                } else {
                    echo '<tr><td colspan="7">No users found.</td></tr>';
                }
                ?>

            </tbody>
        </table>

        <!-- Pagination -->
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

<?php include('./includes/footer.php'); ?>
