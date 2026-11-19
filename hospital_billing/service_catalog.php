<?php
// service_catalog.php

function get_service_categories($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT c.id, c.category_name AS name, c.category_code,
                   MAX(CASE WHEN s.price > 0 THEN 1 ELSE 0 END) AS has_default_price
            FROM billing_categories c
            LEFT JOIN services s ON s.category_id = c.id AND s.is_active = 1
            GROUP BY c.id, c.category_name, c.category_code
            ORDER BY c.category_name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function catalog_json($pdo) {
    try {
        // MAGIC QUERY: 
        // 1. Kunin ang explanation sa services table.
        // 2. Kung blanko, hanapin sa semantic_dictionary (dictionary fallback).
        // 3. Kung talagang wala, mag-generate ng generic text.
        $stmt = $pdo->query("
            SELECT s.id, s.service_name, s.category_id, s.price AS default_price,
                   COALESCE(
                       NULLIF(s.patient_explanation, ''),
                       (SELECT sd.patient_explanation FROM semantic_dictionary sd 
                        WHERE LOWER(s.service_name) LIKE CONCAT('%', LOWER(sd.technical_term), '%') 
                           OR LOWER(sd.synonym_terms) LIKE CONCAT('%', LOWER(s.service_name), '%')
                        LIMIT 1),
                       CONCAT('This service covers ', s.service_name, '. Please ask your nurse or doctor for more details.')
                   ) AS patient_explanation,
                   CASE WHEN s.price > 0 THEN 1 ELSE 0 END AS has_default_price
            FROM services s
            WHERE s.is_active = 1
            ORDER BY s.service_name
        ");
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return json_encode(['services' => $services]);
    } catch (PDOException $e) {
        return json_encode(['services' => []]);
    }
}

function get_service_by_id($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}
?>