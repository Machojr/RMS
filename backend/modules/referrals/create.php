<?php
// ===========================================
// Referrals API - Create Referral
// POST /referrals/create.php
// ===========================================

require_once __DIR__ . '/../../config/db.php';
require_once dirname(__DIR__, 2) . '/includes/session.php';
require_once dirname(__DIR__, 2) . '/includes/notifications.php';
require_once dirname(__DIR__, 2) . '/includes/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

if (!isLoggedIn()) {
    sendError('Authentication required', 401);
}

$user = getCurrentUser();

if ($user['role'] !== 'co') {
    sendError('Only Clinical Officers can create referrals', 403);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    sendError('Invalid JSON input');
}

validateRequired($input, [
    'patient_first_name',
    'patient_last_name',
    'gender',
    'receiving_facility_id',
    'receiving_department_id',
    'urgency',
    'clinical_reason',
]);

$patientFirstName = trim($input['patient_first_name']);
$patientLastName = trim($input['patient_last_name']);
$gender = trim($input['gender']);
$dateOfBirth = !empty($input['date_of_birth']) ? trim($input['date_of_birth']) : null;
$phone = !empty($input['phone']) ? trim($input['phone']) : null;
$address = !empty($input['address']) ? trim($input['address']) : null;
$nationalId = !empty($input['national_id']) ? trim($input['national_id']) : null;
$patientNumber = !empty($input['patient_number']) ? trim($input['patient_number']) : null;
$ageYears = !empty($input['age_years']) ? trim($input['age_years']) : null;
$receivingFacilityId = (int) $input['receiving_facility_id'];
$receivingDepartmentId = isset($input['receiving_department_id']) ? (int)$input['receiving_department_id'] : null;
$assignedDoctorId = null;
$region = !empty($input['region']) ? trim($input['region']) : null;
$district = !empty($input['district']) ? trim($input['district']) : null;
$transferDate = !empty($input['transfer_date']) ? trim($input['transfer_date']) : null;
$referralNumber = !empty($input['referral_number']) ? trim($input['referral_number']) : null;
$urgency = trim($input['urgency']);
$diagnosis = !empty($input['diagnosis']) ? trim($input['diagnosis']) : null;
$temperature = !empty($input['temperature']) ? trim($input['temperature']) : null;
$heartRate = !empty($input['heart_rate']) ? trim($input['heart_rate']) : null;
$respiratoryRate = !empty($input['respiratory_rate']) ? trim($input['respiratory_rate']) : null;
$bloodPressure = !empty($input['blood_pressure']) ? trim($input['blood_pressure']) : null;
$mentalStatus = !empty($input['mental_status']) ? trim($input['mental_status']) : null;
$alertStatus = null;
$patientHistory = null;
$chronicMedications = null;
$medicationAllergies = null;
$examinationFindings = null;
$laboratoryResults = null;
$radiologyResults = null;
$treatmentBeforeTransfer = !empty($input['treatment_before_transfer']) ? trim($input['treatment_before_transfer']) : null;
$reasonForTransfer = !empty($input['reason_for_transfer']) ? trim($input['reason_for_transfer']) : null;
$doctorName = trim($user['first_name'] . ' ' . $user['last_name']);
$doctorPhone = null;
$facilitatorPhone = null;
$clinicalReason = trim($input['clinical_reason']);
$clinicalFindings = !empty($input['clinical_findings']) ? trim($input['clinical_findings']) : null;
$requestedServices = !empty($input['requested_services']) ? trim($input['requested_services']) : null;

$doctorStmt = $conn->prepare('SELECT phone FROM users WHERE id = ?');
$doctorStmt->bind_param('i', $user['id']);
$doctorStmt->execute();
$doctorResult = $doctorStmt->get_result();
if ($doctorRow = $doctorResult->fetch_assoc()) {
    $doctorPhone = $doctorRow['phone'];
}
$doctorStmt->close();

// Validate receiving facility exists
$stmt = $conn->prepare('SELECT id FROM facilities WHERE id = ?');
$stmt->bind_param('i', $receivingFacilityId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    sendError('Selected receiving facility does not exist', 404);
}
$stmt->close();

// Add patient record
$stmt = $conn->prepare(
    'INSERT INTO patients (first_name, last_name, date_of_birth, gender, phone, address, national_id)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param(
    'sssssss',
    $patientFirstName,
    $patientLastName,
    $dateOfBirth,
    $gender,
    $phone,
    $address,
    $nationalId
);
$success = $stmt->execute();
if (!$success) {
    sendError('Unable to create patient record');
}
$patientId = $stmt->insert_id;
$stmt->close();

// Insert the referral (including receiving department and optional assigned doctor)
$stmt = $conn->prepare(
    'INSERT INTO referrals (
        patient_id, patient_number, age_years, referring_co_id, referring_facility_id, receiving_facility_id,
        receiving_department_id, assigned_doctor_id, region, district, transfer_date, referral_number, urgency,
        diagnosis, temperature, heart_rate, respiratory_rate, blood_pressure, mental_status, alert_status,
        patient_history, chronic_medications, medication_allergies, examination_findings, laboratory_results,
        radiology_results, treatment_before_transfer, reason_for_transfer, doctor_name, doctor_phone, facilitator_phone,
        clinical_reason, clinical_findings, requested_services
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param(
    'issiiiiissssssssssssssssssssssssss',
    $patientId,
    $patientNumber,
    $ageYears,
    $user['id'],
    $user['facility_id'],
    $receivingFacilityId,
    $receivingDepartmentId,
    $assignedDoctorId,
    $region,
    $district,
    $transferDate,
    $referralNumber,
    $urgency,
    $diagnosis,
    $temperature,
    $heartRate,
    $respiratoryRate,
    $bloodPressure,
    $mentalStatus,
    $alertStatus,
    $patientHistory,
    $chronicMedications,
    $medicationAllergies,
    $examinationFindings,
    $laboratoryResults,
    $radiologyResults,
    $treatmentBeforeTransfer,
    $reasonForTransfer,
    $doctorName,
    $doctorPhone,
    $facilitatorPhone,
    $clinicalReason,
    $clinicalFindings,
    $requestedServices
);
$success = $stmt->execute();
if (!$success) {
    sendError('Unable to create referral');
}
$referralId = $stmt->insert_id;
$stmt->close();

$receptionistNotified = notifyReceivingReceptionistOfReferral($conn, $referralId);
logAudit($conn, $user, 'referral_created', $referralId, null, 'pending', 'Referral created and sent to receiving facility');

sendResponse([
    'success' => true,
    'message' => $receptionistNotified
        ? 'Referral created successfully and receiving receptionist was notified'
        : 'Referral created successfully, but receiving receptionist email notification was not sent',
    'referral_id' => $referralId,
    'receptionist_notified' => $receptionistNotified,
]);
?>
