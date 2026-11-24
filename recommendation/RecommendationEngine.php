<?php
declare(strict_types=1);
require_once __DIR__.'/Scoring.php';

final class RecommendationEngine
{
    public static function schoolType(string $name): string { return stripos(trim($name),'SMK')===0 ? 'SMK' : 'SMA'; }
    public static function normalize(string $value): string { return preg_replace('/[^a-z0-9]+/',' ',strtolower(trim($value))) ?? ''; }
    public static function competencyMatch(array $competencies,array $passionTerms): float
    {
        if (!$passionTerms) return 40;
        $schoolText=self::normalize(implode(' ',$competencies)); $schoolText=str_replace(['rekayasa perangkat lunak','rpl'],'software engineering '.$schoolText,$schoolText); $schoolText=str_replace(['teknik komputer dan jaringan','tkj'],'computer network engineering '.$schoolText,$schoolText); $hits=0;
        foreach ($passionTerms as $term) { $term=self::normalize((string)$term); if ($term!=='' && $schoolText!=='' && (str_contains($schoolText,$term)||str_contains($term,$schoolText))) $hits++; }
        return min(100,30+$hits*18);
    }
    public static function rank(array $schools,array $student,array $passionTerms=[],array $weights=[],string $path=''): array
    {
        if (empty($weights) || array_sum($weights) <= 0.001) {
            $weights = function_exists('getSchoolRankingWeights') ? getSchoolRankingWeights() : ['student'=>.30,'major'=>.45,'average'=>.20,'domicile'=>.05];
        } else {
            $weights = $weights + ['student'=>.30,'major'=>.45,'average'=>.20,'domicile'=>.05];
        }
        $education=strtolower(trim((string)($student['education_preference']??'undecided')));
        if(in_array($education,['','belum memutuskan','belum_memutuskan','both','keduanya'],true)) $education='undecided';
        $sameCityOnly=$path==='zonasi_sma';
        $results=[];
        foreach($schools as $school){
            $type=strtoupper((string)($school['school_type']??''));
            if (!in_array($type,['SMA','SMK'],true)) $type=self::schoolType((string)($school['name']??''));
            if($education!=='undecided' && $education!==strtolower($type)) continue;
            $domicile=0;
            $schoolDomicile=(string)($school['domisili']??$school['domicile']??'');
            if (strcasecmp($schoolDomicile,(string)($student['domicile']??''))===0) $domicile=100;
            elseif($sameCityOnly) continue;
            else $domicile=35;
            $match=self::competencyMatch($school['competencies']??[],$passionTerms);
            $schoolAverage=(float)($school['admission_average']??$school['average_score']??0);
            $studentScore=(float)($student['student_score']??0);
            $gap=abs($studentScore-$schoolAverage);
            $averageCompatibility=$schoolAverage>0 ? max(0,100-$gap*2) : 50;
            $limitedMatch=$match<48;
            $score=$studentScore*$weights['student']+$match*$weights['major']+$averageCompatibility*$weights['average']+$domicile*$weights['domicile'];
            $fit=$schoolAverage<=0 || $studentScore>=$schoolAverage;
            $results[]=$school+['school_type'=>$type,'domisili'=>$schoolDomicile,'major_match'=>$match,'limited_match'=>$limitedMatch,'domicile_match'=>$domicile,'academic_gap'=>$gap,'average_compatibility'=>$averageCompatibility,'academic_fit'=>$fit,'recommendation_score'=>round($score,1),'suitability'=>$fit?'Suitable':'Alternative',
                'reasons'=>[$limitedMatch?'Kesesuaian passion terbatas; rekomendasi mempertimbangkan keselarasan profil dan rerata sekolah':'Kompetensi sekolah selaras dengan passion focus Anda',$domicile===100?'Kota sesuai dengan domisili Anda':'Sekolah di luar domisili, tetap dipertimbangkan','Nilai pembanding jalur: '.number_format($schoolAverage,2).($fit?' — skor Anda memenuhi':' — opsi aspiratif')]];
        }
        usort($results,static function($a,$b){
            if(abs($a['recommendation_score']-$b['recommendation_score']) > 0.0001) return $b['recommendation_score']<=>$a['recommendation_score'];
            if($a['major_match']!==$b['major_match']) return $b['major_match']<=>$a['major_match'];
            if($a['academic_gap']!==$b['academic_gap']) return $a['academic_gap']<=>$b['academic_gap'];
            return $b['domicile_match']<=>$a['domicile_match'];
        });
            $reserved=[];
            if($education==='undecided'){
                foreach(['SMA','SMK'] as $requiredType){
                    foreach($results as $index=>$result){
                        if($result['school_type']===$requiredType){$reserved[$index]=true;break;}
                    }
                }
            }
            foreach($results as $index=>$result){
                if($result['domicile_match']<100){$reserved[$index]=true;break;}
            }
            $selected=[];
            foreach($reserved as $index=>$_){$selected[$index]=$results[$index];}
            foreach($results as $index=>$result){
                if(count($selected)>=20) break;
                if(!isset($selected[$index])) $selected[$index]=$result;
            }
            ksort($selected);
            return array_values($selected);
    }
}
?>
