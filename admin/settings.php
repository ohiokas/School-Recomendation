<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php'; require_login(); if ((current_user()['role'] ?? '') !== 'admin') redirect('dashboard.php');
$studentGroups = ['prestasi_akademik'=>'Prestasi Akademik SMA/SMK, Afirmasi SMK, Tahap Kedua SMK','zonasi'=>'Zonasi, Afirmasi SMA, Tahap Kedua SMA','prestasi_non_akademik'=>'Prestasi Non-Akademik SMA/SMK'];
$studentFields = ['academic_weight'=>'Academic','academic_achievement_weight'=>'Academic achievement','organization_weight'=>'Organization','non_academic_weight'=>'Non-academic achievement','percentile_weight'=>'Percentile'];
$studentKeys = [];
foreach ($studentGroups as $group => $label) foreach ($studentFields as $field => $fieldLabel) $studentKeys[] = $group . '_' . $field;
$schoolKeys = ['school_student_weight','school_major_weight','school_average_weight','school_domicile_weight'];
$defaultSchoolWeights = ['school_student_weight'=>.30,'school_major_weight'=>.45,'school_average_weight'=>.20,'school_domicile_weight'=>.05];
foreach (array_merge($studentKeys,$schoolKeys) as $key) {
    $query = db()->prepare('SELECT setting_value FROM system_settings WHERE setting_key=?');
    $query->execute([$key]);
    $value = $query->fetchColumn();
    if ($value === false) {
        if (in_array($key, $studentKeys, true)) {
            $legacyKey = preg_replace('/^(prestasi_akademik|zonasi|prestasi_non_akademik)_/', '', $key);
            $legacy = db()->prepare('SELECT setting_value FROM system_settings WHERE setting_key=?');
            $legacy->execute([$legacyKey]);
            $value = $legacy->fetchColumn();
            $value = $value === false ? ($defaultStudentWeights[$legacyKey] ?? 0) : $value;
        } elseif (in_array($key, $schoolKeys, true)) {
            $value = $defaultSchoolWeights[$key] ?? 0;
        }
    }
    $settings[$key] = (float)($value ?? 0);
}
$error = ''; $saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? null)) {
    $input = [];
    foreach (array_merge($studentKeys,$schoolKeys) as $key) $input[$key] = (float)($_POST[$key] ?? 0);
    $invalidGroup = '';
    foreach ($studentGroups as $group => $label) {
        $groupKeys = array_map(static fn($field): string => $group . '_' . $field, array_keys($studentFields));
        if (abs(array_sum(array_intersect_key($input, array_flip($groupKeys))) - 1) > .0001) {
            $invalidGroup = $label;
            break;
        }
    }
    $schoolTotal = array_sum(array_intersect_key($input, array_flip($schoolKeys)));
    if ($invalidGroup !== '') $error = 'Student weight group must total exactly 1.00: ' . $invalidGroup;
    elseif (abs($schoolTotal - 1) > .0001) $error = 'School weight group must total exactly 1.00.';
    else {
        foreach ($input as $key=>$value) {
            $save = db()->prepare('INSERT INTO system_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
            $save->execute([$key,(string)$value]);
        }
        $settings = $input;
        $saved = true;
    }
}
$title = 'System settings'; require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <div class="eyebrow">Admin · settings</div>
    <h1 class="section-title mb-4">Make the formula accountable.</h1>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php elseif ($saved): ?><div class="alert alert-success">Recommendation weights saved successfully. Formula and school matching now use updated values.</div><?php endif; ?>
    <form method="post" class="panel">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="row g-3">
            <div class="col-md-7">
                <h5>Student score weights by admission route</h5>
                <?php foreach ($studentGroups as $group => $groupLabel): ?>
                    <fieldset class="border rounded p-3 mt-3">
                        <legend class="float-none w-auto px-2 h6"><?= e($groupLabel) ?></legend>
                        <?php foreach ($studentFields as $field => $label): $key = $group . '_' . $field; ?>
                            <label class="form-label small mt-2"><?= e($label) ?></label>
                            <input class="form-control weight-input group-<?= e($group) ?>" name="<?= e($key) ?>" type="number" step=".01" min="0" max="1" value="<?= e((string)$settings[$key]) ?>">
                        <?php endforeach; ?>
                        <div class="small mt-2 text-end text-muted group-total-<?= e($group) ?>"></div>
                    </fieldset>
                <?php endforeach; ?>
            </div>
            <div class="col-md-5">
                <h5>School ranking weights</h5>
                <fieldset class="border rounded p-3 mt-3">
                    <legend class="float-none w-auto px-2 h6">School Matching (100%)</legend>
                    <?php foreach (['school_student_weight'=>'Student profile','school_major_weight'=>'Major compatibility','school_average_weight'=>'School average compatibility','school_domicile_weight'=>'Domicile priority'] as $key=>$label): ?>
                        <label class="form-label small mt-2"><?= e($label) ?></label>
                        <input class="form-control weight-input school-weight" name="<?= e($key) ?>" type="number" step=".01" min="0" max="1" value="<?= e((string)$settings[$key]) ?>">
                    <?php endforeach; ?>
                    <div class="small mt-2 text-end text-muted school-total"></div>
                </fieldset>
                <p class="small text-muted mt-3">Each student route group must sum to 1.00 (100%). School ranking group must sum to 1.00 (100%).</p>
            </div>
        </div>
        <button class="btn btn-accent rounded-pill mt-4">Save settings</button>
    </form>
</div>
<script>
function calcTotals() {
    ['prestasi_akademik','zonasi','prestasi_non_akademik'].forEach(g => {
        let sum = 0;
        document.querySelectorAll('.group-' + g).forEach(i => sum += parseFloat(i.value || 0));
        sum = Math.round(sum * 100);
        const el = document.querySelector('.group-total-' + g);
        if (el) {
            el.innerHTML = 'Total: <strong class="' + (sum === 100 ? 'text-success' : 'text-danger') + '">' + sum + '%</strong>' + (sum === 100 ? ' (Valid)' : ' (Must be 100%)');
        }
    });
    let sSum = 0;
    document.querySelectorAll('.school-weight').forEach(i => sSum += parseFloat(i.value || 0));
    sSum = Math.round(sSum * 100);
    const sEl = document.querySelector('.school-total');
    if (sEl) {
        sEl.innerHTML = 'Total: <strong class="' + (sSum === 100 ? 'text-success' : 'text-danger') + '">' + sSum + '%</strong>' + (sSum === 100 ? ' (Valid)' : ' (Must be 100%)');
    }
}
document.querySelectorAll('.weight-input').forEach(i => i.addEventListener('input', calcTotals));
calcTotals();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>