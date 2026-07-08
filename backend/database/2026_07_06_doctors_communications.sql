USE health_db;

-- Add receptionist role to users and rename existing admin user
ALTER TABLE users MODIFY COLUMN role ENUM('co','admin','receptionist','moh') NOT NULL;
UPDATE users SET role = 'receptionist', email = 'receptionist@rms.go.tz' WHERE role = 'admin';
ALTER TABLE users MODIFY COLUMN role ENUM('co','receptionist','moh') NOT NULL;

-- Rename feedback sender column to receptionist
ALTER TABLE feedback CHANGE COLUMN sent_by_admin_id sent_by_receptionist_id INT NOT NULL;

-- Create communications table for referral messages
CREATE TABLE IF NOT EXISTS communications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    referral_id INT NOT NULL,
    sender_id INT NOT NULL,
    recipient_id INT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referral_id) REFERENCES referrals(id),
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (recipient_id) REFERENCES users(id)
);

-- Insert doctor users for Dar es Salaam facilities if not already present
INSERT INTO users (email, password, role, first_name, last_name, facility_id, phone)
SELECT * FROM (
    SELECT 'muhimbili.emergency@rms.go.tz' AS email, 'pass123' AS password, 'co' AS role, 'Aisha' AS first_name, 'Mhando' AS last_name, 2 AS facility_id, '+255 715 100 101' AS phone
    UNION ALL SELECT 'muhimbili.internal@rms.go.tz', 'pass123', 'co', 'Joseph', 'Katani', 2, '+255 715 100 102'
    UNION ALL SELECT 'amana.pediatrics@rms.go.tz', 'pass123', 'co', 'Rehema', 'Mtui', 6, '+255 715 100 103'
    UNION ALL SELECT 'amana.obgyn@rms.go.tz', 'pass123', 'co', 'Daniel', 'Lema', 6, '+255 715 100 104'
    UNION ALL SELECT 'mwananyamala.surgery@rms.go.tz', 'pass123', 'co', 'Neema', 'Msuya', 7, '+255 715 100 105'
    UNION ALL SELECT 'mwananyamala.ortho@rms.go.tz', 'pass123', 'co', 'Elias', 'Babu', 7, '+255 715 100 106'
    UNION ALL SELECT 'sinza.internal@rms.go.tz', 'pass123', 'co', 'Fatuma', 'Hassan', 8, '+255 715 100 107'
    UNION ALL SELECT 'sinza.radiology@rms.go.tz', 'pass123', 'co', 'Mohamed', 'Juma', 8, '+255 715 100 108'
    UNION ALL SELECT 'mbagala.family@rms.go.tz', 'pass123', 'co', 'Grace', 'Samson', 9, '+255 715 100 109'
    UNION ALL SELECT 'mbagala.lab@rms.go.tz', 'pass123', 'co', 'Hamisi', 'Sudi', 9, '+255 715 100 110'
    UNION ALL SELECT 'temeke.er@rms.go.tz', 'pass123', 'co', 'Stella', 'Nyerere', 10, '+255 715 100 111'
    UNION ALL SELECT 'temeke.ent@rms.go.tz', 'pass123', 'co', 'John', 'Mlay', 10, '+255 715 100 112'
    UNION ALL SELECT 'agakhan.obgyn@rms.go.tz', 'pass123', 'co', 'Salma', 'Ali', 11, '+255 715 100 113'
    UNION ALL SELECT 'agakhan.ophthalmology@rms.go.tz', 'pass123', 'co', 'Michael', 'Chuwa', 11, '+255 715 100 114'
    UNION ALL SELECT 'bmh.surgery@rms.go.tz', 'pass123', 'co', 'Rose', 'Kamara', 12, '+255 715 100 115'
    UNION ALL SELECT 'bmh.icu@rms.go.tz', 'pass123', 'co', 'Joseph', 'Mwinyi', 12, '+255 715 100 116'
) AS t
WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.email = t.email);

-- Create doctors metadata records from the new doctor users and departments
INSERT INTO doctors (user_id, department_id, license_number, contact_phone)
SELECT u.id, d.id, CONCAT('TZA-', LPAD(FLOOR(RAND()*90000)+10000,5,'0')), u.phone
FROM users u
JOIN departments d ON d.facility_id = u.facility_id
WHERE u.email LIKE '%@rms.go.tz'
  AND u.email NOT LIKE 'co@rms.go.tz'
  AND u.email NOT LIKE 'receptionist@rms.go.tz'
  AND u.email NOT LIKE 'moh@rms.go.tz'
  AND NOT EXISTS (
      SELECT 1 FROM doctors doc WHERE doc.user_id = u.id
  )
  AND (
      (u.email = 'muhimbili.emergency@rms.go.tz' AND d.name = 'Emergency') OR
      (u.email = 'muhimbili.internal@rms.go.tz' AND d.name = 'Internal Medicine') OR
      (u.email = 'amana.pediatrics@rms.go.tz' AND d.name = 'Pediatrics') OR
      (u.email = 'amana.obgyn@rms.go.tz' AND d.name = 'Obstetrics & Gynecology') OR
      (u.email = 'mwananyamala.surgery@rms.go.tz' AND d.name = 'General Surgery') OR
      (u.email = 'mwananyamala.ortho@rms.go.tz' AND d.name = 'Orthopedics') OR
      (u.email = 'sinza.internal@rms.go.tz' AND d.name = 'Internal Medicine') OR
      (u.email = 'sinza.radiology@rms.go.tz' AND d.name = 'Radiology') OR
      (u.email = 'mbagala.family@rms.go.tz' AND d.name = 'Emergency') OR
      (u.email = 'mbagala.lab@rms.go.tz' AND d.name = 'Laboratory') OR
      (u.email = 'temeke.er@rms.go.tz' AND d.name = 'Emergency') OR
      (u.email = 'temeke.ent@rms.go.tz' AND d.name = 'ENT') OR
      (u.email = 'agakhan.obgyn@rms.go.tz' AND d.name = 'Obstetrics & Gynecology') OR
      (u.email = 'agakhan.ophthalmology@rms.go.tz' AND d.name = 'Ophthalmology') OR
      (u.email = 'bmh.surgery@rms.go.tz' AND d.name = 'General Surgery') OR
      (u.email = 'bmh.icu@rms.go.tz' AND d.name = 'Intensive Care Unit (ICU)')
  );
