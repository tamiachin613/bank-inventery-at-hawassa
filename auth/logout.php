<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Clear any cookies if they exist
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Redirect to login page
header('Location: ../index.php');
exit();
?>