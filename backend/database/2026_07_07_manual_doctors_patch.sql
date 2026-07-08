USE health_db;

START TRANSACTION;

-- Insert users for the three doctors if they don't already exist
INSERT INTO users (email, password, role, first_name, last_name, facility_id, phone)
SELECT 'aymanrwenda@gmail.com','pass123','co','Said','Juma',2,'+255663373544'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'aymanrwenda@gmail.com');

INSERT INTO users (email, password, role, first_name, last_name, facility_id, phone)
SELECT 'abdulkarimmacho@gmail.com','pass123','co','Ben','Musso',7,'+255764343059'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'abdulkarimmacho@gmail.com');

INSERT INTO users (email, password, role, first_name, last_name, facility_id, phone)
SELECT 'aymanmussojr@gmail.com','pass123','co','Revo','Fawzan',11,'+255627179640'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'aymanmussojr@gmail.com');

-- Ensure departments exist for each facility (creates commonly used departments)
INSERT INTO departments (facility_id, name)
SELECT 2, 'Emergency' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM departments WHERE facility_id = 2 AND name = 'Emergency');

INSERT INTO departments (facility_id, name)
SELECT 7, 'General Surgery' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM departments WHERE facility_id = 7 AND name = 'General Surgery');

INSERT INTO departments (facility_id, name)
SELECT 11, 'Internal Medicine' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM departments WHERE facility_id = 11 AND name = 'Internal Medicine');

-- Create doctor records linked to the users and departments (skip if already present)
INSERT INTO doctors (user_id, department_id, license_number, contact_phone)
SELECT u.id, d.id, NULL, u.phone
FROM users u
JOIN departments d ON d.facility_id = 2 AND d.name = 'Emergency'
WHERE u.email = 'aymanrwenda@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM doctors WHERE user_id = u.id);

INSERT INTO doctors (user_id, department_id, license_number, contact_phone)
SELECT u.id, d.id, NULL, u.phone
FROM users u
JOIN departments d ON d.facility_id = 7 AND d.name = 'General Surgery'
WHERE u.email = 'abdulkarimmacho@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM doctors WHERE user_id = u.id);

INSERT INTO doctors (user_id, department_id, license_number, contact_phone)
SELECT u.id, d.id, NULL, u.phone
FROM users u
JOIN departments d ON d.facility_id = 11 AND d.name = 'Internal Medicine'
WHERE u.email = 'aymanmussojr@gmail.com'
  AND NOT EXISTS (SELECT 1 FROM doctors WHERE user_id = u.id);

COMMIT;

-- Quick verification selects (no-op for mysql client output)
SELECT 'PATCH_EXECUTED' AS status;
