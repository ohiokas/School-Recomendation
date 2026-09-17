<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../recommendation/Scoring.php';
require_once __DIR__ . '/../ml/GaussianNaiveBayes.php';
require_once __DIR__ . '/../recommendation/RecommendationEngine.php';
function check(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); echo "ok: {$message}\n"; }
check(calculateAge('2010-01-01') >= 15, 'age calculation');
check(abs(academicAverage([80,90,70]) - 80) < .01, 'academic average');
check(achievementScore('national','government',true,1) === 91.0, 'achievement scoring');
check(organizationScore('president') === 100.0, 'organization scoring');
check(abs(studentScore(80,90,67,50,100)['total'] - 83.7) < .01, 'student score weights');
$model = new GaussianNaiveBayes();
$model->train([['x'=>1,'class'=>'A'],['x'=>2,'class'=>'A'],['x'=>9,'class'=>'B'],['x'=>10,'class'=>'B']], ['x'], 'class');
check($model->predict(['x'=>1.5])['class'] === 'A', 'gaussian prediction');
$ranked = RecommendationEngine::rank([['name'=>'SMK NEGERI TEST','domisili'=>'Jakarta Selatan','competencies'=>['Rekayasa Perangkat Lunak'],'average_score'=>88]], ['domicile'=>'Jakarta Selatan','student_score'=>83.7], ['Software Engineering']);
check(count($ranked) === 1 && $ranked[0]['domicile_match'] == 100, 'school ranking');
$limitedRanked = RecommendationEngine::rank([['name'=>'SMK LIMITED TEST','school_type'=>'SMK','domicile'=>'Jakarta Selatan','competencies'=>['Rekayasa Perangkat Lunak'],'average_score'=>88]], ['domicile'=>'Jakarta Selatan','education_preference'=>'undecided','student_score'=>83.7], ['Software Engineering']);
check(count($limitedRanked) === 1 && $limitedRanked[0]['major_match'] >= 48, 'passion match threshold');
$databaseSchool = RecommendationEngine::rank([['name'=>'SMA DATABASE TEST','school_type'=>'SMA','domicile'=>'Jakarta Barat','competencies'=>[],'average_score'=>80]], ['domicile'=>'Jakarta Barat','education_preference'=>'sma','student_score'=>80]);
check(count($databaseSchool) === 1 && $databaseSchool[0]['domisili'] === 'Jakarta Barat', 'database school fields');
$limitedOrdering = RecommendationEngine::rank([
	['name'=>'LIMITED NEAR','school_type'=>'SMK','domisili'=>'Jakarta Barat','competencies'=>[],'average_score'=>84],
	['name'=>'LIMITED FAR','school_type'=>'SMK','domisili'=>'Jakarta Barat','competencies'=>[],'average_score'=>96],
	['name'=>'OUTSIDE NEAR','school_type'=>'SMK','domisili'=>'Jakarta Selatan','competencies'=>[],'average_score'=>84],
], ['domicile'=>'Jakarta Barat','student_score'=>83.7], ['Software Engineering']);
check($limitedOrdering[0]['name'] === 'LIMITED NEAR', 'limited matches prioritize aligned academic average');
$outsideLimited = array_values(array_filter($limitedOrdering, static fn($school): bool => $school['name'] === 'OUTSIDE NEAR'));
check(count($outsideLimited) === 1 && $outsideLimited[0]['domicile_match'] < 100, 'outside domicile school remains recommended');
$majorPriority = RecommendationEngine::rank([
	['name'=>'MAJOR MATCH','school_type'=>'SMK','domisili'=>'Jakarta Selatan','competencies'=>['Software Engineering'],'average_score'=>96],
	['name'=>'ACADEMIC MATCH','school_type'=>'SMK','domisili'=>'Jakarta Selatan','competencies'=>[],'average_score'=>80],
], ['domicile'=>'Jakarta Selatan','student_score'=>80], ['Software Engineering']);
check($majorPriority[0]['name'] === 'MAJOR MATCH', 'major fit precedes academic gap');
$undecidedSchools = RecommendationEngine::rank([
	['name'=>'SMA OPTION','school_type'=>'SMA','domisili'=>'Jakarta Barat','average_score'=>80],
	['name'=>'SMK OPTION','school_type'=>'SMK','domisili'=>'Jakarta Barat','average_score'=>80],
], ['domicile'=>'Jakarta Barat','education_preference'=>'Belum memutuskan','student_score'=>80]);
check(count($undecidedSchools) === 2, 'undecided preference recommends SMA and SMK');
$manySchools=[];
for($index=1;$index<=20;$index++) $manySchools[]=['name'=>'SMA LOCAL '.$index,'school_type'=>'SMA','domisili'=>'Jakarta Barat','average_score'=>80];
$manySchools[]=['name'=>'SMK LOCAL','school_type'=>'SMK','domisili'=>'Jakarta Barat','average_score'=>80];
$manySchools[]=['name'=>'SMA OUTSIDE','school_type'=>'SMA','domisili'=>'Jakarta Selatan','average_score'=>80];
$mixedRecommendations=RecommendationEngine::rank($manySchools, ['domicile'=>'Jakarta Barat','education_preference'=>'undecided','student_score'=>80]);
$mixedTypes=array_unique(array_column($mixedRecommendations,'school_type'));
check(in_array('SMA',$mixedTypes,true) && in_array('SMK',$mixedTypes,true), 'top recommendations include both school types');
check(count(array_filter($mixedRecommendations,static fn($school): bool => $school['domicile_match']<100)) >= 1, 'top recommendations include outside domicile');
$zoningRecommendations = RecommendationEngine::rank([
	['name'=>'ZONING LOCAL','school_type'=>'SMA','domisili'=>'Jakarta Barat','average_score'=>80],
	['name'=>'ZONING OUTSIDE','school_type'=>'SMA','domisili'=>'Jakarta Selatan','average_score'=>80],
], ['domicile'=>'Jakarta Barat','education_preference'=>'sma','student_score'=>80], [], [], 'zonasi_sma');
check(count($zoningRecommendations) === 1 && $zoningRecommendations[0]['name'] === 'ZONING LOCAL', 'zonasi stays within domicile city');
$nonAcademicSma = RecommendationEngine::rank([
	['name'=>'SMA NONACADEMIC','school_type'=>'SMA','domisili'=>'Jakarta Barat','average_score'=>80],
], ['domicile'=>'Jakarta Barat','education_preference'=>'sma','student_score'=>80], [], [], 'prestasi_non_akademik');
$nonAcademicSmk = RecommendationEngine::rank([
	['name'=>'SMK NONACADEMIC','school_type'=>'SMK','domisili'=>'Jakarta Barat','average_score'=>80],
], ['domicile'=>'Jakarta Barat','education_preference'=>'smk','student_score'=>80], [], [], 'prestasi_non_akademik');
check(count($nonAcademicSma) === 1 && count($nonAcademicSmk) === 1, 'non-academic route supports SMA and SMK');
check(normalizeAdmissionPath('prestasi_non_akademik_sma') === 'prestasi_non_akademik', 'legacy SMA route is normalized');
check(normalizeAdmissionPath('prestasi_non_akademik_smk') === 'prestasi_non_akademik', 'legacy SMK route is normalized');
check(normalizeAdmissionPath('afirmasi') === 'afirmasi_sma', 'legacy afirmasi route is normalized');
check(normalizeAdmissionPath('afirmasi_sma') === 'afirmasi_sma', 'afirmasi_sma route is preserved');
check(normalizeAdmissionPath('zonasi') === 'zonasi_sma', 'legacy zonasi route is normalized');
check(normalizeAdmissionPath('zonasi_sma') === 'zonasi_sma', 'zonasi_sma route is preserved');
check(normalizeAdmissionPath('tahap_kedua_smk') === 'afirmasi_tahap_kedua_smk', 'legacy SMK tahap kedua route is normalized');
echo "all_logic_tests=passed\n";
?>