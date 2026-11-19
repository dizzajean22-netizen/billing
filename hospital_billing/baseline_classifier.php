<?php
/**
 * baseline_classifier.php
 * Traditional (keyword-only) baseline classifier for thesis comparison.
 *
 * ⚠️ HELPER FILE — do NOT access this directly in the browser.
 *    Include it from comparison.php or a test script.
 */

// --- Direct-access guard -----------------------------------------------
if (php_sapi_name() !== 'cli'
    && isset($_SERVER['SCRIPT_FILENAME'])
    && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])
) {
    http_response_code(403);
    exit('This is a helper file. Include it from comparison.php or a test script.');
}
// ------------------------------------------------------------------------

if (!function_exists('traditional_baseline_classify')) {
    /**
     * Ordinary hospital billing system: literal keyword match only.
     * No semantic analysis. No rule priority. First match wins.
     */
    function traditional_baseline_classify(string $description): string
    {
        $d = strtolower(trim($description));

        $rules = [
            'Laboratory Services' => [
                'laboratory', 'lab ', 'cbc', 'urinalysis', 'blood test',
                'pregnancy test', 'culture', 'biopsy', 'screening',
            ],
            'Diagnostic Services' => [
                'diagnostic', 'x-ray', 'xray', 'ultrasound', 'ct scan',
                'mri', 'ecg', 'ekg', 'imaging', 'scan',
            ],
            'Pharmacy' => [
                'pharmacy', 'medicine', 'drug', 'paracetamol', 'antibiotic',
                'iv fluid', 'medication',
            ],
            'Consultation Services' => [
                'consultation', 'consult', 'checkup', 'check-up',
                'follow-up', 'follow up', 'office visit',
            ],
            'Surgical Services' => [
                'surgery', 'surgical', 'operation', 'appendectomy',
                'cesarean', 'incision',
            ],
            'Cardiac Emergency Services' => [
                'cardiac', 'heart', 'ecg monitoring', 'defibrillation',
            ],
            'Emergency OB-GYN Services' => [
                'ob-gyn', 'obgyn', 'ob gyn', 'delivery', 'prenatal',
                'maternity',
            ],
            'Emergency Pediatric Services' => [
                'pediatric', 'pedia', 'child', 'infant', 'neonatal',
            ],
            'Emergency Assessment & Triage' => [
                'triage', 'assessment', 'emergency assessment',
            ],
            'Emergency Treatment & Procedures' => [
                'emergency treatment', 'wound', 'suture', 'first aid',
            ],
            'Emergency Nursing & Patient Care' => [
                'nursing', 'patient care', 'bedside',
            ],
            'Emergency Medication & IV Services' => [
                'iv ', 'intravenous', 'emergency medication',
            ],
            'Emergency Diagnostic & Monitoring' => [
                'monitoring', 'vital signs', 'emergency diagnostic',
            ],
            'Emergency Referral & Transfer' => [
                'referral', 'transfer', 'endorsement',
            ],
            'Room & Board' => ['room', 'board', 'ward', 'bed stay'],
            'Room Charges' => ['room charge', 'accommodation'],
            'Professional Fees' => ['professional fee', 'pf ', 'doctor fee'],
            'Medical Supplies' => ['supplies', 'gloves', 'syringe', 'gauze'],
            'Emergency Services' => ['emergency'],
        ];

        foreach ($rules as $category => $keywords) {
            foreach ($keywords as $kw) {
                if (strpos($d, $kw) !== false) {
                    return $category;
                }
            }
        }
        return 'Other';
    }
}

if (!function_exists('compute_baseline_metrics')) {
    /**
     * Runs the keyword-only classifier against billing_records
     * and returns accuracy + per-class metrics.
     *
     * @param PDO $pdo
     * @return array
     */
    function compute_baseline_metrics(PDO $pdo): array
    {
        $out = [
            'evaluated' => 0, 'correct' => 0, 'accuracy' => 0.0,
            'confusion' => [], 'per_class' => [], 'elapsed_ms' => 0.0,
        ];

        try {
            $rows = $pdo->query("
                SELECT id, service_description, actual_category
                FROM billing_records
                WHERE actual_category IS NOT NULL AND actual_category <> ''
                  AND service_description IS NOT NULL AND service_description <> ''
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return $out;
        }

        if (empty($rows)) return $out;

        $start = microtime(true);
        $confusion = [];
        $correct = 0;

        foreach ($rows as $r) {
            $actual = $r['actual_category'];
            $pred   = traditional_baseline_classify((string)$r['service_description']);
            $confusion[$actual][$pred] = ($confusion[$actual][$pred] ?? 0) + 1;
            if ($actual === $pred) $correct++;
        }
        $elapsed = (microtime(true) - $start) * 1000;

        // Build label set
        $labels = [];
        foreach ($confusion as $actual => $preds) {
            $labels[$actual] = true;
            foreach ($preds as $p => $_) $labels[$p] = true;
        }
        $labels = array_keys($labels);
        sort($labels, SORT_NATURAL | SORT_FLAG_CASE);

        // Per-class metrics
        $perClass = [];
        foreach ($labels as $lbl) {
            $tp = $confusion[$lbl][$lbl] ?? 0;
            $support = array_sum($confusion[$lbl] ?? []);
            $fp = 0; $fn = 0;
            foreach ($labels as $other) {
                if ($other === $lbl) continue;
                $fp += $confusion[$other][$lbl] ?? 0; // predicted lbl, actual other
                $fn += $confusion[$lbl][$other] ?? 0; // actual lbl, predicted other
            }
            $prec = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0;
            $rec  = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0;
            $f1   = ($prec + $rec) > 0 ? 2 * $prec * $rec / ($prec + $rec) : 0;
            $perClass[$lbl] = [
                'label' => $lbl, 'support' => $support,
                'tp' => $tp, 'fp' => $fp, 'fn' => $fn,
                'precision' => $prec, 'recall' => $rec, 'f1' => $f1,
            ];
        }

        $out['evaluated']  = count($rows);
        $out['correct']    = $correct;
        $out['accuracy']   = $correct / count($rows);
        $out['confusion']  = $confusion;
        $out['per_class']  = $perClass;
        $out['elapsed_ms'] = round($elapsed, 2);
        return $out;
    }
}
// ⛔ NOTHING BELOW THIS LINE. No top-level calls. No test code.