<?php
//start the session
session_start();

//include database connection and activity logger
include 'includes/db.php';
include 'includes/activity_logger.php';

//log logout activity if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    logLogout($conn, $_SESSION['user_id'], $_SESSION['role']);
}

//destroy the session
session_destroy();

//redirect to landing page
header("Location: index.php");

//terminates script
exit();
?>
