USE smakkita;

-- Keep route identifiers extensible; older migration_v3 used an incompatible ENUM.
ALTER TABLE students
MODIFY COLUMN admission_path VARCHAR(40) NULL DEFAULT NULL;