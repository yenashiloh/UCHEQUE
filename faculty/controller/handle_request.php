<?php
session_start();
require '../../vendor/autoload.php';
require '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['auth_user'])) {
    echo "Error: User not logged in.";
    exit;
}

$userId = $_SESSION['auth_user']['userId']; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check if the 'requestType' is set and valid
    if (!isset($_POST['requesType']) || empty($_POST['requesType'])) {
        echo "Error: Request type not selected.";
        exit;
    }

    $requestType = $_POST['requesType']; // Either 'cto' or 'overload'

    // Handle 'startMonth' and 'endMonth' for date range
    $startMonth = $con->real_escape_string($_POST['startMonth']);
    $endMonth = $con->real_escape_string($_POST['endMonth']);

    // Define month order to validate and check the month range
    $monthOrder = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];

    // Get the index of start and end months
    $startIndex = array_search($startMonth, $monthOrder);
    $endIndex = array_search($endMonth, $monthOrder);

    // Validate if months are correct and in proper order
    if ($startIndex === false || $endIndex === false || $startIndex > $endIndex) {
        echo "Error: Invalid month range.";
        exit;
    }

    // Insert the request into the database
    $query = "INSERT INTO request (userId, requestType, startMonth, endMonth, status) 
              VALUES ('$userId', '$requestType', '$startMonth', '$endMonth', 'Pending')";

    if ($con->query($query)) {
        $_SESSION['status'] = "Request submitted successfully!";
        $_SESSION['status_code'] = "success";
        header("Location: ../f_request.php"); // Redirect after successful insertion
        exit;
    } else {
        echo "Error: " . $con->error;
    }
}
?>
