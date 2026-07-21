ALTER TABLE referrals
    ADD COLUMN doctor_decision ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending' AFTER rejection_reason,
    ADD COLUMN doctor_decision_reason TEXT NULL AFTER doctor_decision,
    ADD COLUMN doctor_decision_by INT NULL AFTER doctor_decision_reason,
    ADD COLUMN doctor_decision_at TIMESTAMP NULL AFTER doctor_decision_by,
    ADD INDEX idx_referrals_doctor_decision (doctor_decision),
    ADD INDEX idx_referrals_doctor_decision_by (doctor_decision_by);

ALTER TABLE notifications
    ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER error_message,
    ADD COLUMN read_at TIMESTAMP NULL AFTER is_read,
    ADD COLUMN action_type VARCHAR(80) NULL AFTER read_at,
    ADD COLUMN referral_number VARCHAR(100) NULL AFTER action_type,
    ADD COLUMN patient_name VARCHAR(220) NULL AFTER referral_number,
    ADD COLUMN quick_action_link VARCHAR(500) NULL AFTER patient_name,
    ADD INDEX idx_notifications_recipient_read (recipient_user_id, is_read),
    ADD INDEX idx_notifications_action_type (action_type);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    role VARCHAR(50) NULL,
    action VARCHAR(120) NOT NULL,
    referral_id INT NULL,
    previous_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NULL,
    remarks TEXT NULL,
    ip_address VARCHAR(64) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_referral (referral_id),
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_action (action)
);

CREATE TABLE IF NOT EXISTS treatment_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referral_id INT NOT NULL,
    submitted_by_user_id INT NOT NULL,
    diagnosis TEXT NOT NULL,
    investigations TEXT NULL,
    treatment_given TEXT NOT NULL,
    procedures_done TEXT NULL,
    patient_outcome TEXT NOT NULL,
    current_condition TEXT NULL,
    recommendation TEXT NULL,
    suggested_follow_up TEXT NULL,
    report_date DATE NOT NULL,
    doctor_name VARCHAR(160) NOT NULL,
    digital_signature VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_treatment_referral (referral_id),
    INDEX idx_treatment_submitted_by (submitted_by_user_id)
);
