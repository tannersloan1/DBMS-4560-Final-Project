<?php
// include auth and db
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/activity_logger.php';
require('../fpdf.php');

$patient_id = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT hv.visit_date, hv.visit_reason, hv.diagnosis,
            rvd.height, rvd.weight, rvd.blood_pressure, rvd.heart_rate, rvd.temperature,
            rvd.respiration_rate, rvd.new_medicines, rvd.new_conditions, rvd.urgent_concern, rvd.extra_notes
     FROM HOSPITAL_VISITS hv
     LEFT JOIN RETURNED_VISIT_DATA rvd ON hv.visit_id = rvd.visit_id
     WHERE hv.patient_id = ?
     ORDER BY hv.visit_date DESC
     LIMIT 10"
);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$visits = $stmt->get_result();

//log patient downloading their data
logUserAction($conn, $patient_id, 'patient', 'DATA_DOWNLOAD', 'Patient downloaded their visit data');

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont("Arial", "B", 16);

// Title
$pdf->Cell(0, 10, "Recent Hospital Visit Records", 0, 1, "C");
$pdf->Ln(5);

// Body font
$pdf->SetFont("Arial", "", 12);

if ($visits->num_rows === 0) {
    $pdf->Cell(0, 10, "No visit data found.", 0, 1);
} else {
    while ($row = $visits->fetch_assoc()) {

        // Visit Header
        $pdf->SetFont("Arial", "B", 13);
        $pdf->Cell(0, 10, "Visit on " . $row['visit_date'], 0, 1);
        $pdf->SetFont("Arial", "", 12);

        // Visit info
        $pdf->Cell(0, 8, "Reason: " . $row['visit_reason'], 0, 1);
        $pdf->MultiCell(0, 8, "Diagnosis: " . $row['diagnosis']);

        $pdf->Ln(2);

        // Measurements
        $pdf->Cell(0, 8, "Height: " . $row['height'], 0, 1);
        $pdf->Cell(0, 8, "Weight: " . $row['weight'], 0, 1);
        $pdf->Cell(0, 8, "Blood Pressure: " . $row['blood_pressure'], 0, 1);
        $pdf->Cell(0, 8, "Heart Rate: " . $row['heart_rate'], 0, 1);
        $pdf->Cell(0, 8, "Temperature: " . $row['temperature'], 0, 1);
        $pdf->Cell(0, 8, "Respiration Rate: " . $row['respiration_rate'], 0, 1);

        $pdf->Ln(2);

        // New meds / diagnoses
        $pdf->MultiCell(0, 8, "New Medicines: " . $row['new_medicines']);
        $pdf->MultiCell(0, 8, "New Conditions: " . $row['new_conditions']);

        $pdf->Ln(2);

        // Urgent concerns
        $pdf->MultiCell(0, 8, "Urgent Concern: " . $row['urgent_concern']);

        // Notes
        $pdf->MultiCell(0, 8, "Additional Notes: " . $row['extra_notes']);

        // Divider
        $pdf->Ln(5);
        $pdf->Cell(0, 0, "", "T"); // horizontal line
        $pdf->Ln(5);

        // Add new page if space is low
        if ($pdf->GetY() > 250) {
            $pdf->AddPage();
        }
    }
}

// Output the PDF
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename='visit_records.pdf'");
$pdf->Output("I");
?>
