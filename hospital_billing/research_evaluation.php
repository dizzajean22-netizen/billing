<?php
session_start();
if (empty($_SESSION['loggedin'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/research_evaluation_helper.php';

$userId=(int)($_SESSION['user_id'] ?? 0);
$error=''; $notice='';
if (empty($_SESSION['eval_csrf'])) $_SESSION['eval_csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['eval_csrf'];

function e($v): string { return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function eval_post_guard(string $csrf): void {
    if (!hash_equals($csrf,(string)($_POST['csrf'] ?? ''))) { http_response_code(400); exit('Invalid request token.'); }
}
function eval_make_run(PDO $pdo,int $datasetId,int $userId): int {
    $q=$pdo->prepare("SELECT * FROM evaluation_test_cases WHERE dataset_id=? AND is_active=1 ORDER BY id");
    $q->execute([$datasetId]); $cases=$q->fetchAll(PDO::FETCH_ASSOC);
    if (!$cases) throw new RuntimeException('This dataset has no active test cases.');
    $pdo->beginTransaction();
    try {
        $ins=$pdo->prepare("INSERT INTO evaluation_runs(dataset_id,run_name,baseline_mode,status,executed_by,started_at) VALUES(?,?, 'conventional_keyword','incomplete',?,NOW())");
        $ins->execute([$datasetId,'Evaluation '.date('Y-m-d H:i:s'),$userId ?: null]);
        $runId=(int)$pdo->lastInsertId();
        $save=$pdo->prepare("INSERT INTO evaluation_results(run_id,test_case_id,system_type,expected_category,predicted_category,generated_explanation,processing_time_ms,category_correct,expected_meaning_code) VALUES(?,?,?,?,?,?,?,?,?)");
        foreach($cases as $case) {
            $description=(string)$case['billing_description'];
            $t0=hrtime(true);
            $traditional=traditional_baseline_classify($description);
            $traditionalMs=(hrtime(true)-$t0)/1e6;
            $proposed=classify_service($pdo,$description);
            $proposedCategory=trim((string)($proposed['predicted_category'] ?? ''));
            if($proposedCategory==='') $proposedCategory='Unclassified';
            $proposedExplanation=(string)($proposed['patient_explanation'] ?? '');
            $save->execute([$runId,$case['id'],'traditional',$case['expected_category'],$traditional,'',round($traditionalMs,4),strcasecmp($traditional,$case['expected_category'])===0?1:0,$case['expected_meaning_code']]);
            $save->execute([$runId,$case['id'],'proposed',$case['expected_category'],$proposedCategory,$proposedExplanation,(float)($proposed['processing_ms'] ?? 0),strcasecmp($proposedCategory,$case['expected_category'])===0?1:0,$case['expected_meaning_code']]);
        }
        $done=$pdo->prepare("UPDATE evaluation_runs SET status='completed',completed_at=NOW() WHERE id=?");
        $done->execute([$runId]);
        $pdo->commit();
        return $runId;
    } catch(Throwable $ex) { if($pdo->inTransaction())$pdo->rollBack(); throw $ex; }
}

if($_SERVER['REQUEST_METHOD']==='POST') {
    eval_post_guard($csrf);
    try {
        $action=$_POST['action'] ?? '';
        if($action==='create_dataset') {
            $name=trim((string)($_POST['dataset_name'] ?? ''));
            if($name==='') throw new RuntimeException('Enter a dataset name.');
            $q=$pdo->prepare("INSERT INTO evaluation_datasets(dataset_name,description,source_note,created_by) VALUES(?,?,?,?)");
            $q->execute([$name,trim((string)($_POST['description'] ?? '')),trim((string)($_POST['source_note'] ?? '')),$userId ?: null]);
            $notice='Dataset created. Add verified test cases before running the comparison.';
        } elseif($action==='add_case') {
            $datasetId=(int)($_POST['dataset_id'] ?? 0);
            $code=trim((string)($_POST['case_code'] ?? ''));
            $desc=trim((string)($_POST['billing_description'] ?? ''));
            $category=trim((string)($_POST['expected_category'] ?? ''));
            $meaning=trim((string)($_POST['expected_explanation'] ?? ''));
            if(!$datasetId||$code===''||$desc===''||$category===''||$meaning==='') throw new RuntimeException('Complete the test case fields. Expected category and explanation must be independently verified.');
            $q=$pdo->prepare("INSERT INTO evaluation_test_cases(dataset_id,case_code,billing_description,expected_category,expected_explanation,expected_meaning_code) VALUES(?,?,?,?,?,?)");
            $q->execute([$datasetId,$code,$desc,$category,$meaning,trim((string)($_POST['meaning_code'] ?? '')) ?: null]);
            $notice='Test case saved.';
        } elseif($action==='import_csv') {
            $datasetId=(int)($_POST['dataset_id'] ?? 0);
            if(!$datasetId||empty($_FILES['csv_file']['tmp_name'])) throw new RuntimeException('Choose a dataset and CSV file.');
            $fh=fopen($_FILES['csv_file']['tmp_name'],'r');
            if(!$fh) throw new RuntimeException('Could not read the CSV file.');
            $header=fgetcsv($fh);
            $required=['case_code','billing_description','expected_category','expected_explanation'];
            $map=[];
            foreach((array)$header as $i=>$h) $map[strtolower(trim((string)$h))]=$i;
            foreach($required as $col) if(!array_key_exists($col,$map)) throw new RuntimeException('CSV must include columns: '.implode(', ',$required));
            $insert=$pdo->prepare("INSERT INTO evaluation_test_cases(dataset_id,case_code,billing_description,expected_category,expected_explanation,expected_meaning_code) VALUES(?,?,?,?,?,?)");
            $count=0; $pdo->beginTransaction();
            while(($row=fgetcsv($fh))!==false) {
                $get=fn($key)=>trim((string)($row[$map[$key]] ?? ''));
                if($get('case_code')===''&&$get('billing_description')==='') continue;
                if($get('case_code')===''||$get('billing_description')===''||$get('expected_category')===''||$get('expected_explanation')==='') throw new RuntimeException('A CSV row is missing a required value; import was cancelled.');
                $insert->execute([$datasetId,$get('case_code'),$get('billing_description'),$get('expected_category'),$get('expected_explanation'),isset($map['meaning_code'])?$get('meaning_code'):null]); $count++;
            }
            fclose($fh); $pdo->commit(); $notice="$count test cases imported.";
        } elseif($action==='run') {
            $runId=eval_make_run($pdo,(int)($_POST['dataset_id'] ?? 0),$userId);
            header('Location: research_evaluation.php?run='.$runId); exit;
        }
    } catch(Throwable $ex) {
        if($pdo->inTransaction())$pdo->rollBack();
        $error=$ex->getMessage();
    }
}
$datasets=$pdo->query("SELECT d.*,COUNT(c.id) AS case_count FROM evaluation_datasets d LEFT JOIN evaluation_test_cases c ON c.dataset_id=d.id AND c.is_active=1 GROUP BY d.id ORDER BY d.id DESC")->fetchAll(PDO::FETCH_ASSOC);
$selectedRun=(int)($_GET['run'] ?? 0);
$runs=$pdo->query("SELECT r.*,d.dataset_name FROM evaluation_runs r JOIN evaluation_datasets d ON d.id=r.dataset_id ORDER BY r.id DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
$summary=null;
if($selectedRun) {
    $q=$pdo->prepare("SELECT * FROM evaluation_runs WHERE id=?");$q->execute([$selectedRun]);
    if($q->fetch()) $summary=eval_run_summary($pdo,$selectedRun);
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Research Evaluation</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f4f7f8;color:#172b3a;font:14px Segoe UI,Arial,sans-serif}.wrap{max-width:1200px;margin:auto;padding:26px 18px}.head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}h1{font-size:25px;margin:0}h2{font-size:17px;margin:0 0 15px}.muted{color:#64748b}.panel{background:#fff;border:1px solid #dce5e8;border-radius:12px;padding:20px;margin:18px 0}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:14px}.field{display:flex;flex-direction:column;gap:6px;margin-bottom:12px}label{font-weight:600;color:#334155}input,textarea,select{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:7px;font:inherit}textarea{min-height:76px}.btn{display:inline-block;padding:10px 15px;border:0;border-radius:7px;background:#0f766e;color:#fff;font-weight:650;cursor:pointer;text-decoration:none}.btn.alt{background:#475569}.notice{padding:12px;border-radius:8px;background:#ecfdf5;color:#166534}.error{padding:12px;border-radius:8px;background:#fef2f2;color:#991b1b}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:10px;border-bottom:1px solid #e5e7eb;vertical-align:top}th{background:#f8fafc}.scroll{overflow:auto}.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px}.stat{background:#f8fafc;border:1px solid #e2e8f0;padding:14px;border-radius:8px}.stat b{display:block;font-size:21px;margin-top:5px}.warning{background:#fffbeb;color:#92400e;padding:12px;border-radius:8px;margin-top:12px}@media(max-width:600px){.wrap{padding:16px 10px}.panel{padding:14px}}
</style></head><body><main class="wrap">
<div class="head"><div><h1>Research Evaluation</h1><p class="muted">Controlled conventional baseline vs proposed algorithm testing</p></div><a class="btn alt" href="evaluation_review.php">Semantic Review & Survey</a> <a class="btn alt" href="dashboard.php">Back to Dashboard</a></div>
<?php if($notice):?><p class="notice"><?=e($notice)?></p><?php endif?><?php if($error):?><p class="error"><?=e($error)?></p><?php endif?>
<div class="warning"><b>Baseline disclosure:</b> Traditional results generated here represent a controlled conventional keyword-only baseline, not a real hospital's software. Do not describe them as results from other hospitals.</div>
<section class="panel"><h2>1. Create a test dataset</h2><form method="post"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="action" value="create_dataset"><div class="grid"><div class="field"><label>Dataset name</label><input name="dataset_name" required></div><div class="field"><label>Source / verification note</label><input name="source_note" placeholder="e.g. researcher-prepared and expert-verified"></div></div><div class="field"><label>Description</label><textarea name="description"></textarea></div><button class="btn">Create dataset</button></form></section>
<section class="panel"><h2>2. Add or import verified test cases</h2><form method="post"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="action" value="add_case"><div class="grid"><div class="field"><label>Dataset</label><select name="dataset_id" required><?php foreach($datasets as $d):?><option value="<?=$d['id']?>"><?=e($d['dataset_name'])?></option><?php endforeach?></select></div><div class="field"><label>Test Case ID</label><input name="case_code" required placeholder="TC-001"></div><div class="field"><label>Expected category (verified)</label><input name="expected_category" required></div><div class="field"><label>Meaning code (optional)</label><input name="meaning_code" placeholder="e.g. BLOOD_COUNT"></div></div><div class="field"><label>Technical billing description</label><textarea name="billing_description" required></textarea></div><div class="field"><label>Expected patient-friendly meaning (verified)</label><textarea name="expected_explanation" required></textarea></div><button class="btn">Save test case</button></form>
<hr style="border:0;border-top:1px solid #e2e8f0;margin:20px 0">
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="action" value="import_csv"><div class="grid"><div class="field"><label>Import CSV into dataset</label><select name="dataset_id" required><?php foreach($datasets as $d):?><option value="<?=$d['id']?>"><?=e($d['dataset_name'])?></option><?php endforeach?></select></div><div class="field"><label>CSV file</label><input type="file" name="csv_file" accept=".csv,text/csv" required></div></div><p class="muted">Required CSV headers: case_code, billing_description, expected_category, expected_explanation. Optional: meaning_code.</p><button class="btn alt">Import CSV</button></form></section>
<section class="panel"><h2>3. Available datasets</h2><div class="scroll"><table><thead><tr><th>Dataset</th><th>Active cases</th><th>Source note</th><th>Action</th></tr></thead><tbody><?php foreach($datasets as $d):?><tr><td><?=e($d['dataset_name'])?></td><td><?=$d['case_count']?></td><td><?=e($d['source_note'])?></td><td><form method="post"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="action" value="run"><input type="hidden" name="dataset_id" value="<?=$d['id']?>"><button class="btn" <?=$d['case_count']? '' : 'disabled'?>>Run both systems</button></form></td></tr><?php endforeach?><?php if(!$datasets):?><tr><td colspan="4" class="muted">No datasets yet.</td></tr><?php endif?></tbody></table></div></section>
<section class="panel"><h2>4. Evaluation history</h2><div class="scroll"><table><thead><tr><th>Run</th><th>Dataset</th><th>Status</th><th>Completed</th></tr></thead><tbody><?php foreach($runs as $r):?><tr><td><a href="?run=<?=$r['id']?>">#<?=$r['id']?></a></td><td><?=e($r['dataset_name'])?></td><td><?=e($r['status'])?></td><td><?=e($r['completed_at'])?></td></tr><?php endforeach?></tbody></table></div></section>
<?php if($summary):?><section class="panel"><h2>5. Results for run #<?=e($selectedRun)?></h2><p class="muted">Metrics below use the frozen test cases in this run. Percentages are computed from actual stored predictions.</p><?php foreach(['traditional'=>'Conventional baseline','proposed'=>'Proposed algorithms'] as $key=>$label):$m=$summary[$key];?><h3><?=e($label)?></h3><div class="stats"><div class="stat">Accuracy<b><?=number_format($m['accuracy'],2)?>%</b></div><div class="stat">Macro precision<b><?=number_format($m['macro_precision'],2)?>%</b></div><div class="stat">Macro recall<b><?=number_format($m['macro_recall'],2)?>%</b></div><div class="stat">Macro F1<b><?=number_format($m['macro_f1'],2)?>%</b></div><div class="stat">Average processing<b><?=number_format($m['average_ms'],4)?> ms</b></div><div class="stat">Records processed<b><?=$m['records_processed']?></b></div></div><h4>Confusion matrix (rows = expected; columns = predicted)</h4><div class="scroll"><table><thead><tr><th>Expected / Predicted</th><?php foreach($m['labels'] as $lab):?><th><?=e($lab)?></th><?php endforeach?></tr></thead><tbody><?php foreach($m['matrix'] as $actual=>$cols):?><tr><th><?=e($actual)?></th><?php foreach($m['labels'] as $pred):?><td><?=$cols[$pred]??0?></td><?php endforeach?></tr><?php endforeach?></tbody></table></div><p class="muted">Semantic human-review concept metrics: <?=$m['semantic']['reviewed_concepts']?> reviewed concepts. Accuracy <?=number_format($m['semantic']['accuracy'],2)?>%, precision <?=number_format($m['semantic']['precision'],2)?>%, recall <?=number_format($m['semantic']['recall'],2)?>%, F1 <?=number_format($m['semantic']['f1'],2)?>%.</p><?php endforeach?><div class="warning">Semantic metrics remain provisional until qualified evaluators enter concept-level annotations. An empty review set is shown as 0 reviewed concepts, not as a valid 0% performance finding. Human review is required before drawing conclusions.</div></section><?php endif?>
</main></body></html>
