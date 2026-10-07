<?php
// include auth and db
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/activity_logger.php';
include '../includes/header.php';

mysqli_report(MYSQLI_REPORT_OFF);  // Cancels the fatal error screen

// get patient id from session
$patient_id = $_SESSION['user_id'];

// fetch current patient info and history (email and medications)
$patient_stmt = $conn->prepare("SELECT contact_email FROM PATIENT_INFO WHERE patient_id = ?");
$patient_stmt->bind_param("i", $patient_id);
$patient_stmt->execute();
$patient_result = $patient_stmt->get_result();
$patient = $patient_result->fetch_assoc();

$med_stmt = $conn->prepare("SELECT med_id, medication_name, dosage, start_date FROM PATIENT_MEDICATIONS WHERE patient_id = ?");
$med_stmt->bind_param("i", $patient_id);
$med_stmt->execute();
$med_result = $med_stmt->get_result();
$medications = $med_result->fetch_all(MYSQLI_ASSOC);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = "";

    if ($_POST['form'] === 'remove_medication') {
        $remove_id = (int)($_POST['remove_med_id']);
        $del_med = $conn->prepare("DELETE FROM PATIENT_MEDICATIONS WHERE med_id = ? AND patient_id = ?");
        $del_med->bind_param("ii", $remove_id, $patient_id);
        if ($del_med->execute()) {
            $success = "Medication removed successfully.";
            // log medication removal
            logRecordDelete($conn, $patient_id, 'patient', 'PATIENT_MEDICATIONS', $remove_id, "Patient removed medication ID: $remove_id");

            $_SESSION['success'] = "Medication removed successfully!";
        } 
        else {
            $error = "Failed to remove medication.";
        }
    }
    else if ($_POST['form'] === 'new_medication') {
        $new_medication = trim($_POST['medication_name'] ?? '');
        $new_dosage = trim($_POST['dosage'] ?? '');
        if (!empty($new_medication)) {
            $ins_med = $conn->prepare("INSERT INTO PATIENT_MEDICATIONS (patient_id, medication_name, dosage, start_date) VALUES (?, ?, ?, NOW())");
            $ins_med->bind_param("iss", $patient_id, $new_medication, $new_dosage);
            $ins_med->execute();

            // Log new medication
            $med_id = $conn->insert_id;
            logRecordCreate($conn, $patient_id, 'patient', 'PATIENT_MEDICATIONS', $med_id, "Patient added medication: $new_medication");

            $_SESSION['success'] = "New medication added successfully!";
        }
    }
    else if ($_POST['form'] === 'update_email') {
        $new_email = trim($_POST['contact_email'] ?? '');

        $sameEmail = $conn->prepare("SELECT contact_email FROM PATIENT_INFO WHERE contact_email = ?");
        $sameEmail->bind_param("s", $new_email);
        $sameEmail->execute();
        $sameEmail->store_result();

        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL) || $sameEmail->num_rows > 0) {
            $error = "invalid email address";
        } 
        else {
            // update patient email
            $upd_patient = $conn->prepare("UPDATE PATIENT_INFO SET contact_email = ? WHERE patient_id = ?");
            $upd_patient->bind_param("si", $new_email, $patient_id);
            $upd_patient->execute();

            // Log contact info change
            logRecordEdit($conn, $patient_id, 'patient', 'PATIENT_INFO', $patient_id, "Patient updated their contact email");

            $_SESSION['success'] = "Contact email updated successfully!";
        }
    }

    if (!empty($error)) {
        $_SESSION['error'] = $error;
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
    <title>Edit Information - HealthCare Portal</title>
    <link rel="stylesheet" href="../css/styles.css?v=5">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Edit Your Information</h1>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class='success-message'><?php echo htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); 
        endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']);
        endif; ?>

        <div class="dashboard-grid">
            <!-- Contact Information Card -->
            <div class="dashboard-card">
                <h3>Contact Information</h3>
                <form method="POST" id="edit-form">
                    <input type="hidden" name="form" value="update_email">
                    <div class="form-row">
                        <label for="email">Email Address:</label>
                        <input type="email" id="email" name="contact_email" value="<?php echo htmlspecialchars($patient['contact_email'] ?? ''); ?>" required>
                    </div>
                    <button type="submit">Update Contact Info</button>
                </form>
            </div>

            <!-- Current Medications Card -->
            <div class="dashboard-card">
                <h3>Current Medications</h3>
                <?php if (!empty($medications)): ?>
                    <div class="table-cards">
                        <?php foreach ($medications as $med): ?>
                            <div class="table-card">
                                <div class="table-card-header"><?php echo htmlspecialchars($med['medication_name']); ?></div>
                                <div class="table-card-row">
                                    <div class="table-card-label">Dosage:</div>
                                    <div class="table-card-value"><?php echo htmlspecialchars($med['dosage']); ?></div>
                                </div>
                                <div class="table-card-row">
                                    <div class="table-card-label">Started:</div>
                                    <div class="table-card-value"><?php echo htmlspecialchars($med['start_date']); ?></div>
                                </div>
                                <form method="POST" style="margin-top: 1rem;">
                                    <input type="hidden" name="form" value="remove_medication">
                                    <button type="submit" name="remove_med_id" value="<?php echo (int)$med['med_id']; ?>" 
                                            style="background: var(--error-color); color: white; padding: 0.5rem 1rem; font-size: 0.9rem;">
                                        Remove Medication
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No medications recorded</p>
                <?php endif; ?>
            </div>

            <!-- Add New Medication Card -->
            <div class="dashboard-card">
                <h3>Add New Medication</h3>
                <form method="POST">
                    <input type="hidden" name="form" value="new_medication">
                    <div class="form-row">
                        <label for="medication_name">Medication Name:</label>
                        <input type="text" id="medication_name" name="medication_name" placeholder="Enter medication name">
                    </div>
                    <div class="form-row">
                        <label for="dosage">Dosage:</label>
                        <input type="text" id="dosage" name="dosage" placeholder="e.g., 500mg daily">
                    </div>
                    <button type="submit">Add Medication</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
