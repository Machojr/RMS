USE health_db;

-- Create departments table per facility
CREATE TABLE IF NOT EXISTS departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    facility_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id)
);

-- Create doctors table linked to users (CO role users)
CREATE TABLE IF NOT EXISTS doctors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    department_id INT NULL,
    license_number VARCHAR(100),
    contact_phone VARCHAR(30),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- Extend referrals with receiving_department_id and assigned_doctor_id
ALTER TABLE referrals
    ADD COLUMN receiving_department_id INT NULL AFTER receiving_facility_id,
    ADD COLUMN assigned_doctor_id INT NULL AFTER receiving_department_id;

ALTER TABLE referrals
    ADD FOREIGN KEY (receiving_department_id) REFERENCES departments(id),
    ADD FOREIGN KEY (assigned_doctor_id) REFERENCES doctors(id);

-- Add indexes for faster lookups
CREATE INDEX idx_departments_facility ON departments(facility_id);
CREATE INDEX idx_doctors_user ON doctors(user_id);
CREATE INDEX idx_referrals_receiving_dept ON referrals(receiving_department_id);
CREATE INDEX idx_referrals_assigned_doctor ON referrals(assigned_doctor_id);
