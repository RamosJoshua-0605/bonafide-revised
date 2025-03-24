<?php
// Check if login_id is set
if (!isset($_SESSION['login_id'])) {
    header("Location: index.php"); // Redirect to login page
    exit();
}
?>