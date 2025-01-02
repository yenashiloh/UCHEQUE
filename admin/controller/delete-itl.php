<?php
    require '../config/config.php';
session_start();

if (isset($_GET['userId']) && !empty($_GET['userId'])) {
    $id = $_GET['userId'];

    $sql = "DELETE FROM itl_extracted_data WHERE userId = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        header("Location: ../itl.php?deleted=true");
        exit();
    } else {
        header("Location: ../itl.php?deleted=false");
        exit();
    }
} else {    
    header("Location: ../itl.php?deleted=false");
    exit();
}

?>