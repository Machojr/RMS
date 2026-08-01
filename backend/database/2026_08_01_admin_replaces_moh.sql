-- Admin now owns national MoH oversight responsibilities.
-- Run once after backing up the database.

ALTER TABLE users
    MODIFY role ENUM('admin','co','receptionist','moh','super_admin') NOT NULL;

UPDATE users SET role = 'admin' WHERE role IN ('moh', 'super_admin');

ALTER TABLE users
    MODIFY role ENUM('admin','co','receptionist') NOT NULL;
