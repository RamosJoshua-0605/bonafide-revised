<?php
// Check if login_id is set
if (!isset($_SESSION['login_id'])) {
    header("Location: index.php"); // Redirect to login page
    exit();
}

// Check if user_id is set
if (!isset($_SESSION['user_id'])) {
    header("Location: profile.php"); // Redirect to profile page
    exit();
}
?>