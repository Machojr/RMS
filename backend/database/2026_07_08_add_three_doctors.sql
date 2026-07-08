-- ===========================================
-- Add Three Doctors with Contact Details
-- Doctors for the three main hospitals
-- ===========================================
USE health_db;

-- Step 1: Ensure the three hospitals exist with correct IDs
-- Check if hospitals exist, get their IDs
SELECT @muhimbili_id := id FROM facilities WHERE name = 'Muhimbili National Hospital' LIMIT 1;
SELECT @mwananyamala_id := id FROM facilities WHERE name = 'Mwananyamala Regional Hospital' LIMIT 1;
SELECT @agakhan_id := id FROM facilities WHERE name = 'Aga Khan Hospital Dar es Salaam' LIMIT 1;

-- If Aga Khan doesn't exist, insert it
INSERT INTO facilities (name, tier, region, district, address, phone, email, capacity)
SELECT 'Aga Khan Hospital Dar es Salaam', 'regional_hospital', 'Dar es Salaam', 'Ilala', 'Kivukoni / Masaki area', '+255 22 2000000', 'info@agakhandc.org', 200
WHERE NOT EXISTS (SELECT 1 FROM facilities WHERE name = 'Aga Khan Hospital Dar es Salaam');

-- Re-fetch IDs
SELECT @muhimbili_id := id FROM facilities WHERE name = 'Muhimbili National Hospital' LIMIT 1;
SELECT @mwananyamala_id := id FROM facilities WHERE name = 'Mwananyamala Regional Hospital' LIMIT 1;
SELECT @agakhan_id := id FROM facilities WHERE name = 'Aga Khan Hospital Dar es Salaam' LIMIT 1;

-- Step 2: Ensure Emergency, Surgery, and General departments exist for each hospital

-- For Muhimbili
INSERT INTO departments (facility_id, name)
SELECT @muhimbili_id, 'Emergency' 
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE facility_id = @muhimbili_id AND name = 'Emergency');

INSERT INTO departments (facility_id, name)
SELECT @muhimbili_id, 'General Surgery' 
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE facility_id = @muhimbili_id AND name = 'General Surgery');

-- For Mwananyamala
INSERT INTO departments (facility_id, name)
SELECT @mwananyamala_id, 'General Surgery' 
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE facility_id = @mwananyamala_id AND name = 'General Surgery');

INSERT INTO departments (facility_id, name)
SELECT @mwananyamala_id, 'Orthopedics' 
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE facility_id = @mwananyamala_id AND name = 'Orthopedics');

-- For Aga Khan
INSERT INTO departments (facility_id, name)
SELECT @agakhan_id, 'Internal Medicine' 
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE facility_id = @agakhan_id AND name = 'Internal Medicine');

INSERT INTO departments (facility_id, name)
SELECT @agakhan_id, 'Cardiology' 
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE facility_id = @agakhan_id AND name = 'Cardiology');

-- Step 3: Get department IDs for assignment
SELECT @muhimbili_emergency_id := id FROM departments WHERE facility_id = @muhimbili_id AND name = 'Emergency' LIMIT 1;
SELECT @mwananyamala_surgery_id := id FROM departments WHERE facility_id = @mwananyamala_id AND name = 'General Surgery' LIMIT 1;
SELECT @agakhan_internal_id := id FROM departments WHERE facility_id = @agakhan_id AND name = 'Internal Medicine' LIMIT 1;

-- Step 4: Insert or update the three doctors
-- 1. Dr. Said Juma - Muhimbili Emergency
INSERT INTO users (email, password, role, first_name, last_name, facility_id, phone)
VALUES ('said.juma@muhimbili.rms.go.tz', 'pass123', 'co', 'Said', 'Juma', @muhimbili_id, '0663373544')
ON DUPLICATE KEY UPDATE 
    phone = '0663373544',
    first_name = 'Said',
    last_name = 'Juma',
    role = 'co',
    facility_id = @muhimbili_id;

-- Get user ID and link to doctor
SELECT @said_juma_user_id := id FROM users WHERE email = 'said.juma@muhimbili.rms.go.tz' LIMIT 1;

INSERT INTO doctors (user_id, department_id, license_number, contact_phone)
SELECT @said_juma_user_id, @muhimbili_emergency_id, 'LIC-SJ-2026', '0663373544'
WHERE NOT EXISTS (SELECT 1 FROM doctors WHERE user_id = @said_juma_user_id)
ON DUPLICATE KEY UPDATE 
    contact_phone = '0663373544',
    department_id = @muhimbili_emergency_id;

-- 2. Dr. Ben Musso - Mwananyamala Surgery
INSERT INTO users (email, password, role, first_name, last_name, facility_id, phone)
VALUES ('ben.musso@mwananyamala.rms.go.tz', 'pass123', 'co', 'Ben', 'Musso', @mwananyamala_id, '0764343059')
ON DUPLICATE KEY UPDATE 
    phone = '0764343059',
    first_name = 'Ben',
    last_name = 'Musso',
    role = 'co',
    facility_id = @mwananyamala_id;

SELECT @ben_musso_user_id := id FROM users WHERE email = 'ben.musso@mwananyamala.rms.go.tz' LIMIT 1;

INSERT INTO doctors (user_id, department_id, license_number, contact_phone)
SELECT @ben_musso_user_id, @mwananyamala_surgery_id, 'LIC-BM-2026', '0764343059'
WHERE NOT EXISTS (SELECT 1 FROM doctors WHERE user_id = @ben_musso_user_id)
ON DUPLICATE KEY UPDATE 
    contact_phone = '0764343059',
    department_id = @mwananyamala_surgery_id;

-- 3. Dr. Revo Fawzan - Aga Khan Internal Medicine
INSERT INTO users (email, password, role, first_name, last_name, facility_id, phone)
VALUES ('revo.fawzan@agakhan.rms.go.tz', 'pass123', 'co', 'Revo', 'Fawzan', @agakhan_id, '0627179640')
ON DUPLICATE KEY UPDATE 
    phone = '0627179640',
    first_name = 'Revo',
    last_name = 'Fawzan',
    role = 'co',
    facility_id = @agakhan_id;

SELECT @revo_fawzan_user_id := id FROM users WHERE email = 'revo.fawzan@agakhan.rms.go.tz' LIMIT 1;

INSERT INTO doctors (user_id, department_id, license_number, contact_phone)
SELECT @revo_fawzan_user_id, @agakhan_internal_id, 'LIC-RF-2026', '0627179640'
WHERE NOT EXISTS (SELECT 1 FROM doctors WHERE user_id = @revo_fawzan_user_id)
ON DUPLICATE KEY UPDATE 
    contact_phone = '0627179640',
    department_id = @agakhan_internal_id;

-- Step 5: Verification - Show the three doctors with their details
SELECT 
    u.id,
    u.email,
    u.first_name,
    u.last_name,
    u.phone,
    d.id AS doctor_id,
    d.contact_phone,
    dep.name AS department_name,
    f.name AS facility_name
FROM users u
JOIN doctors d ON u.id = d.user_id
JOIN departments dep ON d.department_id = dep.id
JOIN facilities f ON u.facility_id = f.id
WHERE u.email IN ('said.juma@muhimbili.rms.go.tz', 'ben.musso@mwananyamala.rms.go.tz', 'revo.fawzan@agakhan.rms.go.tz')
ORDER BY f.name, u.first_name;
