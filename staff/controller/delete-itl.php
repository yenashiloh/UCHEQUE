<?php
require '../config/config.php';
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['auth_user'])) {
    header('Location: ../login.php');
    exit;
}

// Validate the id parameter
if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $id = intval($_GET['id']); // Ensure it's an integer
    
    // Query to select the file path
    $query = "SELECT filePath FROM itl_extracted_data WHERE id = ?";
    $stmt = $con->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($filePath);
        
        if ($stmt->fetch()) {
            $fullFilePath = "../../uploads/" . $filePath;
            
            // Check if the file exists and delete it
            if (file_exists($fullFilePath)) {
                if (!unlink($fullFilePath)) {
                    $last_error = error_get_last();
                    $_SESSION['status'] = "Failed to delete the file. Error: " . $last_error['message'];
                    $_SESSION['status_code'] = "error";
                    header('Location: ../s_itl.php');
                    exit;
                }
            }
            
            // Delete the record from the database
            $deleteQuery = "DELETE FROM itl_extracted_data WHERE id = ?";
            $deleteStmt = $con->prepare($deleteQuery);
            
            if ($deleteStmt) {
                $deleteStmt->bind_param('i', $id);
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
            $_SESSION['status'] = "No record found with the given ID.";
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
} else {
    $_SESSION['status'] = "Invalid ID provided.";
    $_SESSION['status_code'] = "error";
    header('Location: ../s_itl.php');
    exit;
}

$con->close();
?>