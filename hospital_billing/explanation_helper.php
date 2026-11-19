<?php
/**
 * explanation_helper.php
 * Robust, case-insensitive, HTML-entity-tolerant, whitespace-tolerant
 * fallback resolver for "Patient Understanding" explanations.
 */

if (!function_exists('_exp_norm')) {
    function _exp_norm($s): string
    {
        $s = (string)$s;
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = preg_replace('/[\s\x{00A0}]+/u', ' ', $s);
        return strtolower(trim($s));
    }
}

if (!function_exists('fetch_patient_explanations')) {
    function fetch_patient_explanations(PDO $pdo, array $service_names): array
    {
        $result = [];
        if (empty($service_names)) return $result;

        $norm_to_orig = [];
        foreach ($service_names as $name) {
            $n = _exp_norm($name);
            if ($n !== '') $norm_to_orig[$n] = (string)$name;
        }
        if (empty($norm_to_orig)) return $result;

        $norm_keys = array_keys($norm_to_orig);
        $by_norm   = [];

        /* 1) services table */
        try {
            $rows = $pdo->query(
                "SELECT service_name, patient_explanation
                   FROM services
                  WHERE patient_explanation IS NOT NULL
                    AND patient_explanation <> ''"
            )->fetchAll(PDO::FETCH_ASSOC);

            $key_set = array_flip($norm_keys);
            foreach ($rows as $row) {
                $k = _exp_norm($row['service_name'] ?? '');
                $v = trim((string)($row['patient_explanation'] ?? ''));
                if ($k !== '' && $v !== '' && isset($key_set[$k]) && !isset($by_norm[$k])) {
                    $by_norm[$k] = $v;
                }
            }
        } catch (PDOException $e) {}

        /* 2) semantic_dictionary */
        $missing = array_values(array_diff($norm_keys, array_keys($by_norm)));
        if (!empty($missing)) {
            try {
                $rows = $pdo->query(
                    "SELECT technical_term, patient_explanation
                       FROM semantic_dictionary
                      WHERE patient_explanation IS NOT NULL
                        AND patient_explanation <> ''"
                )->fetchAll(PDO::FETCH_ASSOC);

                $key_set = array_flip($missing);
                foreach ($rows as $row) {
                    $k = _exp_norm($row['technical_term'] ?? '');
                    $v = trim((string)($row['patient_explanation'] ?? ''));
                    if ($k !== '' && $v !== '' && isset($key_set[$k]) && !isset($by_norm[$k])) {
                        $by_norm[$k] = $v;
                    }
                }
            } catch (PDOException $e) {}
        }

        /* 3) back-fill from any other billing record */
        $missing = array_values(array_diff($norm_keys, array_keys($by_norm)));
        if (!empty($missing)) {
            try {
                $rows = $pdo->query(
                    "SELECT service_description, patient_explanation
                       FROM billing_records
                      WHERE patient_explanation IS NOT NULL
                        AND patient_explanation <> ''
                      ORDER BY id DESC"
                )->fetchAll(PDO::FETCH_ASSOC);

                $key_set = array_flip($missing);
                foreach ($rows as $row) {
                    $k = _exp_norm($row['service_description'] ?? '');
                    $v = trim((string)($row['patient_explanation'] ?? ''));
                    if ($k !== '' && $v !== '' && isset($key_set[$k]) && !isset($by_norm[$k])) {
                        $by_norm[$k] = $v;
                    }
                }
            } catch (PDOException $e) {}
        }

        /* 4) match by billing_records.service_id -> services.id */
        $missing = array_values(array_diff($norm_keys, array_keys($by_norm)));
        if (!empty($missing)) {
            try {
                $rows = $pdo->query(
                    "SELECT DISTINCT LOWER(TRIM(service_description)) AS nd, service_id
                       FROM billing_records
                      WHERE service_description IS NOT NULL
                        AND service_description <> ''
                        AND service_id IS NOT NULL
                        AND service_id > 0"
                )->fetchAll(PDO::FETCH_ASSOC);

                $desc_to_sid = [];
                foreach ($rows as $row) {
                    $desc_to_sid[_exp_norm($row['nd'])] = (int)$row['service_id'];
                }

                $key_set   = array_flip($missing);
                $sid_match = [];
                foreach ($key_set as $k => $_) {
                    if (isset($desc_to_sid[$k])) $sid_match[$k] = $desc_to_sid[$k];
                }

                if (!empty($sid_match)) {
                    $ids = array_values(array_unique(array_values($sid_match)));
                    $ph  = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $pdo->prepare(
                        "SELECT id, patient_explanation
                           FROM services
                          WHERE id IN ($ph)
                            AND patient_explanation IS NOT NULL
                            AND patient_explanation <> ''"
                    );
                    $stmt->execute($ids);

                    $id_to_exp = [];
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $id_to_exp[(int)$row['id']] = trim((string)$row['patient_explanation']);
                    }
                    foreach ($sid_match as $k => $sid) {
                        if (isset($id_to_exp[$sid]) && !isset($by_norm[$k])) {
                            $by_norm[$k] = $id_to_exp[$sid];
                        }
                    }
                }
            } catch (PDOException $e) {}
        }

        /* 5) ✅ HARDCODED FALLBACK (Komprehensibong listahan) */
        $missing = array_values(array_diff($norm_keys, array_keys($by_norm)));
        if (!empty($missing)) {
            $hardcoded = [
                'patient transfer' => 'Moving a patient from one area or facility to another to receive appropriate medical care.',
                'pediatric emergency assessment' => 'A rapid medical check-up for infants, children, and teenagers who need immediate attention for an illness or injury.',
                'suturing' => 'Closing a wound with stitches to help it heal properly and prevent infection.',
                'cpr' => 'Cardiopulmonary Resuscitation — an emergency procedure to restart the heart and breathing.',
                'private room' => 'Accommodation in a room intended for one patient.',
                'splint application' => 'This service supports an injured bone or body part to help prevent unnecessary movement.',
                'cardiac monitoring' => 'Continuous monitoring of the heart\'s electrical activity to detect any abnormalities or changes.',
                'medication administration' => 'Providing prescribed medications through various routes (oral, IV, etc.) as part of your treatment.',
                'emergency consultation' => 'A prompt medical evaluation by a doctor for an urgent health issue.',
                'iv fluid administration' => 'Delivering fluids and electrolytes directly into your vein to keep you hydrated and support your body\'s functions.',
                'pregnancy-related emergency assessment' => 'An urgent medical check-up for pregnant women to ensure the safety of both the mother and the baby.',
                'consultation' => 'A standard medical check-up to discuss your health concerns with a doctor.',
                'initial medical assessment' => 'A comprehensive first evaluation by medical staff to determine your immediate healthcare needs.',
                'ambulance reception' => 'The initial intake and stabilization of a patient arriving via ambulance at the emergency department.',
                'nebulization' => 'A treatment that turns liquid medicine into a mist you inhale to help open up your airways.',
                'iv insertion' => 'The placement of a small tube into your vein to deliver fluids or medication.',
                'referral coordination' => 'Arranging for you to see a specialist or receive care at another facility.',
                'pediatric emergency treatment' => 'Urgent medical care provided to infants, children, and adolescents for acute illnesses or injuries.',
                'wound dressing (er)' => 'Cleaning and covering a wound in the emergency room to prevent infection and promote healing.',
                'wound dressing' => 'Cleaning and applying a protective covering to a wound to help it heal.',
                'pulse oximetry' => 'A test that measures the oxygen level in your blood.',
                'blood sugar testing' => 'A quick test to check the amount of sugar (glucose) in your blood.'
            ];
            
            foreach ($missing as $k) {
                if (isset($hardcoded[$k])) {
                    $by_norm[$k] = $hardcoded[$k];
                } else {
                    foreach ($hardcoded as $hk => $hv) {
                        if (strpos($k, $hk) !== false) {
                            $by_norm[$k] = $hv;
                            break;
                        }
                    }
                }
            }
        }

        foreach ($norm_to_orig as $n => $orig) {
            if (isset($by_norm[$n]) && $by_norm[$n] !== '') {
                $result[$orig] = $by_norm[$n];
            }
        }
        return $result;
    }

    function resolve_patient_explanation(array $rec, array $explanations): string
    {
        $own = trim((string)($rec['patient_explanation'] ?? ''));
        if ($own !== '' && strpos($own, 'This service covers') === false) return $own;

        $svc = (string)($rec['service_description'] ?? '');

        if (isset($explanations[$svc]) && $explanations[$svc] !== '') {
            return $explanations[$svc];
        }
        $n = _exp_norm($svc);
        foreach ($explanations as $k => $v) {
            if ($v !== '' && _exp_norm($k) === $n) return $v;
        }
        
        return $own;
    }
}

if (!function_exists('render_service_details_cell')) {
    function render_service_details_cell(array $rec, array $explanations, array $opts = []): string
    {
        $show_explanation = $opts['show_explanation'] ?? true;
        $compact          = $opts['compact'] ?? false;
        $empty_text       = $opts['empty_text'] ?? 'No patient-friendly explanation on file.';

        $svc_desc = (string)($rec['service_description'] ?? '');
        $cat      = trim((string)($rec['category'] ?? ''));
        $exp      = resolve_patient_explanation($rec, $explanations);

        $html  = '<div class="service-cell' . ($compact ? ' compact' : '') . '">';
        $html .= '<div class="svc-title">' . htmlspecialchars($svc_desc) . '</div>';

        if ($cat !== '') {
            $html .= '<div class="cat"><i class="fas fa-tag"></i> ' . htmlspecialchars($cat) . '</div>';
        }

        if ($show_explanation) {
            if ($exp !== '') {
                $html .= '<div class="explanation">'
                       . '<strong>Patient Understanding:</strong><br>'
                       . htmlspecialchars($exp)
                       . '</div>';
            } else {
                $html .= '<div class="explanation empty"><em>' . htmlspecialchars($empty_text) . '</em></div>';
            }
        }

        $html .= '</div>';
        return $html;
    }
}