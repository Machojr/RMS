UPDATE users SET email='aymanrwenda@gmail.com' WHERE id=23;
UPDATE users SET email='abdulkarimmacho@gmail.com' WHERE id=24;
UPDATE users SET email='aymanmussojr@gmail.com' WHERE id=25;

SELECT 
  id,
  CONCAT(first_name, ' ', last_name) AS name,
  email,
  phone,
  facility_id
FROM users 
WHERE id IN (23, 24, 25)
ORDER BY first_name;

SELECT 
  u.id,
  CONCAT(u.first_name, ' ', u.last_name) AS doctor_name,
  u.email,
  u.phone,
  d.contact_phone,
  dep.name AS department_name,
  f.name AS facility_name
FROM users u
JOIN doctors d ON u.id = d.user_id
JOIN departments dep ON d.department_id = dep.id
JOIN facilities f ON u.facility_id = f.id
WHERE u.id IN (23, 24, 25)
ORDER BY f.name;
