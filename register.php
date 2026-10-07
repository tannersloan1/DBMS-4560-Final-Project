<?php
//start session
session_start();

//include the database connection
include 'includes/db.php';

//error messages stored here
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect user inputted data
    $email = $_POST['email'];
    $password = $_POST['password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    	// Collect additional data for the patient
	$date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $phone_number = $_POST['phone_number'];
        $contact_email = $_POST['contact_email'];
        $address = $_POST['address'];
        $emergency_contact_name = $_POST['emergency_contact_name'];
        $emergency_contact_number = $_POST['emergency_contact_number'];
        $marital_status = $_POST['marital_status'];
        $ethnicity = $_POST['ethnicity'];
        if (!empty($email) && !empty($password) && !empty($first_name) && !empty($last_name)) {
            $stmt1 = $conn->prepare("INSERT INTO patient_info (
                    first_name, last_name, date_of_birth, gender, phone_number,
                    contact_email, address, emergency_contact_name, emergency_contact_number,
                    marital_status, ethnicity
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
	    $stmt1->bind_param("sssssssssss",
            $first_name, $last_name, $date_of_birth, $gender, $phone_number,
            $contact_email, $address, $emergency_contact_name, $emergency_contact_number,
            $marital_status, $ethnicity);
	    $stmt1->execute();

	    //get recently auto incremented patient_id
	    $patient_id = $conn->insert_id;

	    //hash the password
	    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

	    //insert into users table
	    $stmt2 = $conn->prepare("INSERT INTO PATIENT_USERS (patient_id, login_email, password_hash) VALUES (?, ?, ?)");
	    $stmt2->bind_param("iss", $patient_id, $email, $passwordHash);

	    if ($stmt2->execute()) {
	        header("Location: index.php");
	        exit();
	    }
            else {
	        $error = "Invalid login";
	    }
        }
        else {
            $error = "Please fill in all the fields.";
        }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - HealthCare Portal</title>
    <link rel="stylesheet" href="css/styles.css?v=5">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <h1>Create Account</h1>
        <p>Join our secure healthcare platform</p>

        <!-- Registration Form -->
        <form method="POST" class="login-form">
            <!-- Patient Data -->
            <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
		<div>
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div>
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
		<div>
                    <label for="email">Login Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div>
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth">
                </div>
                <div>
                    <label for="gender">Gender</label>
                    <input type="text" id="gender" name="gender">
                </div>
                <div>
                    <label for="phone_number">Phone Number</label>
                    <input type="text" id="phone_number" name="phone_number">
                </div>
                <div>
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address">
                </div>
                <div>
                    <label for="marital_status">Marital Status</label>
                    <select id="marital_status" name="marital_status">
                        <option value="">Select</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Divorced">Divorced</option>
                        <option value="Widowed">Widowed</option>
                        <option value="Seperated">Separated</option>
                    </select>
                </div>
		<div>
                    <label for="emergency_contact_name">Emergency Contact Name</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name">
                </div>
                <div>
                    <label for="emergency_contact_number">Emergency Contact Number</label>
                    <input type="text" id="emergency_contact_number" name="emergency_contact_number">
                </div>
                <div>
                    <label for="ethnicity">Ethnicity</label>
                    <input type="text" id="ethnicity" name="ethnicity">
                </div>
                <div>
                    <label for="contact_email">Contact Email</label>
                    <input type="email" id="contact_email" name="contact_email">
                </div>
            </div>

            <button type="submit">Create Account</button>
        </form>

        <!-- back to login page -->
        <p>Already have an account?</p>
        <a href="login.php">
            <button>Sign In</button>
        </a>

        <!-- display error -->
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
    </div>
</body>
</html>

