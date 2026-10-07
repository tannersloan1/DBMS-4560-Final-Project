<?php
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/activity_logger.php';

// ensure doctor role
if ($_SESSION['role'] != 'doctor') {
    die('restricted access');
}

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
if (!$patient_id) {
    die('no patient selected');
}

//log report generation
$doctor_id = $_SESSION['user_id'];
logUserAction($conn, $doctor_id, 'doctor', 'REPORT_GENERATE', "Doctor generated report for patient ID: $patient_id");

// basic patient info
$pstmt = $conn->prepare("SELECT * FROM PATIENT_INFO WHERE patient_id = ?");
$pstmt->bind_param("i", $patient_id);
$pstmt->execute();
$patient = $pstmt->get_result()->fetch_assoc();

// preexisting history
$hstmt = $conn->prepare("SELECT * FROM PREEXISTING_MEDICAL_HISTORY WHERE patient_id = ?");
$hstmt->bind_param("i", $patient_id);
$hstmt->execute();
$history = $hstmt->get_result()->fetch_assoc();

// visits summary
$vstmt = $conn->prepare("SELECT * FROM HOSPITAL_VISITS WHERE patient_id = ? ORDER BY visit_date DESC");
$vstmt->bind_param("i", $patient_id);
$vstmt->execute();
$visits = $vstmt->get_result();

// returned visit data
$rstmt = $conn->prepare("SELECT * FROM RETURNED_VISIT_DATA WHERE patient_id = ?");
$rstmt->bind_param("i", $patient_id);
$rstmt->execute();
$rvd_res = $rstmt->get_result();
$rvd_by_visit = [];
while ($row = $rvd_res->fetch_assoc()) {
    $rvd_by_visit[$row['visit_id']] = $row;
}

// fetch medications
$mstmt = $conn->prepare("SELECT medication_name, dosage FROM PATIENT_MEDICATIONS WHERE patient_id = ?");
$mstmt->bind_param("i", $patient_id);
$mstmt->execute();
$medications = $mstmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Report</title>
    <link rel="stylesheet" href="../css/styles.css?v=5">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Patient Medical Report</h1>
        </div>

        <?php if (!$patient): ?>
            <div class="dashboard-card">
                <h3>Patient Not Found</h3>
                <p>The requested patient record could not be found.</p>
            </div>
        <?php else: ?>
            <div class="dashboard-grid">
                <!-- Personal Information Card -->
                <div class="dashboard-card">
                    <h3>Personal Information</h3>
                    <div class="table-card-row">
                        <div class="table-card-label">Name:</div>
                        <div class="table-card-value"><?php echo htmlspecialchars($patient['first_name'].' '.$patient['last_name']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <div class="table-card-label">Date of Birth:</div>
                        <div class="table-card-value"><?php echo htmlspecialchars($patient['date_of_birth']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <div class="table-card-label">Email:</div>
                        <div class="table-card-value"><?php echo htmlspecialchars($patient['contact_email'] ?? 'Not provided'); ?></div>
                    </div>
                    <div class="table-card-row">
                        <div class="table-card-label">Phone:</div>
                        <div class="table-card-value"><?php echo htmlspecialchars($patient['phone_number'] ?? 'Not provided'); ?></div>
                    </div>
                    <div class="table-card-row">
                        <div class="table-card-label">Address:</div>
                        <div class="table-card-value"><?php echo htmlspecialchars($patient['address'] ?? 'Not provided'); ?></div>
                    </div>
                </div>

                <!-- Medical History Card -->
                <div class="dashboard-card">
                    <h3>Medical History</h3>
                    <?php if ($history): ?>
                        <div class="table-card-row">
                            <div class="table-card-label">Conditions:</div>
                            <div class="table-card-value"><?php echo htmlspecialchars($history['conditions'] ?? 'None recorded'); ?></div>
                        </div>
                        <div class="table-card-row">
                            <div class="table-card-label">Allergies:</div>
                            <div class="table-card-value"><?php echo htmlspecialchars($history['allergies'] ?? 'None recorded'); ?></div>
                        </div>
                        <div class="table-card-row">
                            <div class="table-card-label">Surgeries:</div>
                            <div class="table-card-value"><?php echo htmlspecialchars($history['surgeries'] ?? 'None recorded'); ?></div>
                        </div>
                        <div class="table-card-row">
                            <div class="table-card-label">Family History:</div>
                            <div class="table-card-value"><?php echo htmlspecialchars($history['family_history'] ?? 'None recorded'); ?></div>
                        </div>
                        <div class="table-card-row">
                            <div class="table-card-label">Social History:</div>
                            <div class="table-card-value"><?php echo htmlspecialchars($history['social_history'] ?? 'None recorded'); ?></div>
                        </div>
                        <div class="table-card-row">
                            <div class="table-card-label">Activity Level:</div>
                            <div class="table-card-value"><?php echo htmlspecialchars($history['activity_level'] ?? 'Not specified'); ?></div>
                        </div>
                        <div class="table-card-row">
                            <div class="table-card-label">Last Updated:</div>
                            <div class="table-card-value"><?php echo htmlspecialchars($history['last_time_updated'] ?? 'Never'); ?></div>
                        </div>
                    <?php else: ?>
                        <p>No medical history recorded</p>
                    <?php endif; ?>
                </div>

                <!-- Current Medications Card -->
                <div class="dashboard-card">
                    <h3>Current Medications</h3>
                    <?php if ($medications->num_rows > 0): ?>
                        <div class="table-cards">
                            <?php while ($med = $medications->fetch_assoc()): ?>
                                <div class="table-card">
                                    <div class="table-card-header"><?php echo htmlspecialchars($med['medication_name']); ?></div>
                                    <div class="table-card-row">
                                        <div class="table-card-label">Dosage:</div>
                                        <div class="table-card-value"><?php echo htmlspecialchars($med['dosage']); ?></div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p>No medications recorded</p>
                    <?php endif; ?>
                </div>

                <!-- Visit History Card -->
                <div class="dashboard-card" style="grid-column: 1 / -1;">
                    <h3>Visit History</h3>
                    <?php if ($visits->num_rows === 0): ?>
                        <p>No visits recorded</p>
                    <?php else: ?>
                        <div class="table-cards">
                            <?php while ($v = $visits->fetch_assoc()): 
                                $rvd = $rvd_by_visit[$v['visit_id']] ?? null; ?>
                                <div class="table-card">
                                    <div class="table-card-header">
                                        Visit on <?php echo htmlspecialchars($v['visit_date']); ?>
                                    </div>
                                    
                                    <div class="table-card-row">
                                        <div class="table-card-label">Reason:</div>
                                        <div class="table-card-value"><?php echo htmlspecialchars($v['visit_reason'] ?? 'Not specified'); ?></div>
                                    </div>
                                    
                                    <?php if (!empty($v['diagnosis'])): ?>
                                    <div class="table-card-row">
                                        <div class="table-card-label">Diagnosis:</div>
                                        <div class="table-card-value"><?php echo htmlspecialchars($v['diagnosis']); ?></div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($rvd): ?>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1rem; margin: 1rem 0; padding: 1rem; background: rgba(37, 99, 235, 0.05); border-radius: 8px;">
                                        <div>
                                            <div class="table-card-label">Blood Pressure</div>
                                            <div class="table-card-value" style="font-weight: 600;"><?php echo htmlspecialchars($rvd['blood_pressure'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div>
                                            <div class="table-card-label">Heart Rate</div>
                                            <div class="table-card-value" style="font-weight: 600;"><?php echo htmlspecialchars($rvd['heart_rate'] ?? 'N/A'); ?> bpm</div>
                                        </div>
                                        <div>
                                            <div class="table-card-label">Temperature</div>
                                            <div class="table-card-value" style="font-weight: 600;"><?php echo htmlspecialchars($rvd['temperature'] ?? 'N/A'); ?>°C</div>
                                        </div>
                                        <div>
                                            <div class="table-card-label">Respiration</div>
                                            <div class="table-card-value" style="font-weight: 600;"><?php echo htmlspecialchars($rvd['respiration_rate'] ?? 'N/A'); ?> rpm</div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($rvd['extra_notes'])): ?>
                                    <div class="table-card-row">
                                        <div class="table-card-label">Notes:</div>
                                        <div class="table-card-value"><?php echo htmlspecialchars($rvd['extra_notes']); ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="dashboard-card" style="text-align: center; margin-top: 2rem;">
            <a href="view_patient.php">
                <button style="background: var(--secondary-color);">Back to Dashboard</button>
            </a>
        </div>
    </div>
</body>
</html>

