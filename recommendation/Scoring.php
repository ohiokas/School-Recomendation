<?php
declare(strict_types=1);

function achievementScore(string $level, string $organization, bool $tiered, int $rank): float
{
    $table = [
        'international' => ['government' => [100,97,94], 'parent' => [92,89,86], 'other' => [76,73,70]],
        'national' => ['government' => [91,88,85], 'parent' => [83,80,77], 'other' => [67,64,61]],
        'province' => ['government' => [82,79,76], 'parent' => [74,71,68], 'other' => [58,55,52]],
        'city' => ['government' => [73,70,67], 'parent' => [65,62,59], 'other' => [49,46,43]],
        'none' => ['other' => [0,0,0]],
    ];
    $rankIndex = max(0, min(2, $rank - 1));
    $organization = in_array($organization, ['government', 'parent'], true) ? $organization : 'other';
    $scores = $table[$level][$organization] ?? [0,0,0];
    if ($organization !== 'other' && !$tiered) $scores = array_map(static fn(int $score): int => max(0, $score - 8), $scores);
    return (float)$scores[$rankIndex];
}
function organizationScore(string $role): float
{
    return ['president'=>100,'officer'=>67,'member'=>33,'none'=>0][$role] ?? 0;
}
function academicAverage(array $scores): float
{
    return count($scores) ? array_sum(array_map('floatval',$scores))/count($scores) : 0;
}
function normalizePercentile(float $value): float { return max(0,min(100,$value)); }
function normalizeAdmissionPath(string $path): string
{
    return match ($path) {
        'prestasi_non_akademik_sma', 'prestasi_non_akademik_smk' => 'prestasi_non_akademik',
        'afirmasi' => 'afirmasi_sma',
        'zonasi' => 'zonasi_sma',
        'tahap_kedua_sma' => 'tahap_kedua_sma',
        'tahap_kedua_smk' => 'afirmasi_tahap_kedua_smk',
        default => $path,
    };
}

function routeWeightGroup(string $path): string
{
    $path = normalizeAdmissionPath($path);
    if (in_array($path, ['zonasi_sma', 'afirmasi_sma', 'tahap_kedua_sma', 'zonasi', 'afirmasi'], true)) {
        return 'zonasi';
    }
    if (in_array($path, ['prestasi_non_akademik', 'prestasi_non_akademik_sma', 'prestasi_non_akademik_smk'], true)) {
        return 'prestasi_non_akademik';
    }
    return 'prestasi_akademik';
}

function getSystemSettings(): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $settings = [];
    if (function_exists('db')) {
        try {
            $stmt = db()->query('SELECT setting_key, setting_value FROM system_settings');
            if ($stmt) {
                $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
            }
        } catch (Throwable $e) {
            $settings = [];
        }
    }
    $cached = $settings;
    return $cached;
}

function getStudentRouteWeights(string $path, ?array $overrideSettings = null): array
{
    $group = routeWeightGroup($path);
    $allSettings = $overrideSettings ?? getSystemSettings();

    $defaults = [
        'prestasi_akademik' => [
            'academic_weight' => 0.40,
            'academic_achievement_weight' => 0.25,
            'organization_weight' => 0.10,
            'non_academic_weight' => 0.05,
            'percentile_weight' => 0.20,
        ],
        'zonasi' => [
            'academic_weight' => 0.40,
            'academic_achievement_weight' => 0.25,
            'organization_weight' => 0.10,
            'non_academic_weight' => 0.05,
            'percentile_weight' => 0.20,
        ],
        'prestasi_non_akademik' => [
            'academic_weight' => 0.20,
            'academic_achievement_weight' => 0.05,
            'organization_weight' => 0.20,
            'non_academic_weight' => 0.50,
            'percentile_weight' => 0.05,
        ],
    ];

    $groupDefaults = $defaults[$group] ?? $defaults['prestasi_akademik'];
    $fields = ['academic_weight', 'academic_achievement_weight', 'organization_weight', 'non_academic_weight', 'percentile_weight'];
    $weights = [];

    $hasGroupKey = false;
    foreach ($fields as $f) {
        if (isset($allSettings[$group . '_' . $f])) {
            $hasGroupKey = true;
            break;
        }
    }

    foreach ($fields as $field) {
        $prefixedKey = $group . '_' . $field;
        if (isset($allSettings[$prefixedKey])) {
            $weights[$field] = (float)$allSettings[$prefixedKey];
        } elseif (!$hasGroupKey && isset($allSettings[$field])) {
            $weights[$field] = (float)$allSettings[$field];
        } else {
            $weights[$field] = (float)($groupDefaults[$field] ?? 0);
        }
    }

    return $weights;
}

function getSchoolRankingWeights(?array $overrideSettings = null): array
{
    $allSettings = $overrideSettings ?? getSystemSettings();
    $defaults = [
        'student' => 0.30,
        'major' => 0.45,
        'average' => 0.20,
        'domicile' => 0.05,
    ];

    $weights = [
        'student' => isset($allSettings['school_student_weight']) ? (float)$allSettings['school_student_weight'] : (isset($allSettings['student']) ? (float)$allSettings['student'] : null),
        'major' => isset($allSettings['school_major_weight']) ? (float)$allSettings['school_major_weight'] : (isset($allSettings['major']) ? (float)$allSettings['major'] : null),
        'average' => isset($allSettings['school_average_weight']) ? (float)$allSettings['school_average_weight'] : (isset($allSettings['average']) ? (float)$allSettings['average'] : null),
        'domicile' => isset($allSettings['school_domicile_weight']) ? (float)$allSettings['school_domicile_weight'] : (isset($allSettings['domicile']) ? (float)$allSettings['domicile'] : null),
    ];

    $defined = array_filter($weights, static fn($v) => $v !== null);
    if (count($defined) === 0 || array_sum($weights) <= 0.001) {
        return $defaults;
    }

    return [
        'student' => $weights['student'] ?? $defaults['student'],
        'major' => $weights['major'] ?? $defaults['major'],
        'average' => $weights['average'] ?? $defaults['average'],
        'domicile' => $weights['domicile'] ?? $defaults['domicile'],
    ];
}

/** Score according to the selected admission path and system settings. */
function admissionScore(
    string $path,
    float $academic,
    float $academicAchievement,
    float $organization,
    float $nonAcademic,
    float $percentile,
    array $subjects = [],
    ?array $weights = null
): array {
    $path = normalizeAdmissionPath($path);
    $percentile = normalizePercentile($percentile);
    $academic = max(0, min(100, $academic));
    $academicAchievement = max(0, min(100, $academicAchievement));
    $organization = max(0, min(100, $organization));
    $nonAcademic = max(0, min(100, $nonAcademic));

    if ($weights === null) {
        $weights = getStudentRouteWeights($path);
    }

    $subjectAverage = count($subjects) ? academicAverage($subjects) : $academic;
    $academicBasis = $subjectAverage;

    $parts = [
        'academic' => $academicBasis * (float)($weights['academic_weight'] ?? 0),
        'academic_achievement' => $academicAchievement * (float)($weights['academic_achievement_weight'] ?? 0),
        'organization' => $organization * (float)($weights['organization_weight'] ?? 0),
        'non_academic' => $nonAcademic * (float)($weights['non_academic_weight'] ?? 0),
        'percentile' => $percentile * (float)($weights['percentile_weight'] ?? 0),
    ];

    $labels = [
        'academic_weight' => 'Akademik',
        'academic_achievement_weight' => 'Prestasi akademik',
        'non_academic_weight' => 'Non-akademik',
        'percentile_weight' => 'Persentil',
        'organization_weight' => 'Organisasi',
    ];

    $formulaParts = [];
    foreach ($labels as $fieldKey => $label) {
        $val = (float)($weights[$fieldKey] ?? 0);
        if ($val > 0.0001) {
            $pct = round($val * 100, 1);
            $formulaParts[] = "{$label} {$pct}%";
        }
    }
    $formula = implode(' + ', $formulaParts) ?: 'Akademik 100%';

    return [
        'parts' => $parts,
        'total' => round(array_sum($parts), 2),
        'academic_basis' => $academicBasis,
        'formula' => $formula,
        'weights' => $weights,
        'group' => routeWeightGroup($path),
    ];
}

function studentScore(
    float $academic,
    float $academicAchievement,
    float $organization,
    float $nonAcademic,
    float $percentile = 100,
    string $path = 'prestasi_akademik_sma',
    array $subjects = [],
    ?array $weights = null
): array {
    return admissionScore($path, $academic, $academicAchievement, $organization, $nonAcademic, $percentile, $subjects, $weights);
}
?>
