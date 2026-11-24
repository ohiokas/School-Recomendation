<?php
declare(strict_types=1);

require_once __DIR__.'/../config/database.php';

require_login();

/*
|--------------------------------------------------------------------------
| MENGAMBIL DATA SEKOLAH BERDASARKAN NPSN
|--------------------------------------------------------------------------
*/

$npsn = trim($_GET['npsn'] ?? '');

$q = db()->prepare(
    'SELECT
        sd.*,
        GROUP_CONCAT(
            sc.competency
            SEPARATOR "||"
        ) AS competencies_text
    FROM school_data sd
    LEFT JOIN school_competencies sc
        ON sc.school_id = sd.id
    WHERE sd.npsn = ?
    GROUP BY sd.id'
);

$q->execute([$npsn]);

$school = $q->fetch();

if (!$school) {
    http_response_code(404);
    exit('School not found.');
}

/*
|--------------------------------------------------------------------------
| MENGOLAH DATA KOMPETENSI SEKOLAH
|--------------------------------------------------------------------------
*/

$school['competencies'] = array_values(
    array_filter(
        explode('||', (string)$school['competencies_text'])
    )
);

/*
|--------------------------------------------------------------------------
| MENENTUKAN JENJANG SEKOLAH
|--------------------------------------------------------------------------
*/

$schoolType = strtoupper(trim((string)$school['school_type']));

$isSMA = $schoolType === 'SMA';
$isSMK = $schoolType === 'SMK';

/*
|--------------------------------------------------------------------------
| DATA DAYA TAMPUNG BERDASARKAN JALUR
|--------------------------------------------------------------------------
*/

$capacityPaths = [
    'capacity_prestasi_akademik' => 'Prestasi Akademik',
    'capacity_prestasi_nonakademik' => 'Prestasi Non-Akademik',
    'capacity_afirmasi' => 'Afirmasi',
    'capacity_tahap_kedua' => 'Tahap Kedua'
];

// Jalur Zonasi hanya ditampilkan untuk SMA.
if ($isSMA) {
    $capacityPaths['capacity_zonasi'] = 'Zonasi';
}

$capacityTotal = 0;
$hasCapacity = false;

foreach ($capacityPaths as $column => $label) {

    if (
        isset($school[$column]) &&
        $school[$column] !== null &&
        $school[$column] !== ''
    ) {
        $capacityTotal += (int)$school[$column];
        $hasCapacity = true;
    }
}

/*
|--------------------------------------------------------------------------
| DATA NILAI ACUAN PENERIMAAN
|--------------------------------------------------------------------------
*/

$benchmarkPaths = [
    'admission_prestasi_akademik' => 'Prestasi Akademik',
    'admission_prestasi_nonakademik' => 'Prestasi Non-Akademik',
    'admission_afirmasi' => 'Afirmasi',
    'admission_tahap_kedua' => 'Tahap Kedua'
];

// Jalur Zonasi hanya ditampilkan untuk SMA.
if ($isSMA) {
    $benchmarkPaths['admission_zonasi'] = 'Zonasi';
}

$title = $school['name'];

require __DIR__.'/../includes/header.php';
?>

<div class="container py-5">

    <!-- KEMBALI KE REKOMENDASI -->

    <a
        href="<?=APP_URL?>/student/recommendation.php"
        class="text-decoration-none"
    >
        <i class="bi bi-arrow-left"></i>
        Kembali ke rekomendasi
    </a>

    <!-- INFORMASI UTAMA SEKOLAH -->

    <div class="school-detail-hero mt-4">

        <div>

            <div class="eyebrow">
                <?=e($schoolType)?>
                ·
                <?=e($school['domicile'])?>
            </div>

            <h1 class="section-title mt-2 mb-2">
                <?=e($school['name'])?>
            </h1>

            <p class="lead text-muted mb-0">
                <?=e($school['address'])?>
            </p>

        </div>

        <div class="detail-score">

            <span>Akreditasi</span>

            <strong>
                <?=e((string)($school['accreditation'] ?: '—'))?>
            </strong>

        </div>

    </div>

    <!-- PROGRAM KOMPETENSI DAN INFORMASI SEKOLAH -->

    <div class="row g-4 mt-1">

        <!-- PROGRAM KOMPETENSI -->

        <div class="col-lg-8">

            <div class="panel">

                <div class="eyebrow">
                    Program kompetensi
                </div>

                <h3 class="mt-2 mb-3">
                    <?=count($school['competencies'])?>
                    program tercatat
                </h3>

                <div class="competency-grid">

                    <?php foreach ($school['competencies'] as $c): ?>

                        <div class="competency-large">

                            <i class="bi bi-arrow-up-right"></i>

                            <?=e(trim($c))?>

                        </div>

                    <?php endforeach; ?>

                    <?php if (empty($school['competencies'])): ?>

                        <p class="text-muted">
                            Belum terdapat data program kompetensi.
                        </p>

                    <?php endif; ?>

                </div>

            </div>

        </div>

        <!-- INFORMASI SEKOLAH -->

        <div class="col-lg-4">

            <div class="panel">

                <div class="eyebrow">
                    School facts
                </div>

                <div class="fact-list">

                    <div>
                        <span>NPSN</span>

                        <strong>
                            <?=e($school['npsn'])?>
                        </strong>
                    </div>

                    <div>
                        <span>Jenjang</span>

                        <strong>
                            <?=e($schoolType)?>
                        </strong>
                    </div>

                    <div>
                        <span>Domisili</span>

                        <strong>
                            <?=e($school['domicile'])?>
                        </strong>
                    </div>

                    <div>
                        <span>Akreditasi</span>

                        <strong>
                            <?=e((string)($school['accreditation'] ?: 'Belum tersedia'))?>
                        </strong>
                    </div>

                    <div>
                        <span>Total daya tampung tercatat</span>

                        <strong>
                            <?=$hasCapacity
                                ? number_format($capacityTotal)
                                : 'Belum tersedia'?>
                        </strong>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- DAYA TAMPUNG PER JALUR -->

    <div class="panel mt-4">

        <div class="eyebrow">
            Admission capacity
        </div>

        <h3 class="mt-2 mb-3">
            Daya Tampung Berdasarkan Jalur Penerimaan
        </h3>

        <p class="text-muted small mb-3">
            Informasi daya tampung ditampilkan berdasarkan data
            masing-masing jalur penerimaan yang tersedia pada dataset.
        </p>

        <div class="row g-3">

            <?php foreach ($capacityPaths as $column => $label): ?>

                <div class="col-6 col-md-4 col-lg">

                    <div class="benchmark">

                        <span>
                            <?=e($label)?>
                        </span>

                        <strong>

                            <?=
                                ($school[$column] ?? null) !== null
                                ? number_format((int)$school[$column])
                                : '—'
                            ?>

                        </strong>

                        <small class="text-muted">
                            Kursi
                        </small>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

        <?php if (!$hasCapacity): ?>

            <div class="alert alert-light mt-3 mb-0">
                Data daya tampung untuk sekolah ini belum tersedia.
            </div>

        <?php endif; ?>

    </div>

    <!-- NILAI ACUAN PENERIMAAN -->

    <div class="panel mt-4">

        <div class="eyebrow">
            Admission benchmarks
        </div>

        <h3 class="mt-2 mb-3">
            Nilai Acuan Penerimaan
        </h3>

        <div class="row g-3">

            <?php foreach ($benchmarkPaths as $column => $label): ?>

                <div class="col-6 col-md-4 col-lg">

                    <div class="benchmark">

                        <span>
                            <?=e($label)?>
                        </span>

                        <strong>

                            <?=
                                ($school[$column] ?? null) !== null
                                ? number_format((float)$school[$column], 2)
                                : '—'
                            ?>

                        </strong>

                        <small class="text-muted">
                            Nilai acuan
                        </small>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

    <p class="text-muted small mt-3">
        Keterangan: Daya tampung merupakan jumlah kursi berdasarkan
        data jalur penerimaan yang tercatat pada dataset.
        Tanda — menunjukkan data tidak tersedia.
        Sekolah SMK tidak menampilkan jalur Zonasi.
    </p>

</div>

<style>
.capacity-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.benchmark {
    height: 100%;
    min-height: 80px;
}

.benchmark span {
    display: block;
    margin-bottom: 8px;
}

.benchmark strong {
    display: block;
    font-size: 20px;
}

.benchmark small {
    display: block;
    margin-top: 4px;
}
</style>

<?php require __DIR__.'/../includes/footer.php'; ?>
