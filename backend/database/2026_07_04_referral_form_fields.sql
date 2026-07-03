USE health_db;

ALTER TABLE referrals
    ADD COLUMN patient_number VARCHAR(80) NULL AFTER patient_id,
    ADD COLUMN age_years VARCHAR(20) NULL AFTER patient_number,
    ADD COLUMN region VARCHAR(100) NULL AFTER receiving_facility_id,
    ADD COLUMN district VARCHAR(100) NULL AFTER region,
    ADD COLUMN transfer_date DATE NULL AFTER district,
    ADD COLUMN referral_number VARCHAR(100) NULL AFTER transfer_date,
    ADD COLUMN diagnosis TEXT NULL AFTER urgency,
    ADD COLUMN temperature VARCHAR(20) NULL AFTER diagnosis,
    ADD COLUMN heart_rate VARCHAR(20) NULL AFTER temperature,
    ADD COLUMN respiratory_rate VARCHAR(20) NULL AFTER heart_rate,
    ADD COLUMN blood_pressure VARCHAR(30) NULL AFTER respiratory_rate,
    ADD COLUMN mental_status VARCHAR(100) NULL AFTER blood_pressure,
    ADD COLUMN alert_status VARCHAR(100) NULL AFTER mental_status,
    ADD COLUMN patient_history TEXT NULL AFTER alert_status,
    ADD COLUMN chronic_medications TEXT NULL AFTER patient_history,
    ADD COLUMN medication_allergies TEXT NULL AFTER chronic_medications,
    ADD COLUMN examination_findings TEXT NULL AFTER medication_allergies,
    ADD COLUMN laboratory_results TEXT NULL AFTER examination_findings,
    ADD COLUMN radiology_results TEXT NULL AFTER laboratory_results,
    ADD COLUMN treatment_before_transfer TEXT NULL AFTER radiology_results,
    ADD COLUMN reason_for_transfer TEXT NULL AFTER treatment_before_transfer,
    ADD COLUMN doctor_name VARCHAR(150) NULL AFTER reason_for_transfer,
    ADD COLUMN doctor_phone VARCHAR(30) NULL AFTER doctor_name,
    ADD COLUMN facilitator_phone VARCHAR(30) NULL AFTER doctor_phone;

ALTER TABLE feedback
    ADD COLUMN department VARCHAR(150) NULL AFTER sent_by_admin_id,
    ADD COLUMN referral_serial_no VARCHAR(100) NULL AFTER department,
    ADD COLUMN referral_diagnosis TEXT NULL AFTER referral_serial_no,
    ADD COLUMN confirmed_diagnosis TEXT NULL AFTER referral_diagnosis,
    ADD COLUMN comments TEXT NULL AFTER confirmed_diagnosis;
