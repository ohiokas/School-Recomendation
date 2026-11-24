USE sma_kita;
CREATE TABLE IF NOT EXISTS passion_professions (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 profession VARCHAR(180) NOT NULL UNIQUE,
 indicator VARCHAR(60) NOT NULL,
 description VARCHAR(255) NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO passion_professions(profession,indicator,description) VALUES('Actor / Actress','Linguistic','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Actuary','Logical - Mathematical','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Anthropologist','Spatial-Visualization','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Archeologist','Naturalist','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Artist','Musical','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Astronomer','Naturalist','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Athlete','Intrapersonal','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Audiologist','Logical - Mathematical','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Broadcaster','Linguistic','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Business Analyst','Interpersonal','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Business manager','Logical - Mathematical','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Chief financial officer','Interpersonal','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Computer programmer','Linguistic','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Dancer','Musical','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Editor','Logical - Mathematical','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Engineer','Spatial-Visualization','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Geologist','Naturalist','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Graphic Designer','Spatial-Visualization','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Historian','Intrapersonal','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Interior Decorator','Interpersonal','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Internal auditor','Intrapersonal','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Journalist','Linguistic','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Lawyer','Logical - Mathematical','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Leader','Intrapersonal','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Manager','Logical - Mathematical','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Marine Biologist','Logical - Mathematical','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Marketing','Linguistic','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Mathematician','Spatial-Visualization','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Mechanic','Bodily','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Medical','Interpersonal','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Militry','Interpersonal','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Music teacher','Interpersonal','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Nature photographer','Naturalist','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Para Medical (physiotherapy, occupational theropy, audio and speech language theropy, nursing)','Interpersonal','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Para Militry (https://en.wikipedia.org/wiki/Paramilitary_forces_of_India)','Bodily','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Pharmacist','Interpersonal','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Physical Therapist','Naturalist','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Physician','Interpersonal','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Physicist','Logical - Mathematical','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Pilot','Naturalist','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Poet','Musical','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Police Force (Spies, CBI officials, CID, Detectives)','Interpersonal','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Politician','Logical - Mathematical','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Pre Primary Teacher (2020 NEP and Mental Health)','Bodily','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Primary Teacher','Musical','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Psychologist','Logical - Mathematical','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Receptionist','Interpersonal','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Recording engineer','Interpersonal','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Research analyst','Linguistic','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Sales Representative','Logical - Mathematical','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Social Worker','Bodily','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Sound editor','Naturalist','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Stock Broker','Intrapersonal','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Veterinarian','Intrapersonal','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
INSERT INTO passion_professions(profession,indicator,description) VALUES('Writer','Linguistic','Profesi yang muncul pada Dataset Project 404') ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),is_active=1;
