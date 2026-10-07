<?php
// start a session
session_start();

//makes sure user is logged in, else redirects to landing page and exits
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}