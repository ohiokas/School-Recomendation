<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../recommendation/Scoring.php';

require_login();

if ((current_user()['role'] ?? '') !== 'admin') {
    redirect('dashboard.php');
}

require_once __DIR__ . '/../vendor/autoload.php';

try {

    require_once __DIR__ . '/../vendor/autoload.php';

    $exportStudents = db()->query("
            SELECT
                u.id,
                u.full_name,
                u.email,
                u.created_at,
                s.domicile,
                s.previous_school,
                s.education_preference,
                s.admission_path,

                (
                    SELECT i.indicator
                    FROM interest_scores i
                    WHERE i.student_id = s.id
                    ORDER BY i.score DESC, i.id ASC
                    LIMIT 1
                ) AS focus_interest,

                (
                    SELECT i.score
                    FROM interest_scores i
                    WHERE i.student_id = s.id
                    ORDER BY i.score DESC, i.id ASC
                    LIMIT 1
                ) AS focus_interest_percentage,

                a.science,
                a.social_studies,
                a.indonesian,
                a.english,
                a.mathematics,
                a.civics,
                a.academic_average,

                (
                    SELECT GROUP_CONCAT(
                        CONCAT(
                            rd.rank_number,
                            '. ',
                            COALESCE(sd.name, 'School not found'),
                            ' (',
                            FORMAT(rd.score, 2),
                            ')'
                        )
                        ORDER BY rd.rank_number ASC
                        SEPARATOR ' | '
                    )
                    FROM recommendation_details rd
                    INNER JOIN school_data sd ON sd.id = rd.school_id
                    WHERE rd.recommendation_id = (
                        SELECT r.id
                        FROM recommendations r
                        WHERE r.student_id = s.id
                        ORDER BY r.created_at DESC, r.id DESC
                        LIMIT 1
                    )
                    AND rd.rank_number <= 5
                ) AS school_recommendations

            FROM users u
            LEFT JOIN students s ON s.user_id = u.id
            LEFT JOIN academic_scores a ON a.student_id = s.id
            WHERE u.role = 'student'
            ORDER BY u.created_at DESC
        ")->fetchAll();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Student Data');

        /*
        |--------------------------------------------------------------------------
        | HEADER EXCEL
        |--------------------------------------------------------------------------
        */

        $headers = [
            'No',
            'Name',
            'Email',
            'Domicile',
            'Education Preference',
            'Focus Interest',
            'Interest Percentage',
            'IPA',
            'IPS',
            'Bahasa Indonesia',
            'Bahasa Inggris',
            'Matematika',
            'Pendidikan Pancasila',
            'Academic Average',
            'School Recommendations (Top 5)',
            'Admission Path',
            'Previous School',
            'Registered'
        ];

        $sheet->fromArray(
            $headers,
            null,
            'A1'
        );

        /*
        |--------------------------------------------------------------------------
        | DATA EXCEL
        |--------------------------------------------------------------------------
        */

        $rowNumber = 2;
        $number = 1;

        foreach ($exportStudents as $student) {

            $admissionPath = normalizeAdmissionPath(
                (string)($student['admission_path'] ?? '')
            );

            $educationPreference = $student['education_preference'] ?? 'undecided';

            $focusInterest = $student['focus_interest'] ?? null;

            $focusPercentage = $student['focus_interest_percentage'] ?? null;

            $sheet->fromArray(
                [
                    $number++,
                    $student['full_name'] ?? '',
                    $student['email'] ?? '',
                    $student['domicile'] ?? 'Not set',
                    ucfirst($educationPreference),
                    $focusInterest ?? 'Not set',
                    $focusPercentage !== null ? (float)$focusPercentage : null,
                    $student['science'] !== null ? (float)$student['science'] : null,
                    $student['social_studies'] !== null ? (float)$student['social_studies'] : null,
                    $student['indonesian'] !== null ? (float)$student['indonesian'] : null,
                    $student['english'] !== null ? (float)$student['english'] : null,
                    $student['mathematics'] !== null ? (float)$student['mathematics'] : null,
                    $student['civics'] !== null ? (float)$student['civics'] : null,
                    $student['academic_average'] !== null ? (float)$student['academic_average'] : null,
                    $student['school_recommendations'] ?? 'Not available',
                    ucfirst(
                        str_replace(
                            '_',
                            ' ',
                            $admissionPath !== '' ? $admissionPath : 'Not set'
                        )
                    ),
                    $student['previous_school'] ?? '-',
                    $student['created_at'] ?? ''
                ],
                null,
                'A' . $rowNumber
            );

            $rowNumber++;
        }

        /*
        |--------------------------------------------------------------------------
        | EXCEL FORMATTING
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle('A1:R1')
            ->getFont()
            ->setBold(true);

        $sheet->getStyle('A1:R1')
            ->getAlignment()
            ->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );

        $sheet->freezePane('A2');

        foreach (range('A', 'R') as $column) {
            $sheet->getColumnDimension($column)
                ->setAutoSize(true);
        }

        /*
        |--------------------------------------------------------------------------
        | FORMAT PERSENTASE
        |--------------------------------------------------------------------------
        */

        if ($rowNumber > 2) {
            $sheet->getStyle('G2:G' . ($rowNumber - 1))
                ->getNumberFormat()
                ->setFormatCode('0.00"%"');
        }

        /*
        |--------------------------------------------------------------------------
        | DOWNLOAD FILE
        |--------------------------------------------------------------------------
        */

        $filename = 'student_data_' . date('Y-m-d_H-i-s') . '.xlsx';

        header(
            'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        header(
            'Content-Disposition: attachment; filename="' . $filename . '"'
        );

        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx(
            $spreadsheet
        );

        $writer->save('php://output');

        exit;

    } catch (Throwable $exception) {

        $error = 'Error exporting student data: ' . $exception->getMessage();
    }
