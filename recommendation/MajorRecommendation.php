<?php
declare(strict_types=1);
final class MajorRecommendation
{
    private const JOBS = [
        'Linguistic'=>[
            'Journalist', 'Writer', 'Editor', 'Copywriter', 'Translator', 'Public Relations Specialist', 
            'Teacher (Language)', 'Broadcaster', 'Marketing Manager', 'Content Creator', 'Speech Therapist',
            'Social Media Manager', 'Technical Writer', 'Librarian', 'News Anchor', 'Voice Actor',
            'Communication Specialist', 'Publishing Editor', 'Scriptwriter', 'Brand Strategist'
        ],
        'Musical'=>[
            'Music Producer', 'Sound Engineer', 'Music Teacher', 'Composer', 'DJ', 'Music Therapist',
            'Audio Editor', 'Recording Artist', 'Music Director', 'Conductor', 'Instrumentalist',
            'Voice Coach', 'Acoustic Engineer', 'Music Journalist', 'Event DJ', 'Sound Designer'
        ],
        'Bodily'=>[
            'Athlete', 'Dancer', 'Physical Therapist', 'Personal Trainer', 'Surgeon', 'Actor',
            'Chef', 'Mechanic', 'Fitness Instructor', 'Sports Coach', 'Choreographer',
            'Massage Therapist', 'Occupational Therapist', 'Construction Worker', 'Firefighter', 'Paramedic'
        ],
        'Logical-Mathematical'=>[
            'Software Engineer', 'Data Scientist', 'Accountant', 'Financial Analyst', 'Mathematician',
            'Engineer (Civil)', 'Economist', 'Actuary', 'Statistician', 'Systems Analyst',
            'Quantitative Analyst', 'Operations Research Analyst', 'Financial Planner', 'Investment Banker', 'Cryptographer'
        ],
        'Spatial-Visualization'=>[
            'Architect', 'Graphic Designer', 'Interior Designer', 'Photographer', 'Pilot',
            'Urban Planner', 'Animator', 'Industrial Designer', 'Landscape Architect', 'Cartographer',
            'Video Editor', '3D Modeler', 'Fashion Designer', 'Art Director', 'Game Designer'
        ],
        'Interpersonal'=>[
            'Psychologist', 'Social Worker', 'Human Resources Manager', 'Sales Manager', 'Counselor',
            'Politician', 'Event Planner', 'Teacher', 'Nurse', 'Public Relations Specialist',
            'Team Leader', 'Customer Success Manager', 'Recruiter', 'Training Coordinator', 'Community Manager'
        ],
        'Intrapersonal'=>[
            'Philosopher', 'Writer', 'Researcher', 'Life Coach', 'Meditation Instructor',
            'Theologian', 'Therapist', 'Author', 'Consultant', 'Strategic Planner',
            'Executive Coach', 'Motivational Speaker', 'Research Scientist', 'Policy Analyst', 'Thought Leader'
        ],
        'Naturalist'=>[
            'Biologist', 'Environmental Scientist', 'Veterinarian', 'Botanist', 'Farmer',
            'Marine Biologist', 'Ecologist', 'Geologist', 'Zoologist', 'Forestry Ranger',
            'Environmental Consultant', 'Wildlife Biologist', 'Hydrologist', 'Climate Scientist', 'Sustainable Agriculture Specialist'
        ]
    ];
    public static function fromIndicator(string $indicator): array {
        $key=self::key($indicator); return self::JOBS[$key]??[];
    }
    public static function fromPrediction(string $profession,string $indicator): array {
        $text=strtolower($profession);
        $all=self::fromIndicator($indicator);
        foreach(self::JOBS as $jobs) foreach($jobs as $job) if(str_contains(strtolower($job),$text)||str_contains($text,strtolower($job))) return array_values(array_unique(array_merge([$job],$all)));
        return $all;
    }
    public static function focus(string $indicator): array {
        return [
          'Linguistic'=>['focus'=>'Komunikasi, bahasa, media, persuasi, edukasi, dan storytelling','color'=>'coral'],
          'Musical'=>['focus'=>'Musik, audio, performa, produksi kreatif, dan ekspresi ritmis','color'=>'violet'],
          'Bodily'=>['focus'=>'Gerak, olahraga, praktik lapangan, kesehatan, dan keterampilan hands-on','color'=>'lime'],
          'Logical-Mathematical'=>['focus'=>'Analitik, angka, riset, teknologi, sistem, dan pemecahan masalah','color'=>'blue'],
          'Spatial-Visualization'=>['focus'=>'Desain, visual, arsitektur, media, pemetaan, dan ruang','color'=>'orange'],
          'Interpersonal'=>['focus'=>'Leadership, layanan, kesehatan, bisnis, kolaborasi, dan pengembangan orang','color'=>'pink'],
          'Intrapersonal'=>['focus'=>'Refleksi, riset mandiri, strategi, psikologi, dan perencanaan','color'=>'indigo'],
          'Naturalist'=>['focus'=>'Lingkungan, bumi, hayati, kesehatan, keberlanjutan, dan eksplorasi alam','color'=>'green'],
        ][$indicator]??['focus'=>'Eksplorasi lintas teknologi, komunikasi, manusia, dan dunia terapan','color'=>'coral'];
    }
    public static function competencyTerms(string $indicator): array {
        return [
            'Linguistic'=>[
                'Bahasa','Pemasaran','Manajemen Perkantoran','Broadcasting','Jurnalistik','Perhotelan','Layanan',
                'Public Relations','Social Media','Content Writing','Communication','Publishing','Media','Advertising'
            ],
            'Musical'=>[
                'Musik','Seni','Audio','Broadcasting','Produksi','Karawitan','Sound Engineering','Music Production',
                'Performance','Recording','Acoustics','Entertainment','Audio Visual','Music Theory'
            ],
            'Bodily'=>[
                'Teknik Kendaraan','Pemesinan','Konstruksi','Olahraga','Kesehatan','Farmasi','Keperawatan','Teknik',
                'Fitness','Sports','Physical Therapy','Culinary','Performing Arts','Healthcare','Emergency Services'
            ],
            'Logical-Mathematical'=>[
                'Rekayasa Perangkat Lunak','Teknik Komputer','Jaringan','Akuntansi','Matematika','Teknik','Sistem Informasi','Otomotif',
                'Data Science','Financial Analysis','Engineering','Statistics','Research','Analytics','Programming','Systems'
            ],
            'Spatial-Visualization'=>[
                'Desain Komunikasi Visual','Desain Pemodelan','Animasi','Multimedia','Konstruksi','Arsitektur','Interior','Grafika',
                'Graphic Design','Architecture','Photography','Animation','Urban Planning','Industrial Design','Visual Arts'
            ],
            'Interpersonal'=>[
                'Manajemen Perkantoran','Bisnis Retail','Pemasaran','Manajemen','Perhotelan','Layanan Kesehatan','Keperawatan',
                'Human Resources','Psychology','Social Work','Education','Leadership','Customer Service','Counseling','Team Management'
            ],
            'Intrapersonal'=>[
                'Akuntansi','Rekayasa Perangkat Lunak','Desain','Manajemen','Psikologi','Riset','Administrasi',
                'Research','Philosophy','Writing','Strategic Planning','Self Development','Analysis','Consulting','Coaching'
            ],
            'Naturalist'=>[
                'Agribisnis','Lingkungan','Peternakan','Perikanan','Farmasi','Kesehatan','Geologi','Pertanian',
                'Biology','Environmental Science','Veterinary','Ecology','Conservation','Sustainability','Wildlife','Agriculture'
            ],
        ][self::key($indicator)]??[];
    }
    private static function key(string $indicator): string { return str_replace('Logical - Mathematical','Logical-Mathematical',$indicator); }
}
?>