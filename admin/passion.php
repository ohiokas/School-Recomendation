<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php';require_login();if((current_user()['role']??'')!=='admin')redirect('dashboard.php');
$inds=['Linguistic','Musical','Bodily','Logical-Mathematical','Spatial-Visualization','Interpersonal','Intrapersonal','Naturalist'];$message='';$error='';

// Seed professions if table is empty
$checkCount = db()->query('SELECT COUNT(*) FROM passion_professions')->fetchColumn();
if ($checkCount == 0) {
    $professions = [
        // Linguistic
        ['Journalist', 'Linguistic', 'Writing and reporting news, articles, and content'],
        ['Translator', 'Linguistic', 'Converting text between languages while maintaining meaning'],
        ['Copywriter', 'Linguistic', 'Creating persuasive marketing and advertising content'],
        ['Editor', 'Linguistic', 'Reviewing and refining written content for publication'],
        ['Speech Therapist', 'Linguistic', 'Helping people improve communication and speech disorders'],
        ['Teacher (Language)', 'Linguistic', 'Teaching languages and literature to students'],
        ['Public Relations', 'Linguistic', 'Managing public image and communications for organizations'],
        
        // Musical
        ['Music Producer', 'Musical', 'Creating and producing music recordings'],
        ['Sound Engineer', 'Musical', 'Recording and mixing audio for various media'],
        ['Music Teacher', 'Musical', 'Teaching music theory and performance'],
        ['Composer', 'Musical', 'Creating original musical compositions'],
        ['DJ', 'Musical', 'Mixing and playing music for audiences'],
        ['Music Therapist', 'Musical', 'Using music to help patients heal and improve wellbeing'],
        
        // Bodily-Kinesthetic
        ['Athlete', 'Bodily', 'Competing in sports and physical activities'],
        ['Dancer', 'Bodily', 'Performing artistic dance movements'],
        ['Physical Therapist', 'Bodily', 'Helping patients recover physical mobility'],
        ['Personal Trainer', 'Bodily', 'Guiding individuals in fitness and exercise'],
        ['Surgeon', 'Bodily', 'Performing surgical procedures requiring precision'],
        ['Actor', 'Bodily', 'Performing roles in theater, film, or television'],
        ['Chef', 'Bodily', 'Preparing food with culinary techniques and creativity'],
        
        // Logical-Mathematical
        ['Software Engineer', 'Logical-Mathematical', 'Designing and developing software systems'],
        ['Data Scientist', 'Logical-Mathematical', 'Analyzing complex data to derive insights'],
        ['Accountant', 'Logical-Mathematical', 'Managing financial records and calculations'],
        ['Financial Analyst', 'Logical-Mathematical', 'Analyzing financial data and market trends'],
        ['Mathematician', 'Logical-Mathematical', 'Conducting research in mathematical theories'],
        ['Engineer (Civil)', 'Logical-Mathematical', 'Designing infrastructure and construction projects'],
        ['Economist', 'Logical-Mathematical', 'Studying economic systems and policies'],
        
        // Spatial-Visualization
        ['Architect', 'Spatial-Visualization', 'Designing buildings and structural spaces'],
        ['Graphic Designer', 'Spatial-Visualization', 'Creating visual content for media and marketing'],
        ['Interior Designer', 'Spatial-Visualization', 'Planning and designing interior spaces'],
        ['Photographer', 'Spatial-Visualization', 'Capturing and composing visual images'],
        ['Pilot', 'Spatial-Visualization', 'Navigating aircraft using spatial awareness'],
        ['Urban Planner', 'Spatial-Visualization', 'Designing city layouts and community spaces'],
        ['Animator', 'Spatial-Visualization', 'Creating moving visual content and animations'],
        
        // Interpersonal
        ['Psychologist', 'Interpersonal', 'Studying human behavior and providing mental health support'],
        ['Social Worker', 'Interpersonal', 'Helping individuals and communities with social services'],
        ['Human Resources', 'Interpersonal', 'Managing employee relations and organizational culture'],
        ['Sales Manager', 'Interpersonal', 'Leading sales teams and client relationships'],
        ['Counselor', 'Interpersonal', 'Providing guidance and support for personal issues'],
        ['Politician', 'Interpersonal', 'Representing constituents and building public support'],
        ['Event Planner', 'Interpersonal', 'Coordinating events and managing stakeholder relationships'],
        
        // Intrapersonal
        ['Philosopher', 'Intrapersonal', 'Studying fundamental questions about existence and knowledge'],
        ['Writer', 'Intrapersonal', 'Creating written works through introspection and creativity'],
        ['Researcher', 'Intrapersonal', 'Conducting independent research and analysis'],
        ['Life Coach', 'Intrapersonal', 'Helping individuals achieve personal growth and goals'],
        ['Meditation Instructor', 'Intrapersonal', 'Teaching mindfulness and self-awareness techniques'],
        ['Theologian', 'Intrapersonal', 'Studying religious and spiritual traditions'],
        
        // Naturalist
        ['Biologist', 'Naturalist', 'Studying living organisms and their environments'],
        ['Environmental Scientist', 'Naturalist', 'Researching environmental systems and conservation'],
        ['Veterinarian', 'Naturalist', 'Providing medical care to animals'],
        ['Botanist', 'Naturalist', 'Studying plants and their ecosystems'],
        ['Farmer', 'Naturalist', 'Cultivating crops and managing agricultural land'],
        ['Marine Biologist', 'Naturalist', 'Studying marine life and ocean ecosystems'],
        ['Ecologist', 'Naturalist', 'Researching relationships between organisms and environments']
    ];
    
    foreach ($professions as $prof) {
        db()->prepare('INSERT INTO passion_professions(profession,indicator,description,is_active) VALUES(?,?,?,1)')->execute([$prof[0], $prof[1], $prof[2]]);
    }
    $message = 'Default professions seeded successfully.';
}

if($_SERVER['REQUEST_METHOD']==='POST'&&verify_csrf($_POST['csrf']??null)){try{$a=$_POST['action']??'';if($a==='save'){db()->prepare('INSERT INTO passion_professions(profession,indicator,description) VALUES(?,?,?) ON DUPLICATE KEY UPDATE indicator=VALUES(indicator),description=VALUES(description),is_active=1')->execute([trim($_POST['profession']),$_POST['indicator'],trim($_POST['description'])]);$message='Profession focus disimpan.';}elseif($a==='delete'){db()->prepare('UPDATE passion_professions SET is_active=0 WHERE id=?')->execute([(int)$_POST['id']]);$message='Profession focus dinonaktifkan.';}}catch(Throwable $e){$error=$e->getMessage();}}
$items=[];try{$items=db()->query('SELECT * FROM passion_professions WHERE is_active=1 ORDER BY indicator,profession')->fetchAll();}catch(Throwable $e){}
$title='Passion library';require __DIR__.'/../includes/header.php';
?>
<style>
.passion-header { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem; }
.crud-card { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 15px rgba(0,0,0,0.08); margin-bottom: 1rem; }
</style>
<div class="container py-5">
<div class="passion-header">
<div class="row align-items-center">
<div class="col-lg-8">
<div class="eyebrow mb-2" style="color: rgba(255,255,255,0.8);">Admin · Passion Intelligence</div>
<h1 class="mb-0" style="font-size: 2rem; font-weight: 700;">Professional Career Library</h1>
<p class="mb-0 mt-2" style="opacity: 0.9;">Manage passion-based career recommendations aligned with multiple intelligences</p>
</div>
<div class="col-lg-4 text-lg-end">
<div class="d-flex gap-2 justify-content-lg-end">
<span class="badge bg-light text-dark"><?= count($items) ?> Professions</span>
</div>
</div>
</div>
</div>

<?php if($message):?><div class="alert alert-success"><?=e($message)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<div class="row g-4">
<div class="col-lg-4">
<div class="crud-card">
<div class="eyebrow mb-3">Add / Update</div>
<h4 class="mb-3">Profession Focus</h4>
<form method="post">
<input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<input type="hidden" name="action" value="save">
<div class="mb-3">
<label class="form-label">Profession</label>
<input class="form-control" name="profession" required>
</div>
<div class="mb-3">
<label class="form-label">Leading Tendency</label>
<select class="form-select" name="indicator">
<?php foreach($inds as $i):?><option value="<?=$i?>"><?=$i?></option><?php endforeach;?>
</select>
</div>
<div class="mb-3">
<label class="form-label">Description</label>
<textarea class="form-control" rows="4" name="description"></textarea>
</div>
<button class="btn btn-success rounded-pill w-100">Save Focus</button>
</form>
</div>
</div>
<div class="col-lg-8">
<div class="crud-card">
<div class="d-flex justify-content-between mb-3">
<div>
<div class="eyebrow">Dataset-backed Library</div>
<h4 class="mb-0"><?=count($items)?> Active Professions</h4>
</div>
</div>
<div class="table-responsive">
<table class="table align-middle">
<thead>
<tr>
<th>Profession</th>
<th>Focus</th>
<th>Description</th>
<th></th>
</tr>
</thead>
<tbody>
<?php foreach($items as $i):?><tr>
<td><strong><?=e($i['profession'])?></strong></td>
<td><span class="badge text-bg-light"><?=e($i['indicator'])?></span></td>
<td><small class="text-muted"><?=e($i['description']??'')?></small></td>
<td>
<form method="post" onsubmit="return confirm('Deactivate this profession?')">
<input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?=$i['id']?>">
<button class="btn btn-sm btn-outline-danger rounded-pill">Delete</button>
</form>
</td>
</tr><?php endforeach;?>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>
<?php require __DIR__.'/../includes/footer.php';?>
