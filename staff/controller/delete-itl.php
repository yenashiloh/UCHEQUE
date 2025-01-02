<?php
require '../config/config.php';
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['auth_user'])) {
    header('Location: ../login.php');
    exit;
}

// Validate the userId parameter
if (isset($_GET['userId']) && ctype_digit($_GET['userId'])) {
    $userId = intval($_GET['userId']); // Ensure it's an integer

    // Query to select the file path
    $query = "SELECT filePath FROM itl_extracted_data WHERE userId = ?";
    $stmt = $con->prepare($query);

    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($filePath);

        if ($stmt->fetch()) {
            $fullFilePath = "../../uploads/" . $filePath;

            // Check if the file exists
            if (file_exists($fullFilePath)) {
                if (!unlink($fullFilePath)) {
                    $last_error = error_get_last();
                    $_SESSION['status'] = "Failed to delete the file. Error: " . $last_error['message'];
                    $_SESSION['status_code'] = "error";
                    header('Location: ../s_itl.php');
                    exit;
                }
            }
        } else {
            $_SESSION['status'] = "No file found for the given user ID.";
            $_SESSION['status_code'] = "error";
            header('Location: ../s_itl.php');
            exit;
        }
    } else {
        $_SESSION['status'] = "Error preparing the query.";
        $_SESSION['status_code'] = "error";
        header('Location: ../s_itl.php');
        exit;
    }

    // Delete the record from the database
    $deleteQuery = "DELETE FROM itl_extracted_data WHERE userId = ?";
    $deleteStmt = $con->prepare($deleteQuery);

    if ($deleteStmt) {
        $deleteStmt->bind_param('i', $userId);

        if ($deleteStmt->execute()) {
            $_SESSION['status'] = "Record and associated file deleted successfully!";
            $_SESSION['status_code'] = "success";
            header('Location: ../s_itl.php');
            exit;
        } else {
            $_SESSION['status'] = "Failed to delete the record.";
            $_SESSION['status_code'] = "error";
            header('Location: ../s_itl.php');
            exit;
        }
    } else {
        $_SESSION['status'] = "Error preparing the delete query.";
        $_SESSION['status_code'] = "error";
        header('Location: ../s_itl.php');
        exit;
    }
} else {
    $_SESSION['status'] = "Invalid user ID.";
    $_SESSION['status_code'] = "error";
    header('Location: ../s_itl.php');
    exit;
}

$con->close();
?>
