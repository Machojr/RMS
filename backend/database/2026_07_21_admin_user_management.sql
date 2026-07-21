ALTER TABLE users
    MODIFY role ENUM('admin','co','receptionist','moh') NOT NULL;
