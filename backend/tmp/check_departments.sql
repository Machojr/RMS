SELECT id, name FROM facilities WHERE id IN (2, 7, 11);

SELECT COUNT(*) AS dept_count, facility_id FROM departments WHERE facility_id IN (2,7,11) GROUP BY facility_id;

SELECT id, name, facility_id FROM departments WHERE facility_id IN (2,7,11) ORDER BY facility_id, id;
