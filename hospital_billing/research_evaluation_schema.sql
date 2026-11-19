-- Research evaluation extension for Hospital Billing System
-- Safe additive migration: creates new tables only; does not alter or delete existing records.
-- Apply once through phpMyAdmin or: mysql -u root hospital_billing < research_evaluation_schema.sql

CREATE TABLE IF NOT EXISTS evaluation_datasets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  dataset_name VARCHAR(180) NOT NULL,
  description TEXT NULL,
  source_note TEXT NULL,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_eval_dataset_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evaluation_test_cases (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  dataset_id BIGINT UNSIGNED NOT NULL,
  case_code VARCHAR(80) NOT NULL,
  billing_description TEXT NOT NULL,
  expected_category VARCHAR(120) NOT NULL,
  expected_explanation TEXT NOT NULL,
  expected_meaning_code VARCHAR(120) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_eval_case_code (dataset_id, case_code),
  KEY idx_eval_case_dataset (dataset_id, is_active),
  CONSTRAINT fk_eval_case_dataset FOREIGN KEY (dataset_id)
    REFERENCES evaluation_datasets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evaluation_runs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  dataset_id BIGINT UNSIGNED NOT NULL,
  run_name VARCHAR(180) NOT NULL,
  baseline_mode ENUM('conventional_keyword','manual_reference') NOT NULL DEFAULT 'conventional_keyword',
  status ENUM('completed','incomplete','failed') NOT NULL DEFAULT 'incomplete',
  executed_by INT NULL,
  started_at DATETIME NULL,
  completed_at DATETIME NULL,
  notes TEXT NULL,
  PRIMARY KEY (id),
  KEY idx_eval_run_dataset (dataset_id, completed_at),
  CONSTRAINT fk_eval_run_dataset FOREIGN KEY (dataset_id)
    REFERENCES evaluation_datasets(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evaluation_results (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  run_id BIGINT UNSIGNED NOT NULL,
  test_case_id BIGINT UNSIGNED NOT NULL,
  system_type ENUM('traditional','proposed') NOT NULL,
  expected_category VARCHAR(120) NOT NULL,
  predicted_category VARCHAR(120) NOT NULL,
  generated_explanation TEXT NULL,
  processing_time_ms DECIMAL(12,4) NOT NULL DEFAULT 0,
  category_correct TINYINT(1) NOT NULL DEFAULT 0,
  expected_meaning_code VARCHAR(120) NULL,
  evaluator_meaning_code VARCHAR(120) NULL,
  semantic_review_status ENUM('unreviewed','correct','incorrect') NOT NULL DEFAULT 'unreviewed',
  evaluator_id INT NULL,
  evaluator_notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_eval_result (run_id, test_case_id, system_type),
  KEY idx_eval_result_run_system (run_id, system_type),
  CONSTRAINT fk_eval_result_run FOREIGN KEY (run_id)
    REFERENCES evaluation_runs(id) ON DELETE CASCADE,
  CONSTRAINT fk_eval_result_case FOREIGN KEY (test_case_id)
    REFERENCES evaluation_test_cases(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Concept-level semantic annotations let evaluators score meaning, not exact sentence wording.
-- One row represents one expected concept in one generated explanation.
CREATE TABLE IF NOT EXISTS evaluation_semantic_annotations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  run_id BIGINT UNSIGNED NOT NULL,
  test_case_id BIGINT UNSIGNED NOT NULL,
  system_type ENUM('traditional','proposed') NOT NULL,
  concept_code VARCHAR(120) NOT NULL,
  expected_present TINYINT(1) NOT NULL,
  evaluator_found_present TINYINT(1) NOT NULL,
  evaluator_id INT NULL,
  evaluator_notes TEXT NULL,
  reviewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_semantic_annotation (run_id, test_case_id, system_type, concept_code),
  KEY idx_semantic_annotation_run (run_id, system_type),
  CONSTRAINT fk_semantic_annotation_run FOREIGN KEY (run_id)
    REFERENCES evaluation_runs(id) ON DELETE CASCADE,
  CONSTRAINT fk_semantic_annotation_case FOREIGN KEY (test_case_id)
    REFERENCES evaluation_test_cases(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evaluation_survey_questions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  question_code VARCHAR(60) NOT NULL,
  question_text VARCHAR(500) NOT NULL,
  dimension ENUM('readability','clarity','transparency','usefulness','understanding') NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  display_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_eval_question_code (question_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evaluation_survey_responses (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  respondent_code VARCHAR(100) NOT NULL,
  system_type ENUM('traditional','proposed') NOT NULL,
  question_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment TEXT NULL,
  collected_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_eval_survey_group (system_type, question_id),
  KEY idx_eval_survey_respondent (respondent_code),
  CONSTRAINT chk_eval_survey_rating CHECK (rating BETWEEN 1 AND 5),
  CONSTRAINT fk_eval_survey_question FOREIGN KEY (question_id)
    REFERENCES evaluation_survey_questions(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO evaluation_survey_questions
  (question_code, question_text, dimension, display_order)
VALUES
  ('READABILITY', 'The billing information is easy to read.', 'readability', 1),
  ('CLARITY', 'The billing descriptions are clear and understandable.', 'clarity', 2),
  ('TRANSPARENCY', 'The billing charges, deductions, and coverage are clearly explained.', 'transparency', 3),
  ('USEFULNESS', 'The explanations are useful in understanding the billed services.', 'usefulness', 4),
  ('UNDERSTANDING', 'I understand the hospital billing information presented to me.', 'understanding', 5);
