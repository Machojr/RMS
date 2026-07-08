USE health_db;

INSERT IGNORE INTO users (email, password, role, first_name, last_name, facility_id, phone)
VALUES
('muhimbili.emergency@rms.go.tz', 'pass123', 'co', 'Aisha', 'Mhando', 2, '+255 715 100 101'),
('muhimbili.internal@rms.go.tz', 'pass123', 'co', 'Joseph', 'Katani', 2, '+255 715 100 102'),
('amana.pediatrics@rms.go.tz', 'pass123', 'co', 'Rehema', 'Mtui', 6, '+255 715 100 103'),
('amana.obgyn@rms.go.tz', 'pass123', 'co', 'Daniel', 'Lema', 6, '+255 715 100 104'),
('mwananyamala.surgery@rms.go.tz', 'pass123', 'co', 'Neema', 'Msuya', 7, '+255 715 100 105'),
('mwananyamala.ortho@rms.go.tz', 'pass123', 'co', 'Elias', 'Babu', 7, '+255 715 100 106'),
('sinza.internal@rms.go.tz', 'pass123', 'co', 'Fatuma', 'Hassan', 8, '+255 715 100 107'),
('sinza.radiology@rms.go.tz', 'pass123', 'co', 'Mohamed', 'Juma', 8, '+255 715 100 108'),
('mbagala.family@rms.go.tz', 'pass123', 'co', 'Grace', 'Samson', 9, '+255 715 100 109'),
('mbagala.lab@rms.go.tz', 'pass123', 'co', 'Hamisi', 'Sudi', 9, '+255 715 100 110'),
('temeke.er@rms.go.tz', 'pass123', 'co', 'Stella', 'Nyerere', 10, '+255 715 100 111'),
('temeke.ent@rms.go.tz', 'pass123', 'co', 'John', 'Mlay', 10, '+255 715 100 112'),
('agakhan.obgyn@rms.go.tz', 'pass123', 'co', 'Salma', 'Ali', 11, '+255 715 100 113'),
('agakhan.ophthalmology@rms.go.tz', 'pass123', 'co', 'Michael', 'Chuwa', 11, '+255 715 100 114'),
('bmh.surgery@rms.go.tz', 'pass123', 'co', 'Rose', 'Kamara', 12, '+255 715 100 115'),
('bmh.icu@rms.go.tz', 'pass123', 'co', 'Joseph', 'Mwinyi', 12, '+255 715 100 116');

INSERT INTO doctors (user_id, department_id, license_number, contact_phone)
SELECT u.id, d.id, CONCAT('TZA-', LPAD(FLOOR(RAND()*90000)+10000,5,'0')), u.phone
FROM users u
JOIN departments d ON d.facility_id = u.facility_id
WHERE u.email = 'muhimbili.emergency@rms.go.tz' AND d.name = 'Emergency'
  AND NOT EXISTS (SELECT 1 FROM doctors doc WHERE doc.user_id = u.id)
UNION ALL
SELECT u.id, d.id, CONCAT('TZA-', LPAD(FLOOR(RAND()*90000)+10000,5,'0')), u.phone
FROM users u
JOIN departments d ON d.facility_id = u.facility_id
WHERE u.email = 'muhimbili.internal@rms.go.tz' AND d.name = 'Internal Medicine'
  AND NOT EXISTS (SELECT 1 FROM doctors doc WHERE doc.user_id = u.id)
UNION ALL
SELECT u.id, d.id, CONCAT('TZA-', LPAD(FLOOR(RAND()*90000)+10000,5,'0')), u.phone
FROM users u
JOIN departments d ON d.facility_id = u.facility_id
WHERE u.email = 'amana.pediatrics@rms.go.tz' AND d.name = 'Pediatrics'
  AND NOT EXISTS (SELECT 1 FROM doctors doc WHERE doc.user_id = u.id)
UNION ALL
SELECT u.id, d.id, CONCAT('TZA-', LPAD(FLOOR(RAND()*90000)+10000,5,'0')), u.phone
FROM users u
JOIN departments d ON d.facility_id = u.facility_id
WHERE u.email = 'amana.obgyn@rms.go.tz' AND d.name = 'Obstetrics & Gynecology'
  AND NOT EXISTS (SELECT 1 FROM doctors doc WHERE doc.user_id = u.id)
UNION ALL
SELECT u.id, d.id, CONCAT('TZA-', LPAD(FLOOR(RAND()*90000)+10000,5,'0')), u.phone
FROM users u
JOIN departments d ON d.facility_id = u.facility_id
WHERE u.email = 'mwananyamala.surgery@rms.go.tz' AND d.name = 'General Surgery'
  AND NOT EXISTS (SELECT 1 FROM doctors doc WHERE doc.user_id = u.id)
UNION ALL
SELECT u.id, d.id, CONCAT('TZA-', LPAD(FLOOR(RAND()*90000)+10000,5,'0')), u.phone
FROM users u
JOIN departments d ON d.facility_id = u.facility_id
WHERE u.email = 'mwananyamala.ortho@rms.go.tz' AND d.name = 'Orthopedics'
  AND NOT EXISTS (SELECT 1 FROM doctors doc WHERE doc.user_id = u.id)
UNION ALL
SELECT u.id, d.id, CONCAT('TZA-', LPAD(FLOOR(RAND()*90000)+10000,5,'0')), u.phone
FROM users u
JOIN departments d ON d.facility_id = u.facility_id
WHERE u.email = 'sinza.internal@rms.go.tz' AND d.name = 'Internal Medicine'
  AND NOT EXISTS (SELECT 1 FROM doctors doc WHERE doc.user_id = u.id)
UNION ALL
SELECT u.id, d.id, CONCAT('TZA-', LPAD(FLOOR(RAND()*90000)+10000,5,'0')), u.phone
FROM users u
JOIN departments d ON d.facility_id = u.facility_id
WHERE u.email = 'sinza.radiology@rms.go.tz' AND d.name = 'Radiology'
  AND NOT EXISTS (SELECT 1 FROM doctors doc WHERE doc.user_id = u.id)
UNION ALL
SELECT u.id, d.id, CONCAT('TZA-', LPAD(FLOOR(RAND()*90000)+10000,5,'0')), u.phone
FROM users u
JOIN departments d ON d.facility_id = u.facility_id
WHERE u.email = 'mbagala.family@rms.go.tz' AND d.name = 'Emergency'
  AND NOT EXISTS (SELECT 1 FROM doctors doc WHERE doc.user_id = u.id)
UNION ALL
SELECT u.id, d.id, CONCAT('TZA-', LPAD(FLOOR(RAND()*90000)+10000,5,'0')), u.phone
FROM users u
JOIN departments d ON d.facility_id = u.facility_id
WHERE u.email = 'mbagala.lab@rms.go.tz' AND d.name = 'Laboratory'
  AND NOT EXISTS (SELECT 1 FROM doctors doc WHERE doc.user_id = u.id)
UNION ALL
SELECT u.id, d.id, CONCAT('TZA-', LPAD(FLOOR(RAND()*90000)+10000,5,'0')), u.phone
FROM users u
JOIN departments d ON d.facility_id = u.facility_id
WHERE u.email = 'temeke.er@rms.go.tz' AND d.name = 'Emergency'
  AND NOT EXISTS (SELECT 1 FROM doctors doc WHERE doc.user_id = u.id)
UNION ALL
SELECT u.id, d.id, CONCAT('TZA-', LPAD(FLOOR(RAND()*90000)+10000,5,'0')), u.phone
FROM users u
JOIN departments d ON d.facility_id = u.facility_id
WHERE u.email = 'temeke.ent@rms.go.tz' AND d.name = 'ENT'
  AND NOT EXISTS (SELECT 1 FROM doctors doc WHERE doc.user_id = u.id)
UNION ALL
SELECT u.id, d.id, CONCAT('TZA-', LPAD(FLOOR(RAND()*90000)+10000,5,'0')), u.phone
FROM users u
JOIN departments d ON d.facility_id = u.facility_id
WHERE u.email = 'agakhan.obgyn@rms.go.tz' AND d.name = 'Obstetrics & Gynecology'
  AND NOT EXISTS (SELECT 1 FROM doctors doc WHERE doc.user_id = u.id)
UNION ALL
SELECT u.id, d.id, CONCAT('TZA-', LPAD(FLOOR(RAND()*90000)+10000,5,'0')), u.phone
FROM users u
JOIN departments d ON d.facility_id = u.facility_id
WHERE u.email = 'agakhan.ophthalmology@rms.go.tz' AND d.name = 'Ophthalmology'
  AND NOT EXISTS (SELECT 1 FROM doctors doc WHERE doc.user_id = u.id)
UNION ALL
SELECT u.id, d.id, CONCAT('TZA-', LPAD(FLOOR(RAND()*90000)+10000,5,'0')), u.phone
FROM users u
JOIN departments d ON d.facility_id = u.facility_id
WHERE u.email = 'bmh.surgery@rms.go.tz' AND d.name = 'General Surgery'
  AND NOT EXISTS (SELECT 1 FROM doctors doc WHERE doc.user_id = u.id)
UNION ALL
SELECT u.id, d.id, CONCAT('TZA-', LPAD(FLOOR(RAND()*90000)+10000,5,'0')), u.phone
FROM users u
JOIN departments d ON d.facility_id = u.facility_id
WHERE u.email = 'bmh.icu@rms.go.tz' AND d.name = 'Intensive Care Unit (ICU)'
  AND NOT EXISTS (SELECT 1 FROM doctors doc WHERE doc.user_id = u.id);
