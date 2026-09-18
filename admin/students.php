<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../recommendation/Scoring.php';

require_login();

if ((current_user()['role'] ?? '') !== 'admin') {
    redirect('dashboard.php');
}

$message = '';
$error = '';
$editing = null;

/*
|--------------------------------------------------------------------------
| EXPORT STUDENT DATA TO EXCEL
|--------------------------------------------------------------------------
*/
if (isset($_GET['export']) && $_GET['export'] === 'excel') {

    require_once __DIR__ . '/../vendor/autoload.php';

    try {

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
}


/*
|--------------------------------------------------------------------------
| POST ACTION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && verify_csrf($_POST['csrf'] ?? null)
) {

    $action = $_POST['action'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | DELETE STUDENT
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete') {

        $id = (int)($_POST['user_id'] ?? 0);

        if (
            $id > 0
            && $id !== (int)current_user()['id']
        ) {

            try {

                db()->prepare(
                    "DELETE FROM users
                     WHERE id = ?
                     AND role = 'student'"
                )->execute([$id]);

                $message = 'Student deleted successfully.';

            } catch (Throwable $exception) {

                $error =
                    'Error deleting student: '
                    . $exception->getMessage();
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT STUDENT
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'edit') {

        try {

            $userId = (int)($_POST['user_id'] ?? 0);

            $fullName = trim(
                $_POST['full_name'] ?? ''
            );

            $email = trim(
                $_POST['email'] ?? ''
            );

            $domicile = trim(
                $_POST['domicile'] ?? ''
            );

            $previousSchool = trim(
                $_POST['previous_school'] ?? ''
            );

            $educationPreference =
                $_POST['education_preference']
                ?? 'undecided';

            $admissionPath =
                $_POST['admission_path']
                ?? null;


            /*
            | Update users
            */

            db()->prepare(
                'UPDATE users
                 SET full_name = ?, email = ?
                 WHERE id = ?'
            )->execute(
                [
                    $fullName,
                    $email,
                    $userId
                ]
            );


            /*
            | Update students
            */

            db()->prepare(
                'UPDATE students
                 SET
                    domicile = ?,
                    previous_school = ?,
                    education_preference = ?,
                    admission_path = ?
                 WHERE user_id = ?'
            )->execute(
                [
                    $domicile,
                    $previousSchool,
                    $educationPreference,
                    $admissionPath,
                    $userId
                ]
            );

            $message = 'Student updated successfully.';

        } catch (Throwable $exception) {

            $error =
                'Error updating student: '
                . $exception->getMessage();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | OPEN EDIT FORM
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'edit_form') {

        $editing = db()->prepare(
            'SELECT
                u.*,
                s.*
             FROM users u
             LEFT JOIN students s
                ON s.user_id = u.id
             WHERE u.id = ?'
        );

        $editing->execute(
            [
                (int)($_POST['user_id'] ?? 0)
            ]
        );

        $editing = $editing->fetch();
    }
}


/*
|--------------------------------------------------------------------------
| GET STUDENT DATA
|--------------------------------------------------------------------------
|
| Fokus minat:
| mengambil indikator dengan score tertinggi
| dari interest_scores untuk setiap siswa.
|
*/

$students = db()->query("
    SELECT
        u.id,
        u.full_name,
        u.email,
        u.created_at,

        s.id AS student_id,
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

        a.academic_average,

        (
            SELECT GROUP_CONCAT(
                CONCAT(
                    rd.rank_number,
                    '. ',
                    COALESCE(sd.name, 'School not found'),
                    ' — ',
                    FORMAT(rd.score, 2)
                )
                ORDER BY rd.rank_number ASC
                SEPARATOR '<br>'
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


$title = 'Student Management';

require __DIR__ . '/../includes/header.php';

?>

<style>

.student-crud-header {
    background: linear-gradient(
        135deg,
        #f093fb 0%,
        #f5576c 100%
    );

    color: white;

    padding: 2rem;

    border-radius: 16px;

    margin-bottom: 2rem;
}


.crud-card {
    background: white;

    border-radius: 12px;

    padding: 1.5rem;

    box-shadow:
        0 2px 15px rgba(0,0,0,0.08);

    margin-bottom: 1rem;
}


.btn-action {
    padding: 0.4rem 0.8rem;

    font-size: 0.85rem;

    border-radius: 8px;
}


.focus-interest-badge {
    white-space: nowrap;
}


.student-table th {
    white-space: nowrap;
}


.student-table td {
    vertical-align: middle;
}

</style>


<div class="container py-5">


    <!-- =========================================================
         HEADER
    ========================================================== -->

    <div class="student-crud-header">

        <div class="row align-items-center">

            <div class="col-lg-8">

                <div
                    class="eyebrow mb-2"
                    style="color: rgba(255,255,255,0.8);"
                >
                    Admin · Student Management
                </div>

                <h1
                    class="mb-0"
                    style="
                        font-size: 2rem;
                        font-weight: 700;
                    "
                >
                    Student Database Control
                </h1>

                <p
                    class="mb-0 mt-2"
                    style="opacity: 0.9;"
                >
                    View and manage student accounts and their profiles
                </p>

            </div>


            <div class="col-lg-4 text-lg-end">

                <div class="d-flex gap-2 justify-content-lg-end">

                    <span class="badge bg-light text-dark">
                        <?= count($students) ?>
                        Total Students
                    </span>

                </div>

            </div>

        </div>

    </div>


    <!-- =========================================================
         MESSAGE
    ========================================================== -->

    <?php if ($message): ?>

        <div class="alert alert-success">
            <?= e($message) ?>
        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <!-- =========================================================
         STUDENT TABLE
    ========================================================== -->

    <div class="crud-card">


        <div
            class="
                d-flex
                justify-content-between
                align-items-center
                mb-3
                flex-wrap
                gap-2
            "
        >

            <h4 class="mb-0">

                <i class="bi bi-people"></i>

                Registered Students
                (<?= count($students) ?>)

            </h4>


            <!-- EXPORT EXCEL -->

            <a
                href="<?= e(APP_URL) ?>/admin/student.php?export=excel"
                class="btn btn-success rounded-pill"
            >

                <i class="bi bi-file-earmark-excel"></i>

                Export Excel

            </a>

        </div>


        <div class="table-responsive">


            <table
                class="table align-middle student-table"
            >


                <thead>

                    <tr>

                        <th>Name</th>

                        <th>Email</th>

                        <th>Domicile</th>

                        <th>Education</th>

                        <th>Focus Interest</th>

                        <th>Percentage</th>

                        <th>Academic Average</th>

                        <th>School Recommendations</th>

                        <th>Admission Path</th>

                        <th>Previous School</th>

                        <th>Registered</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>


                    <?php foreach ($students as $student): ?>

                        <tr>


                            <!-- NAME -->

                            <td>

                                <strong>
                                    <?= e(
                                        $student['full_name']
                                    ) ?>
                                </strong>

                            </td>


                            <!-- EMAIL -->

                            <td>

                                <?= e(
                                    $student['email']
                                ) ?>

                            </td>


                            <!-- DOMICILE -->

                            <td>

                                <?= e(
                                    $student['domicile']
                                    ?? 'Not set'
                                ) ?>

                            </td>


                            <!-- EDUCATION -->

                            <td>

                                <span
                                    class="
                                        badge
                                        bg-<?=
                                            $student[
                                                'education_preference'
                                            ] === 'sma'
                                                ? 'primary'
                                                : (
                                                    $student[
                                                        'education_preference'
                                                    ] === 'smk'
                                                        ? 'success'
                                                        : 'secondary'
                                                )
                                    ?>
                                "
                                >

                                    <?= e(
                                        ucfirst(
                                            $student[
                                                'education_preference'
                                            ]
                                            ?? 'undecided'
                                        )
                                    ) ?>

                                </span>

                            </td>


                            <!-- FOCUS INTEREST -->

                            <td>

                                <?php if (
                                    !empty(
                                        $student['focus_interest']
                                    )
                                ): ?>

                                    <span
                                        class="
                                            badge
                                            bg-info
                                            text-dark
                                            focus-interest-badge
                                        "
                                    >

                                        <?= e(
                                            $student[
                                                'focus_interest'
                                            ]
                                        ) ?>

                                    </span>

                                <?php else: ?>

                                    <span class="text-muted">
                                        Not set
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- PERCENTAGE -->

                            <td>

                                <?php if (
                                    $student[
                                        'focus_interest_percentage'
                                    ] !== null
                                ): ?>

                                    <strong>

                                        <?= e(
                                            number_format(
                                                (float)$student[
                                                    'focus_interest_percentage'
                                                ],
                                                2
                                            )
                                        ) ?>%

                                    </strong>

                                <?php else: ?>

                                    <span class="text-muted">
                                        Not set
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ACADEMIC AVERAGE -->

                            <td>
                                <?php if ($student['academic_average'] !== null): ?>
                                    <strong>
                                        <?= e(
                                            number_format(
                                                (float)$student['academic_average'],
                                                2
                                            )
                                        ) ?>
                                    </strong>
                                <?php else: ?>
                                    <span class="text-muted">Not set</span>
                                <?php endif; ?>
                            </td>


                            <!-- SCHOOL RECOMMENDATIONS -->

                            <td>
                                <?php if (!empty($student['school_recommendations'])): ?>
                                    <div class="small" style="min-width: 280px;">
                                        <?= $student['school_recommendations'] ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Not available</span>
                                <?php endif; ?>
                            </td>


                            <!-- ADMISSION PATH -->

                            <td>

                                <?php

                                $sRoute =
                                    normalizeAdmissionPath(
                                        (string)(
                                            $student[
                                                'admission_path'
                                            ]
                                            ?? ''
                                        )
                                    );

                                echo e(
                                    ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $sRoute !== ''
                                                ? $sRoute
                                                : 'Not set'
                                        )
                                    )
                                );

                                ?>

                            </td>


                            <!-- PREVIOUS SCHOOL -->

                            <td>

                                <?= e(
                                    $student[
                                        'previous_school'
                                    ]
                                    ?? '-'
                                ) ?>

                            </td>


                            <!-- REGISTERED -->

                            <td>

                                <?= e(
                                    $student[
                                        'created_at'
                                    ]
                                ) ?>

                            </td>


                            <!-- ACTIONS -->

                            <td>

                                <!-- EDIT -->

                                <form
                                    method="post"
                                    class="d-inline"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf"
                                        value="<?= e(
                                            csrf_token()
                                        ) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="edit_form"
                                    >

                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?= e(
                                            $student['id']
                                        ) ?>"
                                    >

                                    <button
                                        class="
                                            btn
                                            btn-sm
                                            btn-outline-primary
                                            btn-action
                                        "
                                        title="Edit Student"
                                    >

                                        <i
                                            class="
                                                bi
                                                bi-pencil
                                            "
                                        ></i>

                                    </button>

                                </form>


                                <!-- DELETE -->

                                <form
                                    method="post"
                                    class="d-inline"
                                    onsubmit="
                                        return confirm(
                                            'Delete this student and all their data?'
                                        )
                                    "
                                >

                                    <input
                                        type="hidden"
                                        name="csrf"
                                        value="<?= e(
                                            csrf_token()
                                        ) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >

                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?= e(
                                            $student['id']
                                        ) ?>"
                                    >

                                    <button
                                        class="
                                            btn
                                            btn-sm
                                            btn-outline-danger
                                            btn-action
                                        "
                                        title="Delete Student"
                                    >

                                        <i
                                            class="
                                                bi
                                                bi-trash
                                            "
                                        ></i>

                                    </button>

                                </form>

                            </td>


                        </tr>

                    <?php endforeach; ?>


                    <?php if (!$students): ?>

                        <tr>

                            <td
                                colspan="12"
                                class="
                                    text-center
                                    text-muted
                                "
                            >

                                No student accounts yet.

                            </td>

                        </tr>

                    <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>

</div>


<!-- =========================================================
     EDIT MODAL
========================================================== -->

<div
    class="modal fade"
    id="editModal"
    tabindex="-1"
>

    <div class="modal-dialog modal-lg">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">
                    Edit Student Profile
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <form method="post">


                <div class="modal-body">


                    <input
                        type="hidden"
                        name="csrf"
                        value="<?= e(
                            csrf_token()
                        ) ?>"
                    >


                    <input
                        type="hidden"
                        name="action"
                        value="edit"
                    >


                    <input
                        type="hidden"
                        name="user_id"
                        value="<?= e(
                            $editing['id']
                            ?? ''
                        ) ?>"
                    >


                    <div class="row g-3">


                        <!-- FULL NAME -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Full Name
                            </label>

                            <input
                                class="form-control"
                                name="full_name"
                                value="<?= e(
                                    $editing['full_name']
                                    ?? ''
                                ) ?>"
                                required
                            >

                        </div>


                        <!-- EMAIL -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Email
                            </label>

                            <input
                                class="form-control"
                                type="email"
                                name="email"
                                value="<?= e(
                                    $editing['email']
                                    ?? ''
                                ) ?>"
                                required
                            >

                        </div>


                        <!-- DOMICILE -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Domicile
                            </label>

                            <select
                                class="form-select"
                                name="domicile"
                            >

                                <option value="">
                                    Not set
                                </option>


                                <?php

                                $places = [
                                    'Jakarta Pusat',
                                    'Jakarta Barat',
                                    'Jakarta Timur',
                                    'Jakarta Selatan',
                                    'Jakarta Utara',
                                    'Kepulauan Seribu'
                                ];

                                ?>


                                <?php foreach (
                                    $places
                                    as $place
                                ): ?>

                                    <option
                                        value="<?= e(
                                            $place
                                        ) ?>"
                                        <?= (
                                            $editing[
                                                'domicile'
                                            ] ?? ''
                                        ) === $place
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= e(
                                            $place
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>


                            </select>

                        </div>


                        <!-- PREVIOUS SCHOOL -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Previous School
                            </label>

                            <input
                                class="form-control"
                                name="previous_school"
                                value="<?= e(
                                    $editing[
                                        'previous_school'
                                    ]
                                    ?? ''
                                ) ?>"
                            >

                        </div>


                        <!-- EDUCATION PREFERENCE -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Education Preference
                            </label>

                            <select
                                class="form-select"
                                name="education_preference"
                            >

                                <option
                                    value="undecided"
                                    <?= (
                                        $editing[
                                            'education_preference'
                                        ] ?? ''
                                    ) === 'undecided'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Undecided
                                </option>


                                <option
                                    value="sma"
                                    <?= (
                                        $editing[
                                            'education_preference'
                                        ] ?? ''
                                    ) === 'sma'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    SMA
                                </option>


                                <option
                                    value="smk"
                                    <?= (
                                        $editing[
                                            'education_preference'
                                        ] ?? ''
                                    ) === 'smk'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    SMK
                                </option>

                            </select>

                        </div>


                        <!-- ADMISSION PATH -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Admission Path
                            </label>

                            <select
                                class="form-select"
                                name="admission_path"
                            >

                                <option value="">
                                    Not set
                                </option>


                                <?php

                                $currentEditPath =
                                    normalizeAdmissionPath(
                                        (string)(
                                            $editing[
                                                'admission_path'
                                            ]
                                            ?? ''
                                        )
                                    );


                                $adminPaths = [

                                    'prestasi_akademik_sma'
                                        => 'Prestasi Akademik SMA',

                                    'prestasi_akademik_smk'
                                        => 'Prestasi Akademik SMK',

                                    'afirmasi_sma'
                                        => 'Afirmasi SMA',

                                    'zonasi_sma'
                                        => 'Zonasi SMA',

                                    'tahap_kedua_sma'
                                        => 'Tahap Kedua SMA',

                                    'afirmasi_tahap_kedua_smk'
                                        => 'Afirmasi & Tahap Kedua SMK',

                                    'prestasi_non_akademik'
                                        => 'Prestasi Non-Akademik SMA/SMK'

                                ];

                                ?>


                                <?php foreach (
                                    $adminPaths
                                    as $pCode => $pName
                                ): ?>

                                    <option
                                        value="<?= e(
                                            $pCode
                                        ) ?>"
                                        <?= $currentEditPath === $pCode
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= e(
                                            $pName
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>


                            </select>

                        </div>


                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="
                            btn
                            btn-secondary
                            rounded-pill
                        "
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>


                    <button
                        type="submit"
                        class="
                            btn
                            btn-primary
                            rounded-pill
                        "
                    >
                        Update Student
                    </button>

                </div>


            </form>

        </div>

    </div>

</div>


<?php if ($editing): ?>

<script>

document.addEventListener(
    'DOMContentLoaded',
    () => {

        const modalElement =
            document.getElementById(
                'editModal'
            );

        if (modalElement) {

            new bootstrap.Modal(
                modalElement
            ).show();

        }

    }
);

</script>

<?php endif; ?>


<?php

require __DIR__ . '/../includes/footer.php';

?>
