<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php'); exit;
}
require_once 'config.php';
require_once 'metrics_helper.php';
require_once 'confusion_matrix_helper.php';

// CSV export endpoint — must be before any HTML output
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    stream_confusion_csv($pdo);
}

$clf = compute_classification_metrics($pdo);
$cm  = build_confusion_matrix($pdo);

function getStatus($score) {
    if ($score >= 90) return ['label'=>'Excellent','color'=>'#15803d','bg'=>'#dcfce7'];
    if ($score >= 75) return ['label'=>'Very Good','color'=>'#0e7490','bg'=>'#cffafe'];
    if ($score >= 60) return ['label'=>'Good','color'=>'#a16207','bg'=>'#fef9c3'];
    if ($score >= 40) return ['label'=>'Fair','color'=>'#c2410c','bg'=>'#ffedd5'];
    return ['label'=>'Needs Improvement','color'=>'#b91c1c','bg'=>'#fee2e2'];
}
function f($v) { return round($v * 100, 1); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Performance Metrics · Hospital Billing System</title>
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
.wrap{max-width:1300px;margin:0 auto}
.page-head{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;margin-bottom:22px}
h1{font-size:24px;color:#0f172a;display:flex;align-items:center;gap:10px}
h1 i{color:#0f766e}
.sub{color:#64748b;font-size:13px;margin-top:6px}
.head-actions{display:flex;gap:10px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;border:none;cursor:pointer;transition:all .2s;font-family:inherit}
.btn-primary{background:#0f766e;color:#fff;box-shadow:0 4px 12px rgba(15,118,110,.2)}
.btn-primary:hover{background:#115e59;transform:translateY(-1px)}
.btn-ghost{background:#fff;color:#475569;border:1.5px solid #e2e8f0}
.btn-ghost:hover{background:#f8fafc;color:#0f766e;border-color:#14b8a6}

.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px 22px;border-left:4px solid #0f766e}
.card .lbl{font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
.card .val{font-size:28px;font-weight:800;color:#0f172a}
.card .badge{display:inline-block;margin-top:10px;padding:3px 11px;border-radius:20px;font-size:10.5px;font-weight:700;text-transform:uppercase}

.panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;margin-bottom:24px}
.panel-h{padding:16px 22px;border-bottom:1px solid #f1f5f9;font-size:15px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:9px;flex-wrap:wrap}
.panel-h i{color:#0f766e}
.panel-h .hint{margin-left:auto;font-size:11.5px;font-weight:500;color:#94a3b8;letter-spacing:0}

table{width:100%;border-collapse:collapse;font-size:13px}
thead th{padding:12px 16px;background:#f8fafc;text-align:left;font-size:11px;text-transform:uppercase;color:#64748b;letter-spacing:.5px;border-bottom:1px solid #f1f5f9;font-weight:600}
tbody td{padding:13px 16px;border-bottom:1px solid #f8fafc;color:#334155;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:#f0fdfa}
.bar-wrap{display:flex;align-items:center;gap:8px}
.bar{flex:1;height:7px;background:#e2e8f0;border-radius:4px;overflow:hidden;min-width:80px}
.bar > span{display:block;height:100%;background:linear-gradient(90deg,#14b8a6,#0f766e);border-radius:4px}
.tag{font-size:11px;color:#64748b;min-width:42px;text-align:right;font-variant-numeric:tabular-nums}
.avg-row{background:#f0fdfa !important;font-weight:700}
.avg-row td{border-top:2px solid #14b8a6}
.empty{padding:50px 20px;text-align:center;color:#94a3b8;font-size:13px}
.empty i{font-size:40px;display:block;margin-bottom:12px;color:#cbd5e1}

/* === Confusion Matrix === */
.cm-wrap{overflow-x:auto;padding:16px 20px 22px}
.cm-table{border-collapse:separate;border-spacing:3px;font-size:11.5px;min-width:100%}
.cm-table thead th{
  padding:8px 6px;background:#0f766e;color:#fff;font-size:10.5px;font-weight:700;
  text-align:center;border-radius:6px;white-space:nowrap;letter-spacing:.3px;
}
.cm-table thead th.row-head{background:#115e59}
.cm-table thead th.corner{background:#134e4a;font-size:10px;letter-spacing:.4px;line-height:1.2}
.cm-table thead th.th-actual{background:#0f766e}
.cm-table thead th.th-predicted{background:#0f766e}
.cm-table tbody th{
  background:#f1f5f9;color:#0f172a;font-weight:700;text-align:right;padding:8px 10px;
  border-radius:6px;font-size:11px;white-space:nowrap;
}
.cm-cell{
  padding:10px 6px;text-align:center;font-weight:800;font-size:12px;
  border-radius:6px;min-width:48px;transition:transform .15s,box-shadow .15s;
  font-variant-numeric:tabular-nums;
}
.cm-cell.zero{font-weight:400}
.cm-cell:hover{transform:scale(1.12);box-shadow:0 6px 16px rgba(0,0,0,.18);z-index:2;position:relative}
.cm-legend{display:flex;flex-wrap:wrap;gap:18px;padding:0 22px 20px;font-size:12px;color:#64748b;align-items:center}
.cm-legend .lg{display:inline-flex;align-items:center;gap:7px}
.cm-legend .sw{width:16px;height:16px;border-radius:4px;display:inline-block}
.cm-legend .diag-sw{background:hsl(145,68%,55%)}
.cm-legend .err-sw{background:hsl(0,78%,65%)}
.cm-legend .zero-sw{background:#f8fafc;border:1px solid #e2e8f0}
.cm-summary{display:flex;flex-wrap:wrap;gap:22px;padding:14px 22px;background:#f8fafc;border-top:1px solid #f1f5f9;font-size:12.5px;color:#475569}
.cm-summary strong{color:#0f766e;font-size:14px}
.cm-summary .ok{color:#15803d}
.cm-summary .err{color:#b91c1c}

@media (max-width:900px){.cards{grid-template-columns:repeat(2,1fr)}}
@media (max-width:520px){.cards{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">

    <div class="page-head">
        <div>
            <h1><i class="fas fa-chart-line"></i> Performance Metrics</h1>
            <div class="sub">Classification performance of the Rule-Based Decision Algorithm · <?= $clf['evaluated'] ?> record(s) evaluated</div>
        </div>
        <div class="head-actions">
            <?php if ($clf['evaluated'] > 0): ?>
                <a href="?export=csv" class="btn btn-primary">
                    <i class="fas fa-download"></i> Download Confusion Matrix (CSV)
                </a>
            <?php endif; ?>
            <a href="comparison.php" class="btn btn-ghost">
                <i class="fas fa-scale-balanced"></i> Algorithm vs Traditional
            </a>
            <a href="dashboard.php" class="btn btn-ghost">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="cards">
        <?php
        $top = [
            ['Accuracy',  f($clf['accuracy']),           $clf['accuracy']],
            ['Precision', f($clf['weighted_precision']), $clf['weighted_precision']],
            ['Recall',    f($clf['weighted_recall']),    $clf['weighted_recall']],
            ['F1-Score',  f($clf['weighted_f1']),        $clf['weighted_f1']],
        ];
        foreach ($top as [$lbl, $pct, $raw]):
            $s = getStatus($pct);
        ?>
        <div class="card">
            <div class="lbl"><?= $lbl ?></div>
            <div class="val"><?= $pct ?>%</div>
            <span class="badge" style="background:<?= $s['bg'] ?>;color:<?= $s['color'] ?>;"><?= $s['label'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Aggregate table -->
    <div class="panel">
        <div class="panel-h"><i class="fas fa-list-check"></i> Aggregate Averages</div>
        <table>
            <thead><tr><th>Average Method</th><th>Precision</th><th>Recall</th><th>F1-Score</th><th>Notes</th></tr></thead>
            <tbody>
                <tr><td><strong>Micro</strong></td>
                    <td><?= f($clf['micro_precision']) ?>%</td>
                    <td><?= f($clf['micro_recall']) ?>%</td>
                    <td><?= f($clf['micro_f1']) ?>%</td>
                    <td style="font-size:11.5px;color:#94a3b8;">Weighted by total samples. Equals Accuracy in single-label problems.</td>
                </tr>
                <tr><td><strong>Macro</strong></td>
                    <td><?= f($clf['macro_precision']) ?>%</td>
                    <td><?= f($clf['macro_recall']) ?>%</td>
                    <td><?= f($clf['macro_f1']) ?>%</td>
                    <td style="font-size:11.5px;color:#94a3b8;">Unweighted — rare classes count equally. Lower = rare classes struggle.</td>
                </tr>
                <tr><td><strong>Weighted</strong></td>
                    <td><?= f($clf['weighted_precision']) ?>%</td>
                    <td><?= f($clf['weighted_recall']) ?>%</td>
                    <td><?= f($clf['weighted_f1']) ?>%</td>
                    <td style="font-size:11.5px;color:#94a3b8;">Weighted by class support. Reflects dominant-class performance.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Confusion Matrix -->
    <div class="panel">
        <div class="panel-h">
            <i class="fas fa-table-cells"></i> Confusion Matrix
            <span class="hint">Rows = Actual (ground truth) · Columns = Predicted (algorithm)</span>
        </div>

        <?php if (empty($cm['labels'])): ?>
            <div class="empty"><i class="fas fa-inbox"></i>No records available to build the matrix.</div>
        <?php else: ?>
            <div class="cm-wrap">
                <table class="cm-table">
                    <thead>
                        <tr>
                            <th class="corner">Actual ↓<br>Predicted →</th>
                            <?php foreach ($cm['labels'] as $predicted): ?>
                                <th class="th-predicted" title="<?= htmlspecialchars($predicted) ?>"><?= htmlspecialchars(cm_abbrev($predicted)) ?></th>
                            <?php endforeach; ?>
                            <th style="background:#134e4a;">Row<br>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cm['labels'] as $actual): 
                            $rowTotal = 0;
                        ?>
                            <tr>
                                <th title="<?= htmlspecialchars($actual) ?>"><?= htmlspecialchars(cm_abbrev($actual)) ?></th>
                                <?php foreach ($cm['labels'] as $predicted): 
                                    $cnt = $cm['matrix'][$actual][$predicted] ?? 0;
                                    $rowTotal += $cnt;
                                    $isDiag = ($actual === $predicted);
                                    $style = cm_cell_color($cnt, $isDiag, $cm['max']);
                                    $cls = 'cm-cell' . ($cnt === 0 ? ' zero' : '');
                                ?>
                                    <td class="<?= $cls ?>"
                                        style="background:<?= $style['bg'] ?>;color:<?= $style['fg'] ?>;"
                                        title="Actual: <?= htmlspecialchars($actual) ?> | Predicted: <?= htmlspecialchars($predicted) ?> | Count: <?= $cnt ?>">
                                        <?= $cnt === 0 ? '·' : $cnt ?>
                                    </td>
                                <?php endforeach; ?>
                                <td style="background:#0f172a;color:#fff;font-weight:800;text-align:center;border-radius:6px;"><?= $rowTotal ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <th style="background:#134e4a;color:#fff;">Col Total</th>
                            <?php foreach ($cm['labels'] as $predicted): 
                                $colTotal = 0;
                                foreach ($cm['labels'] as $actual) $colTotal += $cm['matrix'][$actual][$predicted] ?? 0;
                            ?>
                                <td style="background:#0f172a;color:#fff;font-weight:800;text-align:center;border-radius:6px;"><?= $colTotal ?></td>
                            <?php endforeach; ?>
                            <td style="background:#0f766e;color:#fff;font-weight:800;text-align:center;border-radius:6px;"><?= $cm['total'] ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="cm-legend">
                <span class="lg"><span class="sw diag-sw"></span> Correct (diagonal)</span>
                <span class="lg"><span class="sw err-sw"></span> Misclassified (off-diagonal)</span>
                <span class="lg"><span class="sw zero-sw"></span> No records</span>
                <span style="margin-left:auto;color:#94a3b8;font-size:11.5px;">Hover any cell for full label</span>
            </div>

            <div class="cm-summary">
                <span>Total evaluated: <strong><?= $cm['total'] ?></strong></span>
                <span>Correct: <strong class="ok"><?= $cm['correct'] ?></strong></span>
                <span>Wrong: <strong class="err"><?= $cm['total'] - $cm['correct'] ?></strong></span>
                <span>Accuracy: <strong><?= $cm['total'] > 0 ? round($cm['correct'] / $cm['total'] * 100, 1) : 0 ?>%</strong></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Per-class table (existing) -->
    <div class="panel">
        <div class="panel-h">
            <i class="fas fa-table-columns"></i> Per-Category Breakdown
            <span class="hint"><?= $clf['evaluated'] ?> records · <?= count($clf['per_class']) ?> categories</span>
        </div>

        <?php if (empty($clf['per_class'])): ?>
            <div class="empty"><i class="fas fa-inbox"></i>No per-category data yet.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th style="text-align:center;">Support</th>
                    <th style="text-align:center;">TP</th>
                    <th style="text-align:center;">FP</th>
                    <th style="text-align:center;">FN</th>
                    <th>Precision</th>
                    <th>Recall</th>
                    <th>F1-Score</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clf['per_class'] as $pc):
                    $p = f($pc['precision']); $r = f($pc['recall']); $f1 = f($pc['f1']);
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($pc['label']) ?></strong></td>
                    <td style="text-align:center;"><?= $pc['support'] ?></td>
                    <td style="text-align:center;color:#15803d;font-weight:700;"><?= $pc['tp'] ?></td>
                    <td style="text-align:center;color:#b91c1c;font-weight:700;"><?= $pc['fp'] ?></td>
                    <td style="text-align:center;color:#c2410c;font-weight:700;"><?= $pc['fn'] ?></td>
                    <td><div class="bar-wrap"><div class="bar"><span style="width:<?= $p ?>%"></span></div><span class="tag"><?= $p ?>%</span></div></td>
                    <td><div class="bar-wrap"><div class="bar"><span style="width:<?= $r ?>%"></span></div><span class="tag"><?= $r ?>%</span></div></td>
                    <td><div class="bar-wrap"><div class="bar"><span style="width:<?= $f1 ?>%"></span></div><span class="tag"><?= $f1 ?>%</span></div></td>
                </tr>
                <?php endforeach; ?>
                <tr class="avg-row">
                    <td>Macro Average</td>
                    <td style="text-align:center;"><?= array_sum(array_column($clf['per_class'],'support')) ?></td>
                    <td colspan="3" style="text-align:center;color:#64748b;">—</td>
                    <td><?= f($clf['macro_precision']) ?>%</td>
                    <td><?= f($clf['macro_recall']) ?>%</td>
                    <td><?= f($clf['macro_f1']) ?>%</td>
                </tr>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>
</body>
</html>