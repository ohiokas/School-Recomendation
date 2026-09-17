<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php'; require_login();
$npsn=trim($_GET['npsn']??'');$q=db()->prepare('SELECT sd.*,GROUP_CONCAT(sc.competency SEPARATOR "||") competencies_text FROM school_data sd LEFT JOIN school_competencies sc ON sc.school_id=sd.id WHERE sd.npsn=? GROUP BY sd.id');$q->execute([$npsn]);$school=$q->fetch();
if(!$school){http_response_code(404);exit('School not found.');}
$school['competencies']=array_values(array_filter(explode('||',(string)$school['competencies_text'])));
$title=$school['name'];require __DIR__.'/../includes/header.php';
?>
<div class="container py-5">
<a href="<?=APP_URL?>/student/recommendation.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke rekomendasi</a>
<div class="school-detail-hero mt-4"><div><div class="eyebrow"><?=e($school['school_type'])?> · <?=e($school['domicile'])?></div><h1 class="section-title mt-2 mb-2"><?=e($school['name'])?></h1><p class="lead text-muted mb-0"><?=e($school['address'])?></p></div><div class="detail-score"><span>Akreditasi</span><strong><?=e((string)($school['accreditation']?:'—'))?></strong></div></div>
<div class="row g-4 mt-1"><div class="col-lg-8"><div class="panel"><div class="eyebrow">Program kompetensi</div><h3 class="mt-2 mb-3"><?=count($school['competencies'])?> program tercatat</h3><div class="competency-grid"><?php foreach($school['competencies'] as $c):?><div class="competency-large"><i class="bi bi-arrow-up-right"></i><?=e(trim($c))?></div><?php endforeach;?></div></div></div>
<div class="col-lg-4"><div class="panel"><div class="eyebrow">School facts</div><div class="fact-list"><div><span>NPSN</span><strong><?=e($school['npsn'])?></strong></div><div><span>Daya tampung</span><strong><?=$school['capacity']!==null?e((string)$school['capacity']):'Belum tersedia'?></strong></div><div><span>Akreditasi</span><strong><?=e((string)($school['accreditation']?:'Belum tersedia'))?></strong></div><div><span>Rerata prestasi akademik</span><strong><?=number_format((float)$school['admission_prestasi_akademik'],2)?></strong></div><div><span>Rerata non-akademik</span><strong><?=number_format((float)$school['admission_prestasi_nonakademik'],2)?></strong></div></div></div></div></div>
<div class="panel mt-4"><div class="eyebrow">Admission benchmarks</div><div class="row g-3"><?php foreach(['admission_prestasi_akademik'=>'Prestasi Akademik','admission_prestasi_nonakademik'=>'Prestasi Non-Akademik','admission_afirmasi'=>'Afirmasi','admission_zonasi'=>'Zonasi','admission_tahap_kedua'=>'Tahap Kedua'] as $k=>$label):?><div class="col-6 col-lg"><div class="benchmark"><span><?=e($label)?></span><strong><?=($school[$k]??null)!==null?number_format((float)$school[$k],2):'—'?></strong></div></div><?php endforeach;?></div></div>
</div>
<?php require __DIR__.'/../includes/footer.php';?>
