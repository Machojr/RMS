-- First, delete from doctors table (where foreign key is)
DELETE FROM doctors WHERE user_id IN (
  SELECT id FROM users WHERE email='aymanrwenda@gmail.com' AND id != 23
);
DELETE FROM doctors WHERE user_id IN (
  SELECT id FROM users WHERE email='abdulkarimmacho@gmail.com' AND id != 24
);
DELETE FROM doctors WHERE user_id IN (
  SELECT id FROM users WHERE email='aymanmussojr@gmail.com' AND id != 25
);

-- Then delete old duplicate users
DELETE FROM users WHERE email='aymanrwenda@gmail.com' AND id != 23;
DELETE FROM users WHERE email='abdulkarimmacho@gmail.com' AND id != 24;
DELETE FROM users WHERE email='aymanmussojr@gmail.com' AND id != 25;

-- Update the three doctors with correct emails
UPDATE users SET email='aymanrwenda@gmail.com' WHERE id=23;
UPDATE users SET email='abdulkarimmacho@gmail.com' WHERE id=24;
UPDATE users SET email='aymanmussojr@gmail.com' WHERE id=25;

-- Re-add the doctor records to the three doctors
INSERT INTO doctors (user_id, department_id, license_number, contact_phone)
SELECT 23, (SELECT id FROM departments WHERE facility_id=2 AND name='Emergency' LIMIT 1), 'LIC-SJ-2026', '0663373544'
WHERE NOT EXISTS (SELECT 1 FROM doctors WHERE user_id=23);

INSERT INTO doctors (user_id, department_id, license_number, contact_phone)
SELECT 24, (SELECT id FROM departments WHERE facility_id=7 AND name='General Surgery' LIMIT 1), 'LIC-BM-2026', '0764343059'
WHERE NOT EXISTS (SELECT 1 FROM doctors WHERE user_id=24);

INSERT INTO doctors (user_id, department_id, license_number, contact_phone)
SELECT 25, (SELECT id FROM departments WHERE facility_id=11 AND name='Internal Medicine' LIMIT 1), 'LIC-RF-2026', '0627179640'
WHERE NOT EXISTS (SELECT 1 FROM doctors WHERE user_id=25);

-- Final Verification
SELECT 
  u.id,
  u.email,
  u.first_name,
  u.last_name,
  u.phone,
  d.contact_phone,
  dep.name AS department_name,
  f.name AS facility_name
FROM users u
LEFT JOIN doctors d ON u.id = d.user_id
LEFT JOIN departments dep ON d.department_id = dep.id
LEFT JOIN facilities f ON u.facility_id = f.id
WHERE u.id IN (23, 24, 25)
ORDER BY u.id;
