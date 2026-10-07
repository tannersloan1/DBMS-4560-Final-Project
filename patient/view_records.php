<?php
//include auth/session check and database connection
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/activity_logger.php';

//get patient's id from the session
$patient_id = $_SESSION['user_id'];

$allDoctors = $conn->query("SELECT doctor_id, first_name, last_name FROM DOCTOR_INFO");

// Get appointments and relavent info
$getAppointments = $conn->prepare("SELECT A.patient_id, A.doctor_id, A.appointment_date, A.appointment_time, D.first_name, D.last_name FROM APPOINTMENTS A 
                                  JOIN DOCTOR_INFO D ON A.doctor_id = D.doctor_id WHERE A.patient_id = ? AND A.appointment_date >= CURDATE()
                                  ORDER BY A.appointment_date ASC LIMIT 3");
$getAppointments->bind_param("i", $patient_id);
$getAppointments->execute();

$threeAppointments = $getAppointments->get_result()->fetch_all(MYSQLI_ASSOC);
//log patient viewing their records
logUserAction($conn, $patient_id, 'patient', 'RECORD_VIEW', 'Patient viewed their medical records');

//retrieve basic patient info
$sql = "SELECT * FROM PATIENT_INFO WHERE patient_id='$patient_id'";
$result = $conn->query($sql);
$patient = $result->fetch_assoc();

//retrieve patient's medical history
$sql_history = "SELECT * FROM PREEXISTING_MEDICAL_HISTORY WHERE patient_id='$patient_id'";
$history_result = $conn->query($sql_history);
$history = $history_result->fetch_assoc();

// retrieve patient's medications from medications table
$med_stmt = $conn->prepare("SELECT medication_name, dosage, start_date FROM PATIENT_MEDICATIONS WHERE patient_id = ?");
$med_stmt->bind_param("i", $patient_id);
$med_stmt->execute();
$medications_result = $med_stmt->get_result();
$medications = $medications_result->fetch_all(MYSQLI_ASSOC);

// Save appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = trim($_POST['date']);
    $doctor_id = (int)($_POST['doctor_for_appointment']);
    $time = trim($_POST['time']);

    if (!empty($date) && !empty($doctor_id) && !empty($time)) {
        $add = $conn->prepare("INSERT INTO APPOINTMENTS (patient_id, doctor_id, appointment_date, appointment_time)
                              VALUES (?, ?, ?, ?)");
        $add->bind_param("iiss", $patient_id, $doctor_id, $date, $time);
        if ($add->execute()) {
            $_SESSION['success'] = "Appointment saved successfully!";
            logUserAction($conn, $patient_id, 'patient', 'APPOINTMENT_CREATE', 'Patient created an appointment');
        }
        else {
            $error = "Failed";
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Health Records</title>
    <link rel="stylesheet" href="../css/styles.css?v=5">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Welcome back, <?php echo htmlspecialchars($patient['first_name']); ?>!</h1>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class='success-message'><?php echo htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); 
        endif; ?>
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Personal Information Card -->
            <div class="dashboard-card">
                <h3>Personal Information</h3>
                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($patient['first_name']." ".$patient['last_name']); ?></p>
                <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($patient['date_of_birth']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($patient['contact_email']); ?></p>
                <form action="edit_record.php" method="get" style="margin-top: 1rem;">
                    <button type="submit">Update Information</button>
                </form>
            </div>

            <!-- Medical History Card -->
            <div class="dashboard-card">
                <h3>Medical History</h3>
                <?php if($history): ?>
                    <p><strong>Conditions:</strong> 
                        <?php echo !empty($history['conditions']) ? htmlspecialchars($history['conditions']) : "No conditions recorded"; ?>
                    </p>
                    <p><strong>Allergies:</strong> 
                        <?php echo !empty($history['allergies']) ? htmlspecialchars($history['allergies']) : "No allergies recorded"; ?>
                    </p>
                    <p><strong>Last Updated:</strong> 
                        <?php echo !empty($history['last_time_updated']) ? htmlspecialchars($history['last_time_updated']) : "Not updated yet"; ?>
                    </p>
                <?php else: ?>
                    <p>No medical history available. Please update your information.</p>
                <?php endif; ?>
                <a href="update_medical_history.php">
                <button>Update History</button>
                </a>
            </div>

            <!-- medications card -->
            <div class="dashboard-card">
                <h3>Current Medications</h3>
                <?php if (!empty($medications)): ?>
                    <ul class="med-list">
                        <?php foreach ($medications as $med): ?>
                            <li class="med-list-item">
                                <span class="med-list-item-title"><?php echo htmlspecialchars($med['medication_name']); ?></span>
                                <span class="med-list-item-meta"><?php echo htmlspecialchars($med['dosage']); ?></span>
                                <span class="med-list-item-meta">started: <?php echo htmlspecialchars($med['start_date']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No medications or prescriptions recorded</p>
                <?php endif; ?>
            </div>

            <!-- Actions Card -->
            <div class="dashboard-card">
                <h3>Quick Actions</h3>
                <p>Manage your health data and records</p>
                <a href="download_data.php" target="_blank" style="margin: 1rem 0;">
                    <button>Download My Data</button>
                </a>
                <a href="../logout.php" style="display: inline-block; margin-top: 1rem; color: var(--text-secondary); text-decoration: none;">Sign Out</a>
            </div>

            <!-- Make Appointment Card -->
            <div class="dashboard-card">
                <h3>Make Appointment</h3>
                <form method="POST">
                    <div class="form-row">
                        <label for="date">Date:</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                    <div class="form-row">
                        <label for="time">Time:</label>
                        <input type="time" id="time" name="time" required>
                    </div>
                    <div class="form-row">
                        <label for="doctor_for_appointment">Doctor:</label>
                        <select name="doctor_for_appointment" required>
                        <option value="">Doctor</option>
                        <?php
                        // Fetch each doctors in the database and save them as options
                        if ($allDoctors->num_rows > 0) {
                            while ($row = $allDoctors->fetch_assoc()) {
                            echo '<option value="' . (int)$row['doctor_id'] . '">' . $row["first_name"] . ' ' . $row["last_name"] . '</option>';
                            }
                        }
                        else {
                            $error = "No valid doctors in database";
                        }
                        ?>
                        </select>
                    </div>
                    <button type="submit">Set Appointment</button>
                </form>
            </div>

            <!-- See Upcoming Appointment(s) Card -->
            <div class="dashboard-card">
                <h3>Upcoming Appointments</h3>
                <?php if (!empty($threeAppointments)): ?>
                    <!-- I know this uses med-list css but it looks good with it since appointments only need to show 3 items too -->
                    <ul class="med-list">
                        <?php foreach ($threeAppointments as $apps): ?>
                            <li class="med-row-item">
                                <span class="med-list-item-title">Doctor: <?php echo htmlspecialchars($apps['first_name'] . ' ' . $apps['last_name']); ?></span>
                                <span class="med-list-item-meta">Date: <?php echo htmlspecialchars($apps['appointment_date']); ?></span>
                                <span class="med-list-item-meta">Time: <?php echo htmlspecialchars($apps['appointment_time']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No Appointments Scheduled</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
