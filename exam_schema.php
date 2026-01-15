<?php
require_once __DIR__ . '/db_conn.php';

function ensure_exam_schema(): void {
    $queries = [
        "CREATE TABLE IF NOT EXISTS uedu_question_banks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS uedu_questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bank_id INT NOT NULL,
            question_text TEXT NOT NULL,
            choice1 TEXT NOT NULL,
            choice2 TEXT NOT NULL,
            choice3 TEXT NOT NULL,
            choice4 TEXT NOT NULL,
            correct_answer TINYINT NOT NULL,
            score INT NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_bank_id (bank_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS uedu_exams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            bank_id INT NOT NULL,
            question_count INT NOT NULL DEFAULT 0,
            pass_score INT NOT NULL DEFAULT 0,
            is_active TINYINT NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_course_id (course_id),
            INDEX idx_bank_id (bank_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS uedu_exam_questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            exam_id INT NOT NULL,
            question_id INT NOT NULL,
            question_order INT NOT NULL DEFAULT 1,
            INDEX idx_exam_id (exam_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS uedu_exam_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            exam_id INT NOT NULL,
            user_id INT NOT NULL,
            score INT NOT NULL DEFAULT 0,
            passed TINYINT NOT NULL DEFAULT 0,
            taken_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_exam_user (exam_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS uedu_course_completion_settings (
            course_id INT PRIMARY KEY,
            progress_required INT NOT NULL DEFAULT 80,
            exam_required_score INT NOT NULL DEFAULT 60,
            exam_enabled TINYINT NOT NULL DEFAULT 1,
            completion_mode VARCHAR(10) NOT NULL DEFAULT 'auto',
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS uedu_course_completions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            user_id INT NOT NULL,
            status VARCHAR(10) NOT NULL DEFAULT 'pending',
            completed_at DATETIME NULL,
            method VARCHAR(10) NOT NULL DEFAULT 'auto',
            UNIQUE KEY uniq_course_user (course_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    $pdo = db();
    foreach ($queries as $sql) {
        $pdo->exec($sql);
    }
}