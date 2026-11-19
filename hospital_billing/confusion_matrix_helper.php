
<?php
/**
 * confusion_matrix_helper.php
 *
 * Rows    = Actual category
 * Columns = Predicted category
 *
 * Uses the same evaluated records as metrics_helper.php.
 */

require_once __DIR__ . '/metrics_helper.php';

if (!function_exists('build_confusion_matrix')) {

    function build_confusion_matrix(PDO $pdo): array
    {
        $out = [
            'labels' => [],
            'matrix' => [],
            'total' => 0,
            'correct' => 0,
            'wrong' => 0,
            'max' => 0
        ];

        $rows = get_classification_evaluation_rows($pdo);

        if (empty($rows)) {
            return $out;
        }

        $labelSet = [];

        foreach ($rows as $row) {
            $labelSet[$row['actual']] = true;
            $labelSet[$row['predicted']] = true;
        }

        $labels = array_keys($labelSet);

        sort($labels, SORT_NATURAL | SORT_FLAG_CASE);

        $matrix = [];

        foreach ($labels as $actual) {
            $matrix[$actual] = array_fill_keys($labels, 0);
        }

        $correct = 0;
        $max = 0;

        foreach ($rows as $row) {

            $actual = $row['actual'];
            $predicted = $row['predicted'];

            // Actual = row; Predicted = column
            $matrix[$actual][$predicted]++;

            if ($actual === $predicted) {
                $correct++;
            }
        }

        foreach ($matrix as $actual => $columns) {
            foreach ($columns as $predicted => $count) {
                $max = max($max, $count);
            }
        }

        $out['labels'] = $labels;
        $out['matrix'] = $matrix;
        $out['total'] = count($rows);
        $out['correct'] = $correct;
        $out['wrong'] = count($rows) - $correct;
        $out['max'] = $max;

        return $out;
    }
}

if (!function_exists('cm_cell_color')) {

    function cm_cell_color(
        int $count,
        bool $isDiagonal,
        int $maxOverall
    ): array {

        if ($count === 0) {
            return [
                'bg' => '#f8fafc',
                'fg' => '#cbd5e1'
            ];
        }

        $ratio = $count / max(1, $maxOverall);

        $light = 94 - (int) round($ratio * 57);

        if ($isDiagonal) {

            return [
                'bg' => "hsl(145, 68%, {$light}%)",
                'fg' => $light < 55 ? '#ffffff' : '#065f46'
            ];

        }

        return [
            'bg' => "hsl(0, 78%, {$light}%)",
            'fg' => $light < 55 ? '#ffffff' : '#7f1d1d'
        ];
    }
}

if (!function_exists('stream_confusion_csv')) {

    function stream_confusion_csv(PDO $pdo): void
    {
        $cm = build_confusion_matrix($pdo);

        if ($cm['total'] === 0) {
            http_response_code(404);
            exit('No evaluated records available for export.');
        }

        // Export must happen before HTML output.
        header('Content-Type: text/csv; charset=utf-8');

        header(
            'Content-Disposition: attachment; filename="confusion_matrix_'
            . date('Ymd_His') . '.csv"'
        );

        header('Cache-Control: no-store, no-cache, must-revalidate');

        $fh = fopen('php://output', 'w');

        // Excel UTF-8 support
        fwrite($fh, "\xEF\xBB\xBF");

        fputcsv(
            $fh,
            array_merge(
                ['Actual / Predicted'],
                $cm['labels'],
                ['Row Total']
            )
        );

        foreach ($cm['labels'] as $actual) {

            $row = [$actual];
            $rowTotal = 0;

            foreach ($cm['labels'] as $predicted) {

                $value = $cm['matrix'][$actual][$predicted] ?? 0;

                $row[] = $value;
                $rowTotal += $value;
            }

            $row[] = $rowTotal;

            fputcsv($fh, $row);
        }

        // Column totals
        $colTotals = ['Column Total'];

        foreach ($cm['labels'] as $predicted) {

            $total = 0;

            foreach ($cm['labels'] as $actual) {
                $total += $cm['matrix'][$actual][$predicted] ?? 0;
            }

            $colTotals[] = $total;
        }

        $colTotals[] = $cm['total'];

        fputcsv($fh, $colTotals);

        fputcsv($fh, []);

        fputcsv($fh, ['SUMMARY']);
        fputcsv($fh, ['Total evaluated records', $cm['total']]);
        fputcsv($fh, ['Correct classifications', $cm['correct']]);
        fputcsv($fh, ['Incorrect classifications', $cm['wrong']]);

        $accuracy = ($cm['total'] > 0)
            ? ($cm['correct'] / $cm['total']) * 100
            : 0;

        fputcsv($fh, ['Accuracy', round($accuracy, 2) . '%']);

        fputcsv($fh, ['Export date', date('Y-m-d H:i:s')]);

        fputcsv(
            $fh,
            ['Orientation', 'Rows = Actual; Columns = Predicted']
        );

        fclose($fh);
        exit;
    }
}
