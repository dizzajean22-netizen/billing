<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php'); exit;
}
require_once 'config.php';
require_once 'metrics_helper.php';

/* ============================================================================
   TRADITIONAL BASELINE CONFIGURATION — "OTHER HOSPITALS (NO ALGORITHM)"
   ----------------------------------------------------------------------------
   ⚠️  EDIT THE VALUES BELOW before your defense.

   These represent how OTHER hospitals perform — the ones that use the
   TRADITIONAL MANUAL billing interpretation process (no algorithm, no
   automated classification, no semantic analysis).

   Source them from:
     (a) Your pilot study — ask hospital staff to manually interpret the same
         bills WITHOUT the algorithm, measure their accuracy/consistency
     (b) Literature review — cite the source in your thesis
     (c) Hospital records of manual processing (if available)

   Scale: 0–100 (% or score×20)
   ============================================================================ */
$traditional_baseline = [
    'accuracy'      => 55.0,   // ← EDIT: other hospitals' manual accuracy
    'consistency'   => 60.0,   // ← EDIT: other hospitals' consistency score
    'readability'   => 55.0,   // ← EDIT: other hospitals' bill readability
    'transparency'  => 50.0,   // ← EDIT: other hospitals' transparency
    'understanding' => 55.0,   // ← EDIT: other hospitals' patient understanding
];
$baseline_source = 'Pilot study / literature review (update citation in your thesis)';

/* ---------- Your system's algorithm metrics (pulled live) ---------- */
$clf = compute_classification_metrics($pdo);

function getMetric($pdo, $name, $default = 0) {
    try {
        $stmt = $pdo->prepare("SELECT AVG(score) FROM survey_results WHERE metric = ?");
        $stmt->execute([$name]);
        $v = $stmt->fetchColumn();
        return ($v !== false && $v !== null) ? round($v, 2) : $default;
    } catch (PDOException $e) { return $default; }
}

$readability_score     = getMetric($pdo, 'readability', 0);       // 0–5
$patient_understanding = getMetric($pdo, 'understanding', 0);     // 0–100
$consistency_percent   = getMetric($pdo, 'consistency', 0);       // 0–100
$transparency_score    = getMetric($pdo, 'transparency', 0);      // 0–100

/* ---------- YOUR SYSTEM (with algorithm) ---------- */
$algorithm_metrics = [
    'accuracy'      => round($clf['accuracy'] * 100, 1),
    'consistency'   => $consistency_percent,
    'readability'   => round(($readability_score / 5) * 100, 1),
    'transparency'  => $transparency_score,
    'understanding' => $patient_understanding,
];

$metric_labels = [
    'accuracy'      => ['Accuracy',      'fa-bullseye',       'Correctness of billing classification'],
    'consistency'   => ['Consistency',   'fa-equals',         'Same input → same output, every time'],
    'readability'   => ['Readability',   'fa-book-open',      'How easily patients read & understand bills'],
    'transparency'  => ['Transparency',  'fa-eye',            'Clarity of charges, deductions, coverage'],
    'understanding' => ['Understanding', 'fa-brain',          'Patient comprehension of what was billed and why'],
];

/* ---------- Deltas + interpretations ---------- */
$deltas = [];
foreach ($algorithm_metrics as $k => $v) {
    $deltas[$k] = round($v - $traditional_baseline[$k], 1);
}

function diffBand($d) {
    if ($d >= 10)  return ['label' => 'Substantial Advantage', 'color'=>'#15803d', 'bg'=>'#dcfce7', 'icon'=>'fa-arrow-trend-up'];
    if ($d >= 3)   return ['label' => 'Meaningful Advantage',  'color'=>'#0e7490', 'bg'=>'#cffafe', 'icon'=>'fa-arrow-up'];
    if ($d >= 1)   return ['label' => 'Modest Advantage',      'color'=>'#a16207', 'bg'=>'#fef9c3', 'icon'=>'fa-arrow-up'];
    if ($d <= -3)  return ['label' => 'Disadvantage',          'color'=>'#b91c1c', 'bg'=>'#fee2e2', 'icon'=>'fa-arrow-down'];
    if ($d <= -1)  return ['label' => 'Marginal Disadvantage', 'color'=>'#c2410c', 'bg'=>'#ffedd5', 'icon'=>'fa-arrow-down'];
    return ['label' => 'Similar', 'color'=>'#475569', 'bg'=>'#f1f5f9', 'icon'=>'fa-minus'];
}

$avg_delta = round(array_sum($deltas) / count($deltas), 1);
$avg_band  = diffBand($avg_delta);

/* ---------- CSV export ---------- */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="your_system_vs_other_hospitals_' . date('Ymd_His') . '.csv"');
    header('Pragma: no-cache'); header('Expires: 0');
    $fh = fopen('php://output', 'w');
    fwrite($fh, "\xEF\xBB\xBF");

    fputcsv($fh, ['Your System (with Algorithm) vs Other Hospitals (Traditional Manual)']);
    fputcsv($fh, ['Generated', date('Y-m-d H:i:s')]);
    fputcsv($fh, ['Baseline source', $baseline_source]);
    fputcsv($fh, ['Algorithm availability', 'YOUR SYSTEM: Yes | OTHER HOSPITALS: No']);
    fputcsv($fh, []);
    fputcsv($fh, ['Dimension', 'Your System (Algorithm) %', 'Other Hospitals (Traditional) %', 'Advantage (pp)', 'Interpretation']);

    foreach ($metric_labels as $key => [$label, $icon, $desc]) {
        $band = diffBand($deltas[$key]);
        fputcsv($fh, [
            $label,
            $algorithm_metrics[$key],
            $traditional_baseline[$key],
            $deltas[$key],
            $band['label'],
        ]);
    }
    fputcsv($fh, []);
    fputcsv($fh, ['Average Advantage (pp)', '', '', $avg_delta, $avg_band['label']]);
    fclose($fh);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your System vs Other Hospitals · Hospital Billing System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{
  font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
  background:
    radial-gradient(circle at 15% 20%, rgba(20,184,166,.12) 0%, transparent 45%),
    radial-gradient(circle at 85% 80%, rgba(15,118,110,.14) 0%, transparent 50%),
    linear-gradient(135deg,#f0fdfa 0%,#e6fbf7 40%,#ecfdf5 100%);
  background-attachment:fixed;
  min-height:100vh; padding:28px 24px 60px;
}
.wrap{max-width:1200px;margin:0 auto}
.page-head{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;margin-bottom:22px}
h1{font-size:24px;color:#0f172a;display:flex;align-items:center;gap:10px}
h1 i{color:#0f766e}
.sub{color:#64748b;font-size:13px;margin-top:6px;max-width:760px;line-height:1.6}
.head-actions{display:flex;gap:10px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;border:none;cursor:pointer;transition:all .2s;font-family:inherit}
.btn-primary{background:#0f766e;color:#fff;box-shadow:0 4px 12px rgba(15,118,110,.2)}
.btn-primary:hover{background:#115e59;transform:translateY(-1px)}
.btn-ghost{background:#fff;color:#475569;border:1.5px solid #e2e8f0}
.btn-ghost:hover{background:#f8fafc;color:#0f766e;border-color:#14b8a6}

/* === Context banner === */
.context-banner{
  background:linear-gradient(135deg,#0f766e 0%,#115e59 100%);
  color:#fff;border-radius:14px;padding:22px 26px;margin-bottom:22px;
  display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:center;
}
.context-banner h2{font-size:15px;font-weight:700;margin-bottom:6px;display:flex;align-items:center;gap:8px}
.context-banner p{font-size:12.5px;opacity:.92;line-height:1.55}
.ctx-side{background:rgba(255,255,255,.12);border-radius:10px;padding:14px 18px;border:1px solid rgba(255,255,255,.2)}
.ctx-side .side-label{font-size:10.5px;text-transform:uppercase;letter-spacing:.6px;opacity:.85;font-weight:700;margin-bottom:6px}
.ctx-side .side-value{font-size:16px;font-weight:800;display:flex;align-items:center;gap:8px}
.ctx-side.ours .side-value{color:#86efac}
.ctx-side.theirs .side-value{color:#fca5a5}

.source-note{background:#fffbeb;border-left:4px solid #f59e0b;border-radius:10px;padding:14px 18px;font-size:12.5px;color:#92400e;margin-bottom:22px;display:flex;gap:10px;align-items:flex-start}
.source-note i{color:#d97706;margin-top:2px}

.summary-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
.scard{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;position:relative;overflow:hidden}
.scard .lbl{font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px}
.scard .big{font-size:32px;font-weight:800;color:#0f172a;line-height:1}
.scard .diff{font-size:13px;font-weight:700;margin-top:8px;display:inline-flex;align-items:center;gap:6px}
.scard .diff.up{color:#15803d}
.scard .diff.down{color:#b91c1c}
.scard .diff.flat{color:#475569}
.scard .icon-bg{position:absolute;top:16px;right:16px;width:44px;height:44px;border-radius:12px;background:#f0fdfa;display:flex;align-items:center;justify-content:center;color:#0f766e;font-size:18px}

.panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;margin-bottom:24px}
.panel-h{padding:16px 22px;border-bottom:1px solid #f1f5f9;font-size:15px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:9px}
.panel-h i{color:#0f766e}

table{width:100%;border-collapse:collapse;font-size:13.5px}
thead th{padding:14px 18px;background:#f8fafc;text-align:left;font-size:11px;text-transform:uppercase;color:#64748b;letter-spacing:.5px;border-bottom:1px solid #f1f5f9;font-weight:600}
tbody td{padding:16px 18px;border-bottom:1px solid #f8fafc;color:#334155;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:#f0fdfa}
.metric-name{display:flex;flex-direction:column;gap:3px}
.metric-name strong{color:#0f172a;font-size:13.5px;display:flex;align-items:center;gap:8px}
.metric-name strong i{color:#0f766e;font-size:12px;width:14px}
.metric-name small{color:#94a3b8;font-size:11.5px;line-height:1.4}

.value-cell{font-size:15px;font-weight:800;font-variant-numeric:tabular-nums}
.value-cell.ours{color:#0f766e}
.value-cell.theirs{color:#94a3b8}
.value-cell.delta{font-size:16px}

.badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap}

.bar-compare{display:flex;flex-direction:column;gap:5px;min-width:160px}
.bar-row{display:flex;align-items:center;gap:8px;font-size:11px}
.bar-row .lab{width:52px;font-weight:600;color:#64748b;text-align:right;font-size:10.5px}
.bar-row .bar{flex:1;height:9px;background:#e2e8f0;border-radius:5px;overflow:hidden}
.bar-row .bar>span{display:block;height:100%;border-radius:5px;transition:width .6s ease}
.bar-row.ours .bar>span{background:linear-gradient(90deg,#14b8a6,#0f766e)}
.bar-row.theirs .bar>span{background:linear-gradient(90deg,#cbd5e1,#94a3b8)}

.conclusion{margin-top:20px;padding:20px 24px;background:#f0fdfa;border:1px solid #ccfbf1;border-radius:14px;font-size:13.5px;color:#134e4a;line-height:1.75}
.conclusion strong{color:#0f766e}
.conclusion .band-badge{display:inline-block;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;vertical-align:middle;margin:0 4px}

.avg-row{background:#f0fdfa !important;font-weight:700}
.avg-row td{border-top:2px solid #14b8a6}

.note-foot{padding:14px 20px;background:#f8fafc;border-top:1px solid #f1f5f9;font-size:12px;color:#64748b;line-height:1.6}

@media (max-width:900px){.summary-cards{grid-template-columns:1fr} .context-banner{grid-template-columns:1fr}}
@media (max-width:600px){table{font-size:12px} thead th,tbody td{padding:10px 12px} .bar-compare{min-width:100px}}
</style>
</head>
<body>
<div class="wrap">

    <div class="page-head">
        <div>
            <h1><i class="fas fa-scale-balanced"></i> Your System vs Other Hospitals</h1>
            <div class="sub">
                Comparison between <strong>your hospital's Algorithm-Based System</strong> and
                <strong>other hospitals' Traditional Manual Interpretation</strong> across five dimensions.
                Directly answers Statement of the Problem <strong>#6</strong>.
            </div>
        </div>
        <div class="head-actions">
            <a href="?export=csv" class="btn btn-primary"><i class="fas fa-download"></i> Export CSV</a>
            <a href="performance_metrics.php" class="btn btn-ghost"><i class="fas fa-chart-line"></i> Metrics</a>
            <a href="dashboard.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Dashboard</a>
        </div>
    </div>

    <!-- ⭐ KEY CONTEXT BANNER — makes the "we have algorithm, they don't" point explicit -->
    <div class="context-banner">
        <div>
            <h2><i class="fas fa-code-branch"></i> What makes this comparison different?</h2>
            <p>
                Most hospitals still rely on <strong>manual, human interpretation</strong> of billing
                documents. This study introduces a <strong>Rule-Based + Semantic Analysis algorithm</strong>
                that automates classification and patient explanation. The comparison below shows what
                your hospital gains compared to hospitals without this algorithm.
            </p>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <div class="ctx-side ours">
                <div class="side-label">Your Hospital</div>
                <div class="side-value"><i class="fas fa-circle-check"></i> Algorithm-Based System</div>
            </div>
            <div class="ctx-side theirs">
                <div class="side-label">Other Hospitals</div>
                <div class="side-value"><i class="fas fa-circle-xmark"></i> Manual (No Algorithm)</div>
            </div>
        </div>
    </div>

    <div class="source-note">
        <i class="fas fa-circle-info"></i>
        <div>
            <strong>Traditional baseline source:</strong> <?= htmlspecialchars($baseline_source) ?>.
            To update these numbers, edit <code>$traditional_baseline</code> at the top of <code>comparison.php</code>.
        </div>
    </div>

    <!-- Top summary -->
    <div class="summary-cards">
        <div class="scard">
            <div class="icon-bg"><i class="fas fa-robot"></i></div>
            <div class="lbl">Your System (with Algorithm)</div>
            <div class="big"><?= round(array_sum($algorithm_metrics)/count($algorithm_metrics), 1) ?>%</div>
            <div class="diff up"><i class="fas fa-microchip"></i> Live system metrics</div>
        </div>
        <div class="scard">
            <div class="icon-bg"><i class="fas fa-hospital"></i></div>
            <div class="lbl">Other Hospitals (No Algorithm)</div>
            <div class="big"><?= round(array_sum($traditional_baseline)/count($traditional_baseline), 1) ?>%</div>
            <div class="diff flat"><i class="fas fa-file-pen"></i> Manual process baseline</div>
        </div>
        <div class="scard">
            <div class="icon-bg"><i class="fas fa-arrow-trend-up"></i></div>
            <div class="lbl">Your System's Advantage</div>
            <div class="big"><?= ($avg_delta >= 0 ? '+' : '') . $avg_delta ?> pp</div>
            <div class="diff <?= $avg_delta > 0.5 ? 'up' : ($avg_delta < -0.5 ? 'down' : 'flat') ?>">
                <i class="fas <?= $avg_band['icon'] ?>"></i> <?= $avg_band['label'] ?>
            </div>
        </div>
    </div>

    <!-- Comparison table -->
    <div class="panel">
        <div class="panel-h"><i class="fas fa-table-list"></i> Dimension-by-Dimension Comparison</div>
        <table>
            <thead>
                <tr>
                    <th style="width:26%;">Dimension</th>
                    <th style="text-align:center;">Your System<br><small style="font-weight:400;text-transform:none;letter-spacing:0;">(Algorithm)</small></th>
                    <th style="text-align:center;">Other Hospitals<br><small style="font-weight:400;text-transform:none;letter-spacing:0;">(Traditional)</small></th>
                    <th style="text-align:center;">Your<br>Advantage</th>
                    <th>Visual Comparison</th>
                    <th>Interpretation</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($metric_labels as $key => [$label, $icon, $desc]):
                    $ours   = $algorithm_metrics[$key];
                    $theirs = $traditional_baseline[$key];
                    $d      = $deltas[$key];
                    $band   = diffBand($d);
                ?>
                    <tr>
                        <td>
                            <div class="metric-name">
                                <strong><i class="fas <?= $icon ?>"></i> <?= htmlspecialchars($label) ?></strong>
                                <small><?= htmlspecialchars($desc) ?></small>
                            </div>
                        </td>
                        <td style="text-align:center;" class="value-cell ours"><?= $ours ?>%</td>
                        <td style="text-align:center;" class="value-cell theirs"><?= $theirs ?>%</td>
                        <td style="text-align:center;" class="value-cell delta">
                            <span style="color:<?= $band['color'] ?>;"><?= ($d >= 0 ? '+' : '') . $d ?></span>
                        </td>
                        <td>
                            <div class="bar-compare">
                                <div class="bar-row ours">
                                    <span class="lab">Ours</span>
                                    <div class="bar"><span style="width:<?= min(100, $ours) ?>%"></span></div>
                                </div>
                                <div class="bar-row theirs">
                                    <span class="lab">Others</span>
                                    <div class="bar"><span style="width:<?= min(100, $theirs) ?>%"></span></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge" style="background:<?= $band['bg'] ?>;color:<?= $band['color'] ?>;">
                                <i class="fas <?= $band['icon'] ?>" style="margin-right:4px;"></i><?= $band['label'] ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="avg-row">
                    <td>Average across all dimensions</td>
                    <td style="text-align:center;"><?= round(array_sum($algorithm_metrics)/count($algorithm_metrics), 1) ?>%</td>
                    <td style="text-align:center;"><?= round(array_sum($traditional_baseline)/count($traditional_baseline), 1) ?>%</td>
                    <td style="text-align:center;color:<?= $avg_band['color'] ?>;"><?= ($avg_delta >= 0 ? '+' : '') . $avg_delta ?></td>
                    <td colspan="2" style="color:#64748b;font-size:12.5px;"><?= $avg_band['label'] ?></td>
                </tr>
            </tbody>
        </table>
        <div class="note-foot">
            <strong>Advantage</strong> = percentage-point difference (Your System − Other Hospitals).
            Positive = your algorithm is better. Values ≥ <strong>+3 pp</strong> are considered meaningful
            for a thesis defense; formal statistical significance should be verified with a paired
            <em>t</em>-test or McNemar's test on your pilot dataset.
        </div>
    </div>

    <!-- Conclusion -->
    <div class="conclusion">
        <strong>Summary for Statement of the Problem #6:</strong>
        Compared to other hospitals that use the traditional manual billing interpretation process
        (without an algorithm), your algorithm-based system achieves an average
        <span class="band-badge" style="background:<?= $avg_band['bg'] ?>;color:<?= $avg_band['color'] ?>;">
            <?= $avg_band['label'] ?>
        </span>
        of <strong><?= $avg_delta ?> percentage points</strong>
        (Your System avg <strong><?= round(array_sum($algorithm_metrics)/count($algorithm_metrics), 1) ?>%</strong>
        vs Other Hospitals avg <strong><?= round(array_sum($traditional_baseline)/count($traditional_baseline), 1) ?>%</strong>).

        <?php if ($avg_delta >= 3): ?>
            Your hospital's algorithm-based approach delivers a <strong>measurable, meaningful advantage</strong>
            over other hospitals' manual process — improving billing accuracy, consistency, readability,
            transparency, and patient understanding. This justifies adopting the algorithm over the
            traditional method.
        <?php elseif ($avg_delta >= 1): ?>
            Your hospital's algorithm-based approach provides a <strong>modest but positive advantage</strong>
            over other hospitals' manual process. The improvement is real but may warrant additional
            refinement before full deployment.
        <?php elseif ($avg_delta <= -1): ?>
            Your system <strong>underperforms</strong> the traditional method on average. Re-examine the
            specific dimensions showing regression and tune the algorithm before defense.
        <?php else: ?>
            Your system performs <strong>comparably</strong> to the traditional method. For the defense,
            emphasize qualitative benefits (speed, consistency, transparency, 24/7 availability)
            rather than raw accuracy improvements.
        <?php endif; ?>
    </div>

</div>
</body>
</html>