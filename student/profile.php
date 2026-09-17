<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php'; require_once __DIR__.'/../recommendation/Scoring.php'; require_login();
$user=current_user(); $query=db()->prepare('SELECT * FROM students WHERE user_id=?');$query->execute([$user['id']]);$profile=$query->fetch()?:[];$error='';
$preferences=['sma'=>'SMA','smk'=>'SMK','undecided'=>'Belum memutuskan'];
$paths=[
 'prestasi_akademik_sma'=>'Prestasi Akademik — SMA',
 'prestasi_akademik_smk'=>'Prestasi Akademik — SMK',
 'afirmasi_sma'=>'Afirmasi — SMA',
 'zonasi_sma'=>'Zonasi — SMA',
 'tahap_kedua_sma'=>'Tahap Kedua — SMA',
 'afirmasi_tahap_kedua_smk'=>'Afirmasi & Tahap Kedua — SMK',
 'prestasi_non_akademik'=>'Prestasi Non-Akademik — SMA & SMK',
];
$pathFormulas=[];
$fieldLabels=['academic_weight'=>'Akademik','academic_achievement_weight'=>'Prestasi akademik','non_academic_weight'=>'Non-akademik','percentile_weight'=>'Persentil','organization_weight'=>'Organisasi'];
foreach($paths as $pVal=>$pLabel){
  $w=getStudentRouteWeights($pVal);
  $fp=[];
  foreach($fieldLabels as $wk=>$lbl){
    $v=(float)($w[$wk]??0);
    if($v>0.0001) $fp[]="{$lbl} ".round($v*100,1).'%';
  }
  $pathFormulas[$pVal]=implode(' · ',$fp)?:'Akademik 100%';
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!verify_csrf($_POST['csrf']??null)) $error='Invalid form token.';
 elseif(!isset($preferences[$_POST['education_preference']??''])) $error='Pilih SMA, SMK, atau belum memutuskan.';
 else {
   $pref=$_POST['education_preference'];$path=$_POST['admission_path']??'';
   if(!isset($paths[$path])) $error='Pilih jalur masuk.';
   elseif($pref==='smk' && in_array($path,['zonasi_sma','afirmasi_sma','tahap_kedua_sma'],true)) $error='Jalur zonasi dan jalur SMA tidak tersedia untuk pilihan SMK.';
   elseif($pref==='sma' && in_array($path,['prestasi_akademik_smk','afirmasi_tahap_kedua_smk'],true)) $error='Jalur SMK tidak tersedia untuk pilihan SMA.';
   else try{
     $birth=($_POST['birth_date']??'')?:null;$age=$birth?calculateAge($birth):null;$percentile=max(0,min(100,(float)($_POST['percentile']??100)));
     
     // Ensure admission_path supports modern route identifiers without truncation
     $columnType=(string)db()->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='students' AND COLUMN_NAME='admission_path'")->fetchColumn();
     if(stripos($columnType,'enum')!==false){
       try {
         db()->exec("ALTER TABLE students MODIFY COLUMN admission_path VARCHAR(40) NULL DEFAULT NULL");
       } catch (Throwable $ignored) {
         $legacyRoutes=[
           'prestasi_non_akademik'=> $pref==='smk' ? 'prestasi_non_akademik_smk' : 'prestasi_non_akademik_sma',
           'afirmasi_sma'=>'afirmasi',
           'zonasi_sma'=>'zonasi',
           'afirmasi_tahap_kedua_smk'=>'tahap_kedua_smk',
         ];
         if(!str_contains($columnType,"'{$path}'") && isset($legacyRoutes[$path]) && str_contains($columnType,"'{$legacyRoutes[$path]}'")){
           $path=$legacyRoutes[$path];
         }
       }
     }
     $save=db()->prepare('INSERT INTO students(user_id,birth_date,age,domicile,previous_school,education_preference,percentile,admission_path) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE birth_date=VALUES(birth_date),age=VALUES(age),domicile=VALUES(domicile),previous_school=VALUES(previous_school),education_preference=VALUES(education_preference),percentile=VALUES(percentile),admission_path=VALUES(admission_path)');
     $save->execute([$user['id'],$birth,$age,trim($_POST['domicile']??''),trim($_POST['previous_school']??''),$pref,$percentile,$path]);redirect('dashboard.php');
   }catch(Throwable $e){$error='Profile could not be saved. '.$e->getMessage();}
 }
}
$title='Your profile';require __DIR__.'/../includes/header.php';
?>
<div class="container py-5">
<div class="profile-shell">
<div class="profile-intro"><div class="eyebrow mb-2">01 · Profile & admission route</div><h1 class="section-title">Profil yang langsung membaca jalur masukmu.</h1><p class="text-muted">Jalur masuk sekarang menjadi bagian dari mesin rekomendasi. Pilihan jalur menentukan formula nilai dan kolom rerata sekolah yang dibandingkan.</p><div class="route-note"><i class="bi bi-info-circle"></i><span><strong>Catatan:</strong> untuk Afirmasi/Zonasi/Tahap Kedua SMA, rekomendasi sekolah otomatis dibatasi di kota yang sama dengan domisili.</span></div></div>
<div class="panel profile-form-card">
<?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<label class="form-label">Full name</label><input class="form-control mb-3" value="<?=e($user['full_name'])?>" disabled>
<div class="row g-3"><div class="col-md-7"><label class="form-label">Tanggal lahir</label><input class="form-control" name="birth_date" type="date" value="<?=e($profile['birth_date']??'')?>" data-age="#age"></div><div class="col-md-5"><label class="form-label">Usia</label><div class="form-control bg-light" id="age"><?=!empty($profile['age'])?e((string)$profile['age']).' tahun':''?></div></div></div>
<label class="form-label mt-3">Domisili</label><select class="form-select mb-3" name="domicile" required><option value="">Pilih kota</option><?php foreach(['Jakarta Pusat','Jakarta Barat','Jakarta Timur','Jakarta Selatan','Jakarta Utara','Kepulauan Seribu'] as $place):?><option value="<?=e($place)?>" <?=($profile['domicile']??'')===$place?'selected':''?>><?=e($place)?></option><?php endforeach;?></select>
<label class="form-label">Sekolah asal</label><input class="form-control mb-3" name="previous_school" value="<?=e($profile['previous_school']??'')?>" required>
<label class="form-label">Pilihan jenjang</label><div class="choice-grid mb-3"><?php foreach($preferences as $value=>$label):?><label class="choice-card"><input type="radio" name="education_preference" value="<?=e($value)?>" <?=($profile['education_preference']??'undecided')===$value?'checked':''?> required><span><strong><?=e($label)?></strong><small><?= $value==='smk'?'Kompetensi keahlian wajib match passion':'Sekolah umum / masih eksplorasi'?></small></span></label><?php endforeach;?></div>
<label class="form-label">Jalur masuk</label><select class="form-select mb-3" name="admission_path" id="admissionPath" required><option value="">Pilih jalur masuk</option><?php 
$currentAdmissionPath = normalizeAdmissionPath((string)($profile['admission_path']??''));
foreach($paths as $value=>$label):?><option value="<?=e($value)?>" <?=$currentAdmissionPath===$value?'selected':''?>><?=e($label)?></option><?php endforeach;?></select>
<div class="row g-3"><div class="col-md-6"><label class="form-label">Persentil</label><div class="input-group"><input class="form-control" type="number" min="0" max="100" step=".01" name="percentile" value="<?=e((string)($profile['percentile']??100))?>"><span class="input-group-text">/100</span></div></div><div class="col-md-6"><div class="route-formula" id="routeFormula">Pilih jalur untuk melihat formula penilaian.</div></div></div>
<button class="btn btn-accent rounded-pill px-4 mt-4">Save profile <i class="bi bi-arrow-right"></i></button>
</form></div></div></div>
<script>
const pref=[...document.querySelectorAll('input[name="education_preference"]')], path=document.getElementById('admissionPath'), formula=document.getElementById('routeFormula');
const formulas=<?=json_encode($pathFormulas,JSON_UNESCAPED_UNICODE)?>;
function sync(){const education=document.querySelector('input[name="education_preference"]:checked')?.value||'undecided';[...path.options].forEach(o=>{const smk=o.value.includes('_smk');const sma=o.value.includes('_sma');const hidden=(education==='smk'&&sma)||(education==='sma'&&smk);o.hidden=hidden;if(hidden&&o.selected){path.value='';}});formula.textContent=formulas[path.value]||'Pilih jalur untuk melihat formula penilaian.';}
pref.forEach(x=>x.addEventListener('change',sync));path.addEventListener('change',sync);sync();
</script>
<?php require __DIR__.'/../includes/footer.php';?>
