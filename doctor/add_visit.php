<?php
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/activity_logger.php';
include '../includes/header.php';

// ensure doctor role
if ($_SESSION['role'] != 'doctor') {
    die('restricted access');
}

$doctor_id = $_SESSION['user_id'];
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$success = '';
$error = '';

// on 'submit' insert into HOSPITAL_VISITS/RETURNED_VISIT_DATA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Collect HOSPITAL_VISITS
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $visit_date = trim($_POST['visit_date'] ?? '');
    $visit_reason = trim($_POST['visit_reason'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    // 2. Collect RETURNED_VISIT_DATA
    $height = trim($_POST['height'] ?? '');
    $weight = trim($_POST['weight'] ?? '');
    $blood_pressure = trim($_POST['blood_pressure'] ?? '');
    $heart_rate = trim($_POST['heart_rate'] ?? '');
    $respiration_rate = trim($_POST['respiration_rate'] ?? '');
    $temperature = trim($_POST['temperature'] ?? '');
    $skin_health = trim($_POST['skin_health'] ?? '');
    $organ_health = trim($_POST['organ_health'] ?? '');
    $neurological_health = trim($_POST['neurological_health'] ?? '');
    $new_medicines = trim($_POST['new_medicines'] ?? '');
    $new_conditions = trim($_POST['new_conditions'] ?? '');
    $urgent_concern = trim($_POST['urgent_concern'] ?? '');
    $extra_notes = trim($_POST['extra_notes'] ?? '');
    $next_checkup_date = trim($_POST['next_checkup_date'] ?? '');

    // Validate required visit fields
    if (empty($patient_id) || empty($visit_date) || empty($visit_reason)) {
        $error = 'Please provide patient, date, and reason for visit.';
    } 
    else {
        // Insert into HOSPITAL_VISITS
        $stmt = $conn->prepare(" INSERT INTO HOSPITAL_VISITS
            (patient_id, doctor_id, visit_date, visit_reason, diagnosis) 
            VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss",
            $patient_id, $doctor_id, $visit_date, $visit_reason, $diagnosis);

        if ($stmt->execute()) {
            $visit_id = $conn->insert_id;

            logRecordCreate($conn, $doctor_id, 'doctor', 'HOSPITAL_VISITS', $visit_id,
                "Created new visit for patient ID: $patient_id - Reason: $visit_reason");

            // Insert into RETURNED_VISIT_DATA
            $stmt2 = $conn->prepare("INSERT INTO RETURNED_VISIT_DATA
                (visit_id, patient_id, doctor_id, visit_date, height, weight, blood_pressure, heart_rate,
                 respiration_rate, temperature, skin_health, organ_health, neurological_health,
                 new_medicines, new_conditions, urgent_concern, extra_notes, next_checkup_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt2->bind_param("iiissssissssssssss",
                $visit_id, $patient_id, $doctor_id, $visit_date,
                $height, $weight, $blood_pressure, $heart_rate, $respiration_rate, $temperature,
                $skin_health, $organ_health, $neurological_health,
                $new_medicines, $new_conditions, $urgent_concern,
                $extra_notes, $next_checkup_date);

            if ($stmt2->execute()) {
                logRecordCreate($conn, $doctor_id, 'doctor', 'returned_visit_data', $stmt2->insert_id,
                    "Added return visit data for visit ID: $visit_id");

                $_SESSION['success'] = "Visit and Return data saved successfully!";
                $del = $conn->prepare("DELETE FROM APPOINTMENTS WHERE doctor_id = ? AND patient_id = ? AND appointment_date = ?");
                $del->bind_param("iis", $doctor_id, $patient_id, $visit_date);
                $del->execute();
            } 
            else {
                $error = "Visit saved, but failed to save return visit data.";
            }

        } 
        else {
            $error = "Failed to save hospital visit.";
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?patient_id=$patient_id");
    exit();
}

// data that will be used to display patient info on doctor dashboard
$patient = null;
if ($patient_id) {
    $p = $conn->prepare("SELECT patient_id, first_name, last_name, date_of_birth FROM PATIENT_INFO WHERE patient_id = ?");
    $p->bind_param("i", $patient_id);
    $p->execute();
    $patient = $p->get_result()->fetch_assoc();
}
$records = null;
if ($patient_id) {
    $sql = $conn->prepare("SELECT conditions, allergies, family_history, surgeries, social_history, activity_level, 
                      serious_illnesses, serious_injuries, other_info, last_time_updated FROM PREEXISTING_MEDICAL_HISTORY WHERE patient_id = ?");
    $sql->bind_param("i", $patient_id);
    $sql->execute();
    $records = $sql->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Visit</title>
    <link rel="stylesheet" href="../css/styles.css?v=5">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Add New Visit</h1>
            <p>Record a patient visit and diagnosis</p>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class='success-message'><?php echo htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); 
        endif; ?>
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Patient Information Card -->
            <div class="dashboard-card">
                <?php if ($patient): ?>
                    <h3>Patient Information</h3>
                    <div class="table-card-row">
                        <div class="table-card-label">Name:</div>
                        <div class="table-card-value"><?php echo htmlspecialchars($patient['first_name'].' '.$patient['last_name']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <div class="table-card-label">Patient ID:</div>
                        <div class="table-card-value"><?php echo (int)$patient['patient_id']; ?></div>
                    </div>
                    <div class="table-card-row">
                        <div class="table-card-label">Date of Birth:</div>
                        <div class="table-card-value"><?php echo htmlspecialchars($patient['date_of_birth']); ?></div>
                    </div>
                <?php endif; ?>
            
                <?php if ($records): ?>
                    <div class="table-card-row">
                        <label class="table-card-label">Conditions</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($records['conditions']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <label class="table-card-label">Allergies</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($records['allergies']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <label class="table-card-label">Family History</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($records['family_history']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <label class="table-card-label">Surgeries</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($records['surgeries']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <label class="table-card-label">Social History</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($records['social_history']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <label class="table-card-label">Activity Level</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($records['activity_level']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <label class="table-card-label">Serious Illnesses</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($records['serious_illnesses']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <label class="table-card-label">Serious Injuries</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($records['serious_injuries']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <label class="table-card-label">Other</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($records['other_info']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <label class="table-card-label">Last Updated</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($records['last_time_updated']); ?></div>
                    </div>
                <?php else: ?>
                    <p>No History On File</p>
                <?php endif; ?>
            </div>

            <!-- Visit Form Card -->
            <div class="dashboard-card">
                <h3>Visit + Return Visit Data</h3>
                <form method="POST">

                    <input type="hidden" name="patient_id" value="<?php echo (int)$patient_id; ?>">

                    <!-- Hospital Visit Section -->
                    <h4>Visit Details</h4>

                    <div class="form-row">
                        <label for="visit_date">Visit Date:</label>
                        <input type="date" id="visit_date" name="visit_date" required>
                    </div>

                    <div class="form-row">
                        <label for="visit_reason">Reason for Visit:</label>
                        <input type="text" id="visit_reason" name="visit_reason" required>
                    </div>

                    <div class="form-row">
                        <label for="diagnosis">Diagnosis:</label>
                        <input type="text" id="diagnosis" name="diagnosis">
                    </div>

                    <!-- Return Visit Section -->
                    <h4>Return Visit Data</h4>

                    <div class="form-row">
                        <label>Height:</label>
                        <input type="text" name="height">
                    </div>

                    <div class="form-row">
                        <label>Weight:</label>
                        <input type="text" name="weight">
                    </div>

                    <div class="form-row">
                        <label>Blood Pressure:</label>
                        <input type="text" name="blood_pressure" placeholder="e.g., 120/80">
                    </div>

                    <div class="form-row">
                        <label>Heart Rate:</label>
                        <input type="text" name="heart_rate">
                    </div>

                    <div class="form-row">
                        <label>Respiration Rate:</label>
                        <input type="text" name="respiration_rate">
                    </div>

                    <div class="form-row">
                        <label>Temperature:</label>
                        <input type="text" name="temperature">
                    </div>

                    <div class="form-row">
                        <label>Skin Health:</label>
                        <input type="text" name="skin_health">
                    </div>

                    <div class="form-row">
                        <label>Organ Health:</label>
                        <input type="text" name="organ_health">
                    </div>

                    <div class="form-row">
                        <label>Neuro Health:</label>
                        <input type="text" name="neurological_health">
                    </div>

                    <div class="form-row">
                        <label>New Medicines:</label>
                        <input type="text" name="new_medicines">
                    </div>

                    <div class="form-row">
                        <label>New Conditions:</label>
                        <input type="text" name="new_conditions">
                    </div>

                    <div class="form-row">
                        <label>Urgent Concern:</label>
                        <input type="text" name="urgent_concern">
                    </div>

                    <div class="form-row">
                        <label>Next Checkup Date:</label>
                        <input type="date" name="next_checkup_date">
                    </div>

                    <div class="form-row">
                        <label>Extra Notes:</label>
                        <textarea name="extra_notes"></textarea>
                    </div>

                    <button type="submit">Save All Data</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
