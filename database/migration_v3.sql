-- Migration v3: Add admission path and school accreditation fields
USE smakkita;

-- Add admission_path field to students table
ALTER TABLE students 
ADD COLUMN admission_path ENUM('prestasi_akademik_sma', 'prestasi_akademik_smk', 'afirmasi', 'zonasi', 'prestasi_non_akademik_sma', 'prestasi_non_akademik_smk', 'tahap_kedua_sma', 'tahap_kedua_smk') NULL DEFAULT NULL AFTER education_preference;

-- Add accreditation and capacity fields to school_data table
ALTER TABLE school_data 
ADD COLUMN accreditation VARCHAR(10) NULL DEFAULT NULL AFTER average_score,
ADD COLUMN capacity INT UNSIGNED NULL DEFAULT NULL AFTER accreditation,
ADD COLUMN accreditation_status ENUM('A', 'B', 'C', 'Unggul', 'Baik', 'Cukup') NULL DEFAULT NULL AFTER accreditation;

-- Create passion_professions table if not exists
CREATE TABLE IF NOT EXISTS passion_professions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profession VARCHAR(180) NOT NULL,
    indicator VARCHAR(60) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_indicator(indicator)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
