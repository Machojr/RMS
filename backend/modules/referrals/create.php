<?php
// ===========================================
// Referrals API - Create Referral
// POST /referrals/create.php
// ===========================================

require_once __DIR__ . '/../../config/db.php';
require_once dirname(__DIR__, 2) . '/includes/session.php';

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
$receivingFacilityId = (int) $input['receiving_facility_id'];
$urgency = trim($input['urgency']);
$clinicalReason = trim($input['clinical_reason']);
$clinicalFindings = !empty($input['clinical_findings']) ? trim($input['clinical_findings']) : null;
$requestedServices = !empty($input['requested_services']) ? trim($input['requested_services']) : null;

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

// Insert the referral
$stmt = $conn->prepare(
    'INSERT INTO referrals (patient_id, referring_co_id, referring_facility_id, receiving_facility_id, urgency, clinical_reason, clinical_findings, requested_services)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param(
    'iiiiisss',
    $patientId,
    $user['id'],
    $user['facility_id'],
    $receivingFacilityId,
    $urgency,
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

sendResponse([
    'success' => true,
    'message' => 'Referral created successfully',
    'referral_id' => $referralId,
]);
?>