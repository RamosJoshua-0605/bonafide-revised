<?php
// Start the session
session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session entirely
session_destroy();

// Redirect to the login page
header("Location: index.php"); 
exit();
