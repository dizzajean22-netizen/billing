<?php
/**
 * Research evaluation helpers.
 * Uses only frozen evaluation_test_cases and evaluation_results, never live billing records.
 */
require_once __DIR__ . '/baseline_classifier.php';
require_once __DIR__ . '/classify_lib.php';

function eval_safe_percent(int $num, int $den): float {
    return $den > 0 ? ($num / $den) * 100 : 0.0;
}

/** Build one-vs-rest multiclass metrics and a complete confusion matrix. */
function eval_classification_metrics(array $rows): array {
    $labels = [];
    foreach ($rows as $r) {
        $a = trim((string)($r['expected_category'] ?? ''));
        $p = trim((string)($r['predicted_category'] ?? ''));
        if ($a !== '') $labels[$a] = true;
        if ($p !== '') $labels[$p] = true;
    }
    $labels = array_keys($labels);
    sort($labels, SORT_NATURAL | SORT_FLAG_CASE);
    $matrix = [];
    foreach ($labels as $a) $matrix[$a] = array_fill_keys($labels, 0);
    $correct = 0;
    foreach ($rows as $r) {
        $a = trim((string)$r['expected_category']);
        $p = trim((string)$r['predicted_category']);
        if ($a === '' || $p === '') continue;
        $matrix[$a][$p] = ($matrix[$a][$p] ?? 0) + 1;
        if (strcasecmp($a, $p) === 0) $correct++;
    }
    $n = array_sum(array_map('array_sum', $matrix));
    $per = []; $sumP = 0; $sumR = 0; $sumF = 0;
    foreach ($labels as $label) {
        $tp = $matrix[$label][$label] ?? 0;
        $fp = 0; $fn = 0; $tn = 0;
        foreach ($labels as $other) {
            if ($other !== $label) {
                $fp += $matrix[$other][$label] ?? 0;
                $fn += $matrix[$label][$other] ?? 0;
            }
        }
        $tn = max(0, $n - $tp - $fp - $fn);
        $precision = eval_safe_percent($tp, $tp + $fp);
        $recall = eval_safe_percent($tp, $tp + $fn);
        $f1 = ($precision + $recall) > 0 ? 2 * $precision * $recall / ($precision + $recall) : 0;
        $per[$label] = compact('tp','tn','fp','fn','precision','recall','f1');
        $sumP += $precision; $sumR += $recall; $sumF += $f1;
    }
    $k = count($labels);
    return [
        'evaluated' => $n, 'correct' => $correct, 'incorrect' => $n - $correct,
        'accuracy' => eval_safe_percent($correct, $n),
        'macro_precision' => $k ? $sumP / $k : 0,
        'macro_recall' => $k ? $sumR / $k : 0,
        'macro_f1' => $k ? $sumF / $k : 0,
        'labels' => $labels, 'matrix' => $matrix, 'per_class' => $per
    ];
}

/** Semantic scores are computed from human-reviewed concept annotations, not sentence equality. */
function eval_semantic_metrics(PDO $pdo, int $runId, string $system): array {
    $stmt = $pdo->prepare("SELECT expected_present, evaluator_found_present FROM evaluation_semantic_annotations WHERE run_id=? AND system_type=?");
    $stmt->execute([$runId, $system]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tp=$tn=$fp=$fn=0;
    foreach ($rows as $r) {
        $expected=(int)$r['expected_present']; $found=(int)$r['evaluator_found_present'];
        if ($expected && $found) $tp++;
        elseif (!$expected && !$found) $tn++;
        elseif (!$expected && $found) $fp++;
        else $fn++;
    }
    $precision=eval_safe_percent($tp,$tp+$fp);
    $recall=eval_safe_percent($tp,$tp+$fn);
    return [
        'reviewed_concepts'=>count($rows),'tp'=>$tp,'tn'=>$tn,'fp'=>$fp,'fn'=>$fn,
        'accuracy'=>eval_safe_percent($tp+$tn,count($rows)),
        'precision'=>$precision,'recall'=>$recall,
        'f1'=>($precision+$recall)>0?2*$precision*$recall/($precision+$recall):0
    ];
}

function eval_run_summary(PDO $pdo, int $runId): array {
    $summary=[];
    foreach (['traditional','proposed'] as $system) {
        $s=$pdo->prepare("SELECT expected_category,predicted_category,processing_time_ms FROM evaluation_results WHERE run_id=? AND system_type=?");
        $s->execute([$runId,$system]); $rows=$s->fetchAll(PDO::FETCH_ASSOC);
        $summary[$system]=eval_classification_metrics($rows);
        $summary[$system]['average_ms']=count($rows)?array_sum(array_map(fn($r)=>(float)$r['processing_time_ms'],$rows))/count($rows):0;
        $summary[$system]['records_processed']=count($rows);
        $summary[$system]['semantic']=eval_semantic_metrics($pdo,$runId,$system);
    }
    return $summary;
}
