<?php
/**
 * classify_lib.php
 * Shared Rule-Based Decision + Semantic Analysis logic.
 * Used by: classify_billing.php, input_billing.php, add_patient.php, add_transaction.php
 */

if (!function_exists('classify_service')) {

    function classify_service($pdo, $description) {
        $result = [
            'category_id'         => null,
            'category_name'       => null,
            'predicted_category'  => null,
            'matched_rule_id'     => null,
            'matched_rule'        => null,
            'matched_keyword'     => null,
            'patient_explanation' => null,
            'processing_ms'       => 0,
        ];

        $description = trim((string)$description);
        if ($description === '' || mb_strlen($description) < 2) {
            return $result;
        }

        $start = microtime(true);
        $category_desc = null;

        /* ----- 1. Rule-Based Decision ----- */
        try {
            $stmt = $pdo->query("
                SELECT r.id, r.rule_name, r.keywords, r.priority,
                       r.category_id, c.category_name, c.description AS category_desc
                FROM rules r
                JOIN billing_categories c ON r.category_id = c.id
                WHERE r.is_active = 1
                ORDER BY r.priority DESC, r.id ASC
            ");

            $lower_desc = mb_strtolower($description, 'UTF-8');

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $rule) {
                $keywords = array_filter(
                    array_map('trim', explode(',', mb_strtolower($rule['keywords'], 'UTF-8')))
                );

                foreach ($keywords as $kw) {
                    if ($kw === '') continue;

                    $pattern = '/\b' . preg_quote($kw, '/') . '\b/iu';

                    if (preg_match($pattern, $lower_desc)) {
                        $result['matched_rule']       = $rule['rule_name'];
                        $result['matched_rule_id']    = (int)$rule['id'];
                        $result['matched_keyword']    = $kw;
                        $result['category_id']        = (int)$rule['category_id'];
                        $result['category_name']      = $rule['category_name'];
                        $result['predicted_category'] = $rule['category_name'];
                        $category_desc                = $rule['category_desc'];
                        break 2;
                    }
                }
            }
        } catch (PDOException $e) {
            error_log('classify_service [rule] : ' . $e->getMessage());
        }

        /* ----- 2. Semantic Analysis ----- */
        $translated_tokens = [];
        $any_match_found   = false;

        try {
            $tokens = preg_split('/\s+/', $description);
            $lookup = $pdo->prepare("
                SELECT patient_explanation
                FROM semantic_dictionary
                WHERE LOWER(technical_term) = LOWER(?)
                   OR LOWER(synonym_terms) LIKE LOWER(?)
                LIMIT 1
            ");

            foreach ($tokens as $token) {
                $clean = preg_replace('/[^a-zA-Z0-9\-]/', '', $token);

                if (strlen($clean) < 2) { $translated_tokens[] = $token; continue; }

                $lookup->execute([$clean, '%' . $clean . '%']);
                $row = $lookup->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    $translated_tokens[] = $row['patient_explanation'];
                    $any_match_found     = true;
                } else {
                    $translated_tokens[] = $token;
                }
            }
        } catch (PDOException $e) {
            error_log('classify_service [semantic] : ' . $e->getMessage());
            $translated_tokens = [$description];
        }

        $patient_explanation = implode(' ', $translated_tokens);

        /* ----- 3. Fallback ----- */
        if (!$any_match_found || $patient_explanation === $description) {
            $patient_explanation = '';
            if (!empty($category_desc)) {
                $patient_explanation = $result['category_name'] . ' – ' . $category_desc;
            } elseif (!empty($result['category_name'])) {
                $patient_explanation = $result['category_name'] . ' – medical service or procedure performed during your hospital visit.';
            } else {
                $patient_explanation = 'Service: ' . $description;
            }
        }

        $result['patient_explanation'] = $patient_explanation;
        $result['processing_ms']       = (int)round((microtime(true) - $start) * 1000);

        return $result;
    }
}