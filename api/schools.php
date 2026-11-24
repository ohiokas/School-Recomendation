<?php
declare(strict_types=1);
require_once __DIR__.'/../config/session.php';require_login();header('Content-Type: application/json; charset=utf-8');
$type=strtoupper(trim($_GET['type']??''));$dom=trim($_GET['domicile']??'');$items=[];
$sql='SELECT sd.*,GROUP_CONCAT(sc.competency SEPARATOR "||") competencies_text FROM school_data sd LEFT JOIN school_competencies sc ON sc.school_id=sd.id WHERE sd.is_active=1';$args=[];
if($type!==''){$sql.=' AND sd.school_type=?';$args[]=$type;}if($dom!==''){$sql.=' AND sd.domicile=?';$args[]=$dom;}$sql.=' GROUP BY sd.id ORDER BY sd.name';
$q=db()->prepare($sql);$q->execute($args);foreach($q->fetchAll() as $r){$items[]=['npsn'=>$r['npsn'],'name'=>$r['name'],'address'=>$r['address'],'type'=>$r['school_type'],'domicile'=>$r['domicile'],'accreditation'=>$r['accreditation'],'capacity'=>$r['capacity'],'average_score'=>(float)$r['average_score'],'competencies'=>array_values(array_filter(explode('||',(string)$r['competencies_text'])))];}
echo json_encode(['success'=>true,'count'=>count($items),'data'=>$items],JSON_UNESCAPED_UNICODE);
