-- creating medical record database
CREATE DATABASE IF NOT EXISTS healthcare;
USE healthcare;

-- Patient Info table
CREATE TABLE PATIENT_INFO (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender VARCHAR(20) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    contact_email VARCHAR(100) UNIQUE NOT NULL,
    address VARCHAR(200) NOT NULL,
    emergency_contact_name VARCHAR(100) NOT NULL,
    emergency_contact_number VARCHAR(20) NOT NULL,
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed','Seperated'),
    ethnicity VARCHAR(50) NOT NULL
);

-- Preexisting Medical History table
CREATE TABLE PREEXISTING_MEDICAL_HISTORY (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    conditions TEXT,
    allergies TEXT,
    family_history TEXT,
    surgeries TEXT,
    social_history TEXT,
    activity_level TEXT,
    serious_illnesses TEXT,
    serious_injuries TEXT,
    other_info TEXT,
    last_time_updated DATETIME,
    FOREIGN KEY (patient_id) REFERENCES PATIENT_INFO(patient_id)
);

-- Doctor Info table
CREATE TABLE DOCTOR_INFO (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    contact_email VARCHAR(100) UNIQUE NOT NULL,
    patients_handled_this_year INT DEFAULT 0,
    upcoming_patients INT DEFAULT 0
);

-- Hospital Visits table
CREATE TABLE HOSPITAL_VISITS (
    visit_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    doctor_id INT,
    visit_date DATE,
    visit_reason VARCHAR(200),
    diagnosis VARCHAR(200),
    FOREIGN KEY (patient_id) REFERENCES PATIENT_INFO(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES DOCTOR_INFO(doctor_id)
);

-- Returned Visit Data table
CREATE TABLE RETURNED_VISIT_DATA (
    return_id INT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT,
    patient_id INT,
    doctor_id INT,
    visit_date DATE,
    height DECIMAL(5,2),
    weight DECIMAL(5,2),
    blood_pressure VARCHAR(20),
    heart_rate INT,
    next_checkup_date DATE,
    temperature DECIMAL(4,1),
    new_medicines TEXT,
    respiration_rate INT,
    new_conditions TEXT,
    skin_health ENUM('Worst', 'Worsening', 'Normal', 'Better', 'Best'),
    organ_health ENUM('Worst', 'Worsening', 'Normal', 'Better', 'Best'),
    neurological_health ENUM('Worst', 'Worsening', 'Normal', 'Better', 'Best'),
    urgent_concern TEXT,
    extra_notes TEXT,
    FOREIGN KEY (visit_id) REFERENCES HOSPITAL_VISITS(visit_id),
    FOREIGN KEY (patient_id) REFERENCES PATIENT_INFO(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES DOCTOR_INFO(doctor_id)
);

-- Users table
CREATE TABLE PATIENT_USERS (
    patient_id INT PRIMARY KEY,
    login_email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    is_suspended BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (patient_id) REFERENCES PATIENT_INFO(patient_id)
);

-- Doctors table
CREATE TABLE DOCTOR_USERS (
    doctor_id INT PRIMARY KEY,
    login_email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    is_suspended BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (doctor_id) REFERENCES DOCTOR_INFO(doctor_id)
);

-- PATIENT_MEDICATIONS table for storing medications -- works better with 'edit info' functionality vs having to update medication text
CREATE TABLE PATIENT_MEDICATIONS (
    med_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    medication_name VARCHAR(100) NOT NULL,
    start_date DATE,
    end_date DATE,
    dosage VARCHAR(50),
    FOREIGN KEY (patient_id) REFERENCES PATIENT_INFO(patient_id)
);

CREATE TABLE APPOINTMENTS (
	appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE,
    appointment_time TIME,
    FOREIGN KEY (patient_id) REFERENCES PATIENT_INFO(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES DOCTOR_INFO(doctor_id)
);

-- sample patient data
INSERT INTO PATIENT_INFO (first_name, last_name, date_of_birth, gender, phone_number, contact_email, address, emergency_contact_name, emergency_contact_number, marital_status, ethnicity)
VALUES
('nat', 'pg', '1973-06-01', 'Female', '111-1111', 'nat@example.com', '1 Commerce Street', 'Bob null', '222-222', 'Single', 'Hispanic'),
('Bob', 'null', '1969-08-10', 'Male', '222-2222', 'bob@example.com', '2 Main Street', 'Alice null', '555-6789', 'Married', 'Hispanic'),
('Rodge', 'Dodger', '1969-08-10', 'Male', '555-5555', 'rodgerdodger@example.com', '2 Main Street', 'Alice null', '555-6789', 'Married', 'Hispanic');

-- sample preexisting medical data
INSERT INTO PREEXISTING_MEDICAL_HISTORY 
(patient_id, conditions, allergies, family_history, surgeries, social_history, activity_level, serious_illnesses, serious_injuries, other_info, last_time_updated)
VALUES
(1, NULL, NULL, NULL, NULL, 'non-smoker, occasional alcohol', 'Moderate', 'None', NULL, NULL, NOW()),
(2, 'Diabetes', NULL, 'Heart disease', NULL, ' heavy alcohol use', 'Active', NULL, 'Fractured collarbone (2016)', NULL, NOW());

-- sample doctor logins
INSERT INTO DOCTOR_INFO (first_name, last_name, contact_email, patients_handled_this_year, upcoming_patients)
VALUES
('Dr. Guadalupe', 'Daniels', 'g@hospital.com', 120, 10);

-- sample hospital visit data
INSERT INTO HOSPITAL_VISITS (patient_id, doctor_id, visit_date, visit_reason, diagnosis)
VALUES
(1, 1, '2025-09-10', 'Headache and fatigue', 'Migraine'),
(2, 1, '2016-05-01', 'Chest pain', 'Heart disease');

-- sample returned visit data
INSERT INTO RETURNED_VISIT_DATA (visit_id, patient_id, doctor_id, visit_date, height, weight, blood_pressure, heart_rate, next_checkup_date, temperature, new_medicines, respiration_rate, new_conditions, skin_health, organ_health, neurological_health, urgent_concern, extra_notes)
VALUES
(1, 1, 1, '2025-06-10', 160.0, 100.5, '120/80', 72, '2026-02-15', 37.2, 'Tylenol', 16, 'None', 'Worsening', 'Normal', 'Normal', 'No', 'Patient is not sleeping well'),
(2, 2, 1, '2025-07-10', 180.0, 140.0, '130/85', 78, '2026-03-20', 36.9, 'Lisinopril', 18, 'None', 'Normal', 'Normal', 'Normal', 'No', 'Patient is losing weight');

-- sample users
INSERT INTO PATIENT_USERS (patient_id, login_email, password_hash)
VALUES
(1, 'nat@example.com', '$2y$10$R5PT5z/dx4ZcjXhpnU18q.KleKbhHcqlwsuRvNUMaaQdmmD7/pecO'), -- boogers
(2, 'bob@example.com', '$2y$10$cTIVwBRrIRZF5tJ3tRqqn.sS0iZ6NUNMdoE4Wieh9ziAP/LKjw62a'), -- stars  
(3, 'rodgerdodger@example.com', '$2y$10$SzKBFOhKIo9kJPwYmejhSe14tmp3H3Ab2Cw4CisHAwnr0G7M34VIi'); -- jonjonjon

-- sample doctors
INSERT INTO DOCTOR_USERS (doctor_id, login_email, password_hash)
VALUES
(1, 'g@hospital.com', '$2y$10$eXWnh0znAG0i7xSQrxR0kuvEbYUwZlnygTMF.IxiOnayRJ.RkU0XO'); -- doctor123

-- sample medication data
INSERT INTO PATIENT_MEDICATIONS (patient_id, medication_name, start_date, dosage)
VALUES
(1, 'Tylenol', '2025-01-01', '500mg daily'),
(2, 'Lisinopril', '2023-12-25', '10mg daily');

-- ADMIN_USERS table for system admins
CREATE TABLE ADMIN_USERS (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- ACTIVITY_LOG table for tracking activities
CREATE TABLE ACTIVITY_LOG (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_role ENUM('patient', 'doctor', 'admin') NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    action_description TEXT NOT NULL,
    table_affected VARCHAR(50),
    record_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_action_type (action_type)
);

-- sample admin data
INSERT INTO ADMIN_USERS (username, password_hash, email, full_name)
VALUES ('hcadmin', '$2y$12$z0Sr9dwXFkE7XP4D7ptllOQNdEn8ST6fQypeGDjxUNOqImAuGOlnm', 'admin@healthcare.com', 'Healthcare Administrator'); -- password is 'changeme'
