
<?php
/**
 * metrics_helper.php
 *
 * Classification evaluation for the Rule-Based Decision Algorithm.
 *
 * predicted_category = algorithm prediction
 * actual_category    = independently verified correct category
 *
 * Metrics:
 * Accuracy, Precision, Recall, F1-Score
 * Micro, Macro, Weighted and Per-Class
 */

if (!function_exists('get_classification_evaluation_rows')) {

    function get_classification_evaluation_rows(PDO $pdo): array
    {
        try {
            $sql = "
                SELECT
                    TRIM(predicted_category) AS predicted,
                    TRIM(actual_category) AS actual
                FROM billing_records
                WHERE predicted_category IS NOT NULL
                  AND TRIM(predicted_category) <> ''
                  AND actual_category IS NOT NULL
                  AND TRIM(actual_category) <> ''
            ";

            return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log('Classification data error: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('compute_classification_metrics')) {

    function compute_classification_metrics(PDO $pdo): array
    {
        $out = [
            'evaluated' => 0,
            'correct' => 0,
            'accuracy' => 0.0,

            'micro_precision' => 0.0,
            'micro_recall' => 0.0,
            'micro_f1' => 0.0,

            'macro_precision' => 0.0,
            'macro_recall' => 0.0,
            'macro_f1' => 0.0,

            'weighted_precision' => 0.0,
            'weighted_recall' => 0.0,
            'weighted_f1' => 0.0,

            'total_tp' => 0,
            'total_fp' => 0,
            'total_fn' => 0,

            'per_class' => []
        ];

        $rows = get_classification_evaluation_rows($pdo);

        $n = count($rows);
        $out['evaluated'] = $n;

        if ($n === 0) {
            return $out;
        }

        // Collect all actual and predicted labels.
        $labels = [];

        foreach ($rows as $row) {
            $labels[$row['actual']] = true;
            $labels[$row['predicted']] = true;
        }

        $labels = array_keys($labels);

        sort($labels, SORT_NATURAL | SORT_FLAG_CASE);

        $tp = array_fill_keys($labels, 0);
        $fp = array_fill_keys($labels, 0);
        $fn = array_fill_keys($labels, 0);
        $support = array_fill_keys($labels, 0);

        $correct = 0;

        foreach ($rows as $row) {

            $actual = $row['actual'];
            $predicted = $row['predicted'];

            $support[$actual]++;

            if ($actual === $predicted) {

                $tp[$actual]++;
                $correct++;

            } else {

                $fp[$predicted]++;
                $fn[$actual]++;
            }
        }

        // Accuracy
        $out['correct'] = $correct;
        $out['accuracy'] = $correct / $n;

        $sumTP = array_sum($tp);
        $sumFP = array_sum($fp);
        $sumFN = array_sum($fn);

        $out['total_tp'] = $sumTP;
        $out['total_fp'] = $sumFP;
        $out['total_fn'] = $sumFN;

        // Micro averages
        $out['micro_precision'] =
            ($sumTP + $sumFP) > 0
            ? $sumTP / ($sumTP + $sumFP) : 0;

        $out['micro_recall'] =
            ($sumTP + $sumFN) > 0
            ? $sumTP / ($sumTP + $sumFN) : 0;

        $mp = $out['micro_precision'];
        $mr = $out['micro_recall'];

        $out['micro_f1'] =
            ($mp + $mr) > 0
            ? (2 * $mp * $mr) / ($mp + $mr) : 0;

        // Per-class calculations
        $totalSupport = 0;
        $sumPrecision = 0;
        $sumRecall = 0;
        $sumF1 = 0;

        $weightedPrecision = 0;
        $weightedRecall = 0;
        $weightedF1 = 0;

        foreach ($labels as $label) {

            $t = $tp[$label];
            $f = $fp[$label];
            $m = $fn[$label];
            $s = $support[$label];

            $precision = ($t + $f) > 0
                ? $t / ($t + $f) : 0;

            $recall = ($t + $m) > 0
                ? $t / ($t + $m) : 0;

            $f1 = ($precision + $recall) > 0
                ? (2 * $precision * $recall)
                    / ($precision + $recall) : 0;

            $out['per_class'][$label] = [
                'label' => $label,
                'support' => $s,
                'tp' => $t,
                'fp' => $f,
                'fn' => $m,
                'precision' => $precision,
                'recall' => $recall,
                'f1' => $f1
            ];

            $sumPrecision += $precision;
            $sumRecall += $recall;
            $sumF1 += $f1;

            $totalSupport += $s;

            $weightedPrecision += $s * $precision;
            $weightedRecall += $s * $recall;
            $weightedF1 += $s * $f1;
        }

        // Macro averages
        $classCount = count($labels);

        if ($classCount > 0) {

            $out['macro_precision'] =
                $sumPrecision / $classCount;

            $out['macro_recall'] =
                $sumRecall / $classCount;

            $out['macro_f1'] =
                $sumF1 / $classCount;
        }

        // Weighted averages
        if ($totalSupport > 0) {

            $out['weighted_precision'] =
                $weightedPrecision / $totalSupport;

            $out['weighted_recall'] =
                $weightedRecall / $totalSupport;

            $out['weighted_f1'] =
                $weightedF1 / $totalSupport;
        }

        return $out;
    }
}
