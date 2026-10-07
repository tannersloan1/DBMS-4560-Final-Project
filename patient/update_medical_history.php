<?php
//include auth/session check and database connection
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/activity_logger.php';
include '../includes/header.php';

$patient_id = $_SESSION['user_id'];

// Grab the user's history on file (if any)
$sql = $conn->prepare("SELECT conditions, allergies, family_history, surgeries, social_history, activity_level, 
                      serious_illnesses, serious_injuries, other_info, last_time_updated FROM PREEXISTING_MEDICAL_HISTORY WHERE patient_id = ?");
$sql->bind_param("i", $patient_id);
$sql->execute();
$result = $sql->get_result()->fetch_assoc();

// If a file exists and the user inputted a different value than the current one on file, then update just that value
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $result) {
    $conditions = trim($_POST['conditions'] ?? '');
    $allergies = trim($_POST['allergies'] ?? '');
    $family_history = trim($_POST['family_history'] ?? '');
    $surgeries = trim($_POST['surgeries'] ?? '');
    $social_history = trim($_POST['social_history'] ?? '');
    $activity_level = trim($_POST['activity_level'] ?? '');
    $serious_illnesses = trim($_POST['serious_illnesses'] ?? '');
    $serious_injuries = trim($_POST['serious_injuries'] ?? '');
    $other_info = trim($_POST['other_info'] ?? '');

    // There is surely a way to optimize this instead of having individual ifs and updates, so may come back if we have extra time and look into this
    if ($conditions != $result['conditions'] && $conditions != '') {
        $sql = $conn->prepare("UPDATE PREEXISTING_MEDICAL_HISTORY SET conditions = ?, last_time_updated = NOW() WHERE patient_id = ?");
        $sql->bind_param("si", $conditions, $patient_id);
        $sql->execute();
    }
    if ($allergies != $result['allergies'] && $allergies != '') {
        $sql = $conn->prepare("UPDATE PREEXISTING_MEDICAL_HISTORY SET allergies = ?, last_time_updated = NOW() WHERE patient_id = ?");
        $sql->bind_param("si", $allergies, $patient_id);
        $sql->execute();
    }
    if ($family_history != $result['family_history'] && $family_history != '') {
        $sql = $conn->prepare("UPDATE PREEXISTING_MEDICAL_HISTORY SET family_history = ?, last_time_updated = NOW() WHERE patient_id = ?");
        $sql->bind_param("si", $family_history, $patient_id);
        $sql->execute();
    }
    if ($surgeries != $result['surgeries'] && $surgeries != '') {
        $sql = $conn->prepare("UPDATE PREEXISTING_MEDICAL_HISTORY SET surgeries = ?, last_time_updated = NOW() WHERE patient_id = ?");
        $sql->bind_param("si", $surgeries, $patient_id);
        $sql->execute();
    }
    if ($social_history != $result['social_history'] && $social_history != '') {
        $sql = $conn->prepare("UPDATE PREEXISTING_MEDICAL_HISTORY SET social_history = ?, last_time_updated = NOW() WHERE patient_id = ?");
        $sql->bind_param("si", $social_history, $patient_id);
        $sql->execute();
    }
    if ($activity_level != $result['activity_level'] && $activity_level != '') {
        $sql = $conn->prepare("UPDATE PREEXISTING_MEDICAL_HISTORY SET activity_level = ?, last_time_updated = NOW() WHERE patient_id = ?");
        $sql->bind_param("si", $activity_level, $patient_id);
        $sql->execute();
    }
    if ($serious_illnesses != $result['serious_illnesses'] && $serious_illnesses != '') {
        $sql = $conn->prepare("UPDATE PREEXISTING_MEDICAL_HISTORY SET serious_illnesses = ?, last_time_updated = NOW() WHERE patient_id = ?");
        $sql->bind_param("si", $serious_illnesses, $patient_id);
        $sql->execute();
    }
    if ($serious_injuries != $result['serious_injuries'] && $serious_injuries != '') {
        $sql = $conn->prepare("UPDATE PREEXISTING_MEDICAL_HISTORY SET serious_injuries = ?, last_time_updated = NOW() WHERE patient_id = ?");
        $sql->bind_param("si", $serious_injuries, $patient_id);
        $sql->execute();
    }
    if ($other_info != $result['other_info'] && $other_info != '') {
        $sql = $conn->prepare("UPDATE PREEXISTING_MEDICAL_HISTORY SET other_info = ?, last_time_updated = NOW() WHERE patient_id = ?");
        $sql->bind_param("si", $other_info, $patient_id);
        $sql->execute();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Generates a new history file in the data base
// New users must create one with this button before adding new data to it with the form above
if (isset($_POST['new_history_file'])) {
    $sql = $conn->prepare("INSERT INTO PREEXISTING_MEDICAL_HISTORY
                          (patient_id, conditions, allergies, family_history, surgeries, social_history, activity_level, serious_illnesses, serious_injuries, other_info, last_time_updated)
                          VALUES (?, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NOW())");
    $sql->bind_param("i", $patient_id);
    if ($sql->execute()) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    else {
        $error = "Error creating entry.";
    }
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
<body style="overflow: hidden">
    <div class="dashboard-container">
        <div class="dashboard-grid">
            <!-- Card to display the current medical history on file -->
            <div class="dashboard-card">
                <h3>Current Medical History</h3>
                <?php if ($result): ?>
                    <div class="table-card-row">
                        <label class="table-card-label">Conditions</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($result['conditions']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <label class="table-card-label">Allergies</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($result['allergies']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <label class="table-card-label">Family History</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($result['family_history']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <label class="table-card-label">Surgeries</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($result['surgeries']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <label class="table-card-label">Social History</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($result['social_history']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <label class="table-card-label">Activity Level</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($result['activity_level']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <label class="table-card-label">Serious Illnesses</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($result['serious_illnesses']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <label class="table-card-label">Serious Injuries</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($result['serious_injuries']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <label class="table-card-label">Other</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($result['other_info']); ?></div>
                    </div>
                    <div class="table-card-row">
                        <label class="table-card-label">Last Updated</label>
                        <div class="table-card-value"><?php echo htmlspecialchars($result['last_time_updated']); ?></div>
                    </div>
                <?php else: ?>
                    <!-- If no history, ask user to create a file-->
                    <p>No History On File</p>
                    <form method="POST">
                        <button type="submit" name="new_history_file">Create History File</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Form to collect new medical history -->
            <div class="dashboard-card">
                <h3>Update Medical History</h3>
                <form method="POST">
                    <div class="form-row">
                        <label for="conditions">Conditions</label>
                        <input type="text" id="conditions" name="conditions">
                    </div>
                    <div class="form-row">
                        <label for="allergies">Allergies</label>
                        <input type="text" id="allergies" name="allergies">
                    </div>
                    <div class="form-row">
                        <label for="family_history">Family History</label>
                        <input type="text" id="family_history" name="family_history">
                    </div>
                    <div class="form-row">
                        <label for="surgeries">Surgeries</label>
                        <input type="text" id="surgeries" name="surgeries">
                    </div>
                    <div class="form-row">
                        <label for="social_history">Social History</label>
                        <input type="text" id="social_history" name="social_history">
                    </div>
                    <div class="form-row">
                        <label for="activity_level">Activity Level</label>
                        <input type="text" id="activity_level" name="activity_level">
                    </div>
                    <div class="form-row">
                        <label for="serious_illnesses">Serious Illnesses</label>
                        <input type="text" id="serious_illnesses" name="serious_illnesses">
                    </div>
                    <div class="form-row">
                        <label for="serious_injuries">Serious Injuries</label>
                        <input type="text" id="serious_injuries" name="serious_injuries">
                    </div>
                    <div class="form-row">
                        <label for="other_info">Other</label>
                        <textarea name="other_info"></textarea>
                    </div>
                    <button type="submit">Update History</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Displaying error message | mainly just for testing, might implement better error message later-->
    <?php if (!empty($error)): ?>
        <div class="error"><?php echo($error); ?></div>
    <?php endif; ?>
</body>
</html>