<?php
//start session
session_start();

//include the database connection
include 'includes/db.php';
include 'includes/activity_logger.php';

//error messages stored here
$error = "";

//checks if login was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //getting email and password entered by the user
    $email = $_POST['email'];
    $password = $_POST['password'];

    //querying if the email exists in the patient table
    $sql_patient = "SELECT patient_id, password_hash, is_suspended FROM PATIENT_USERS WHERE login_email='$email'";
    $result_patient = $conn->query($sql_patient);

    //querying if the email exists in the doctor table
    $sql_doctor = "SELECT doctor_id, password_hash, is_suspended FROM DOCTOR_USERS WHERE login_email='$email'";
    $result_doctor = $conn->query($sql_doctor);

    //querying if the email exists in the admin table
    $sql_admin = "SELECT admin_id, password_hash FROM ADMIN_USERS WHERE email='$email' AND is_active=1";
    $result_admin = $conn->query($sql_admin);

    //if user is in the patient table
    if ($result_patient->num_rows > 0) {
        $row = $result_patient->fetch_assoc();
	    $patientHash = $row['password_hash'];
	
	    //verify password
	    if (password_verify($password, $patientHash)) {
            //check if account is suspended
            if ($row['is_suspended']) {
                $error = "Your account has been suspended. Please contact an administrator.";
            } else {
                //storing patient id and role in session
                $_SESSION['user_id'] = $row['patient_id'];
                $_SESSION['role'] = "patient";

                //log the login activity
                logLogin($conn, $row['patient_id'], 'patient');

                //redirecting to user's dash
                header("Location: dashboard.php");
                exit();
            }
        }
    } 
    //if user is in the doctor table
    elseif ($result_doctor->num_rows > 0) {
        $row = $result_doctor->fetch_assoc();
	    $doctorHash = $row['password_hash'];

	    //verify password
	    if (password_verify($password, $doctorHash)) {
            //check if account is suspended
            if ($row['is_suspended']) {
                $error = "Your account has been suspended. Please contact an administrator.";
            } else {
                //storing doctor id and role in session
                $_SESSION['user_id'] = $row['doctor_id'];
                $_SESSION['role'] = "doctor";

                //log the login activity
                logLogin($conn, $row['doctor_id'], 'doctor');

                //redirecting to the user's dash
                header("Location: dashboard.php");
                exit();
            }
        }
    } 
    //if user is in the admin table
    elseif ($result_admin->num_rows > 0) {
        $row = $result_admin->fetch_assoc();
	    $adminHash = $row['password_hash'];

	    //verify password
	    if (password_verify($password, $adminHash)) {
            //storing admin id and role in session
            $_SESSION['user_id'] = $row['admin_id'];
            $_SESSION['role'] = "admin";

            //update last login
            $update_login = "UPDATE ADMIN_USERS SET last_login = NOW() WHERE admin_id = " . $row['admin_id'];
            $conn->query($update_login);

            //log the login activity
            logLogin($conn, $row['admin_id'], 'admin');

            //redirecting to admin dashboard
            header("Location: admin/dashboard.php");
            exit();
        }
    } 
    //if no match was found in patient, doctor, OR admin and no other error was set
    if (empty($error)) {
        $error = "Invalid login";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcare Portal</title>
    <link rel="stylesheet" href="css/styles.css?v=5">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <!-- login form container -->
    <div class="login-container">
        <h1>Health Records Portal</h1>
        <p>Secure access to your medical records</p>
        
        <!-- login form -->
        <form method="POST" class="login-form">
            <div class="form-row">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
            </div>
            <div class="form-row">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>
            <button type="submit">Sign In</button>
        </form>

        <!-- register link -->
        <p>Don't have an account?</p>
        <a href="register.php">
            <button>Create Account</button>
        </a>

        <!-- displaying error if login fails -->
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
    </div>
</body>
</html>

