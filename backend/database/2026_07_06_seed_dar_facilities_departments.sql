USE health_db;

-- Remove facilities outside Dar es Salaam
DELETE FROM facilities WHERE region <> 'Dar es Salaam';

-- Insert additional Dar es Salaam facilities (if not already present by name)
INSERT INTO facilities (name, tier, region, district, address, phone, email, capacity)
SELECT * FROM (
    SELECT 'Temeke District Hospital' AS name, 'district_hospital' AS tier, 'Dar es Salaam' AS region, 'Temeke' AS district, 'Temeke, Dar es Salaam' AS address, '+255 22 2xxxxxx' AS phone, 'info@temeke.go.tz' AS email, 300 AS capacity
    UNION ALL
    SELECT 'Aga Khan Hospital Dar es Salaam', 'regional_hospital', 'Dar es Salaam', 'Ilala', 'Kivukoni / Masaki area', '+255 22 2xxxxxx', 'info@agakhandc.org', 200
    UNION ALL
    SELECT 'Benjamin Mkapa Hospital', 'regional_hospital', 'Dar es Salaam', 'Kinondoni', 'Kinondoni, Dar es Salaam', '+255 22 2xxxxxx', 'info@bmh.go.tz', 250
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM facilities f WHERE f.name = tmp.name AND f.region = tmp.region AND f.district = tmp.district);

-- Standard departments to add for each Dar facility
SET @departments = 'Emergency,Internal Medicine,General Surgery,Pediatrics,Obstetrics & Gynecology,Orthopedics,Radiology,Laboratory,Intensive Care Unit (ICU),Pharmacy,ENT,Ophthalmology,Dental';

-- For each facility in Dar es Salaam, insert the departments if they don't exist
DELIMITER $$
CREATE PROCEDURE seed_departments_for_dar()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE fid INT;
    DECLARE cur CURSOR FOR SELECT id FROM facilities WHERE region = 'Dar es Salaam';
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO fid;
        IF done THEN
            LEAVE read_loop;
        END IF;
        -- Insert departments for this facility
        INSERT INTO departments (facility_id, name)
        SELECT fid, dept_name FROM (
            SELECT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(@departments, ',', numbers.n), ',', -1)) AS dept_name
            FROM (
                SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13
            ) numbers
        ) d
        WHERE dept_name <> ''
        AND NOT EXISTS (
            SELECT 1 FROM departments dp WHERE dp.facility_id = fid AND dp.name = TRIM(d.dept_name)
        );
    END LOOP;
    CLOSE cur;
END$$
DELIMITER ;

CALL seed_departments_for_dar();
DROP PROCEDURE IF EXISTS seed_departments_for_dar;

-- Quick verification selects (for manual check)
SELECT id, name, region, district FROM facilities ORDER BY id;
SELECT id, facility_id, name FROM departments ORDER BY facility_id, id;
