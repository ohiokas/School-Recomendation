# SMAKITA

Native PHP 8+ / MySQL 8 recommendation system for Jakarta SMP students exploring public SMA and SMK pathways.

## Included now

- Secure registration, login, session regeneration, CSRF tokens, and password hashing.
- Profile with backend and frontend age calculation.
- Eight-indicator interest quiz with normalized 0-100 scores.
- Gaussian Naive Bayes implementation in `ml/GaussianNaiveBayes.php`.
- Configurable scoring helpers for academics, achievements, organization, and percentile.
- Explainable school ranking using the supplied `SMKSMA404.csv` (206 source rows; semicolon-delimited).
- MySQL schema covering users, students, quiz, scores, schools, majors, datasets, models, recommendations, settings, and audit logs.
- Admin panel with full CRUD for schools and students, modern UI design.
- Admission path selection with dynamic scoring formulas.
- Passion-based school matching with competency analysis.

## Setup

1. Copy the folder into `C:\xampp\htdocs`.
2. Create `config/config.php` values for the local MySQL account if needed.
3. Import `database/database.sql`, then `database/seed.sql` in phpMyAdmin or MySQL CLI.
4. Run `database/migration_v3.sql` for admission path and school accreditation fields.
5. Run `database/quiz_questions_expanded.sql` for expanded quiz questions.

## Admin Login

- **Email:** admin@smakita.local
- **Password:** smakita123

## Recommendation Method

Schools are ranked using **Weighted Scoring**:
- **55%** Student score (based on admission path formula)
- **20%** Passion match (competency alignment with student interests)
- **20%** Average compatibility (student score vs school admission average)
- **5%** Domicile match (same city = 100%, different city = 35%)

### Passion Match vs Limited Match

- **Passion Match (≥60%):** School competencies strongly align with student's passion profile
- **Limited Match (<60%):** Limited alignment between school competencies and student interests

### Admission Path Scoring

Different admission paths use different weight distributions:
- **Prestasi Akademik:** 40% academic, 25% achievements, 5% non-academic, 20% percentile, 10% organization
- **Afirmasi/Zonasi/Tahap Kedua SMA:** 75% subject scores, 25% percentile
- **Prestasi Non-Akademik:** 20% academic, 50% non-academic, 5% academic achievements, 20% organization, 5% percentile

## Usage

1. Open `http://localhost/bleszsme/`.
2. Register a student and complete the profile, quiz, and academic steps.
3. Access admin panel at `http://localhost/bleszsme/admin/` using the admin credentials.
4. Import school data from SMKSMA404.csv via admin panel.

## Security

All SQL in application code uses PDO prepared statements. Escape rendered user values with `e()`, keep uploads outside executable PHP directories, validate MIME and size before accepting files, and set a non-empty database password outside local development.
