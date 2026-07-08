-- RMS Database Schema and Seed Data
-- Referral Management System for Tanzania's Public Healthcare Network

-- Create database
CREATE DATABASE IF NOT EXISTS health_db;
USE health_db;

-- ===========================================
-- TABLE: users
-- Stores all system users with role-based access
-- ===========================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('co', 'receptionist', 'moh') NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    facility_id INT,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- ===========================================
-- TABLE: facilities
-- Health facilities across Tanzania's 5-tier system
-- ===========================================
CREATE TABLE facilities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    tier ENUM('dispensary', 'health_centre', 'district_hospital', 'regional_hospital', 'national_hospital') NOT NULL,
    region VARCHAR(100),
    district VARCHAR(100),
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(255),
    capacity INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ===========================================
-- TABLE: patients
-- Basic patient information for referrals
-- ===========================================
CREATE TABLE patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    phone VARCHAR(20),
    address TEXT,
    national_id VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ===========================================
-- TABLE: referrals
-- Core referral records with status tracking
-- ===========================================
CREATE TABLE referrals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    patient_number VARCHAR(80),
    age_years VARCHAR(20),
    referring_co_id INT NOT NULL,
    referring_facility_id INT NOT NULL,
    receiving_facility_id INT NOT NULL,
    region VARCHAR(100),
    district VARCHAR(100),
    transfer_date DATE,
    referral_number VARCHAR(100),
    status ENUM('pending', 'accepted', 'in_progress', 'completed', 'rejected') DEFAULT 'pending',
    urgency ENUM('emergency', 'urgent', 'routine') DEFAULT 'routine',
    diagnosis TEXT,
    temperature VARCHAR(20),
    heart_rate VARCHAR(20),
    respiratory_rate VARCHAR(20),
    blood_pressure VARCHAR(30),
    mental_status VARCHAR(100),
    alert_status VARCHAR(100),
    patient_history TEXT,
    chronic_medications TEXT,
    medication_allergies TEXT,
    examination_findings TEXT,
    laboratory_results TEXT,
    radiology_results TEXT,
    treatment_before_transfer TEXT,
    reason_for_transfer TEXT,
    doctor_name VARCHAR(150),
    doctor_phone VARCHAR(30),
    facilitator_phone VARCHAR(30),
    clinical_reason TEXT NOT NULL,
    clinical_findings TEXT,
    requested_services TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    rejection_reason TEXT,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (referring_co_id) REFERENCES users(id),
    FOREIGN KEY (referring_facility_id) REFERENCES facilities(id),
    FOREIGN KEY (receiving_facility_id) REFERENCES facilities(id)
);

-- ===========================================
-- TABLE: feedback
-- Clinical feedback sent by receiving facilities
-- ===========================================
CREATE TABLE feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    referral_id INT NOT NULL,
    sent_by_receptionist_id INT NOT NULL,
    department VARCHAR(150),
    referral_serial_no VARCHAR(100),
    referral_diagnosis TEXT,
    confirmed_diagnosis TEXT,
    comments TEXT,
    clinical_outcome TEXT NOT NULL,
    treatment_given TEXT,
    discharge_summary TEXT,
    follow_up_instructions TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referral_id) REFERENCES referrals(id),
    FOREIGN KEY (sent_by_admin_id) REFERENCES users(id)
);

-- ===========================================
-- TABLE: notifications
-- Email and SMS notification logs
-- ===========================================
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    referral_id INT,
    type ENUM('email', 'sms') NOT NULL,
    recipient_email VARCHAR(255),
    recipient_phone VARCHAR(20),
    subject VARCHAR(255),
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    error_message TEXT,
    FOREIGN KEY (referral_id) REFERENCES referrals(id)
);

-- ===========================================
-- SEED DATA: Sample Facilities
-- ===========================================
INSERT INTO facilities (name, tier, region, district, address, phone, email, capacity) VALUES
('Kilimanjaro Christian Medical Centre', 'national_hospital', 'Kilimanjaro', 'Moshi', 'Moshi, Tanzania', '+255 27 275 0060', 'info@kcmc.ac.tz', 600),
('Muhimbili National Hospital', 'national_hospital', 'Dar es Salaam', 'Ilala', 'Dar es Salaam, Tanzania', '+255 22 215 1362', 'info@mnh.go.tz', 1500),
('Bugando Medical Centre', 'regional_hospital', 'Mwanza', 'Nyamagana', 'Mwanza, Tanzania', '+255 28 250 0888', 'info@bugando.ac.tz', 800),
('Mbeya Referral Hospital', 'regional_hospital', 'Mbeya', 'Mbeya', 'Mbeya, Tanzania', '+255 25 250 3355', 'info@mbeya.go.tz', 500),
('Dodoma Regional Referral Hospital', 'regional_hospital', 'Dodoma', 'Dodoma', 'Dodoma, Tanzania', '+255 26 232 1261', 'info@dodoma.go.tz', 400),
('Amana District Hospital', 'district_hospital', 'Dar es Salaam', 'Temeke', 'Temeke, Dar es Salaam', '+255 22 285 0000', 'info@amana.go.tz', 200),
('Mwananyamala Regional Hospital', 'regional_hospital', 'Dar es Salaam', 'Kinondoni', 'Kinondoni, Dar es Salaam', '+255 22 277 0000', 'info@mwananyamala.go.tz', 300),
('Sinza Health Centre', 'health_centre', 'Dar es Salaam', 'Kinondoni', 'Sinza, Dar es Salaam', '+255 22 277 1234', 'info@sinza.go.tz', 50),
('Mbagala Dispensary', 'dispensary', 'Dar es Salaam', 'Temeke', 'Mbagala, Dar es Salaam', '+255 22 285 5678', 'info@mbagala.go.tz', 20);

-- ===========================================
-- SEED DATA: Test Users (One per role)
-- ===========================================
INSERT INTO users (email, password, role, first_name, last_name, facility_id, phone) VALUES
-- CO (Clinician/Doctor)
('co@rms.go.tz', 'co123', 'co', 'John', 'Doe', 8, '+255 712 345 678'),
-- Hospital Admin
('receptionist@rms.go.tz', 'admin123', 'receptionist', 'Jane', 'Smith', 7, '+255 713 456 789'),
-- MOH Official
('moh@rms.go.tz', 'moh123', 'moh', 'David', 'Wilson', NULL, '+255 714 567 890');

-- ===========================================
-- SEED DATA: Sample Patients
-- ===========================================
INSERT INTO patients (first_name, last_name, date_of_birth, gender, phone, address) VALUES
('Maria', 'Joseph', '1985-03-15', 'female', '+255 715 678 901', 'Sinza, Dar es Salaam'),
('Ahmed', 'Kassim', '1990-07-22', 'male', '+255 716 789 012', 'Mbagala, Dar es Salaam'),
('Grace', 'Mwanga', '1978-11-08', 'female', '+255 717 890 123', 'Temeke, Dar es Salaam');

-- ===========================================
-- SEED DATA: Sample Referral
-- ===========================================
INSERT INTO referrals (patient_id, referring_co_id, referring_facility_id, receiving_facility_id, status, urgency, clinical_reason, clinical_findings, requested_services) VALUES
(1, 1, 8, 7, 'pending', 'urgent', 'Suspected appendicitis', 'Severe abdominal pain, fever, nausea', 'Surgical consultation and possible appendectomy');

-- ===========================================
-- SEED DATA: Sample Feedback
-- ===========================================
INSERT INTO feedback (referral_id, sent_by_receptionist_id, clinical_outcome, treatment_given, discharge_summary) VALUES
(1, 2, 'Acute appendicitis confirmed', 'Emergency appendectomy performed successfully', 'Patient discharged after 3 days, full recovery expected');

-- ===========================================
-- SEED DATA: Sample Notifications
-- ===========================================
INSERT INTO notifications (referral_id, type, recipient_email, recipient_phone, subject, message, status) VALUES
(1, 'email', 'co@rms.go.tz', '+255 712 345 678', 'Referral Update', 'Your referral for Maria Joseph has been accepted by Mwananyamala Regional Hospital', 'sent'),
(1, 'sms', NULL, '+255 712 345 678', NULL, 'Referral accepted: Maria Joseph - Mwananyamala Regional Hospital', 'sent');

-- ===========================================
-- INDEXES for Performance
-- ===========================================
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_facilities_tier ON facilities(tier);
CREATE INDEX idx_referrals_status ON referrals(status);
CREATE INDEX idx_referrals_created_at ON referrals(created_at);
CREATE INDEX idx_notifications_type ON notifications(type);
CREATE INDEX idx_notifications_status ON notifications(status);
