<?php

//start the session
session_start();

//check if the user is logged in by verifying that a session with user_id exists
if (!isset($_SESSION['user_id'])) {
    //redirect to landing page if there's isn't a session with the user_id
    header("Location: index.php");
    exit(); //exiting after redirect
}

//if a patient is logged in,
if ($_SESSION['role'] == "patient") {
    //redirect to patient view and exit after
    header("Location: patient/view_records.php");
    exit();
//if a doctor is logged in
} else if ($_SESSION['role'] == "doctor") {
    //redirect to doctor view and exit after
    header("Location: doctor/view_patient.php");
    exit();
//if admin is logged in, redirect to admin dashboard
} else if ($_SESSION['role'] == "admin") {
    header("Location: admin/dashboard.php");
    exit();
}
?>
