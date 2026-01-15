<?php
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/exam_schema.php';

function get_completion_settings(int $course_id): array {
    ensure_exam_schema();
    $stmt = db()->prepare("
        SELECT progress_required, exam_required_score, exam_enabled, completion_mode
        FROM uedu_course_completion_settings
        WHERE course_id=?
        LIMIT 1
    ");
    $stmt->execute([$course_id]);
    $settings = $stmt->fetch();

    if (!$settings) {
        return [
            'progress_required' => 80,
            'exam_required_score' => 60,
            'exam_enabled' => 1,
            'completion_mode' => 'auto'
        ];
    }

    return [
        'progress_required' => intval($settings['progress_required']),
        'exam_required_score' => intval($settings['exam_required_score']),
        'exam_enabled' => intval($settings['exam_enabled']),
        'completion_mode' => $settings['completion_mode'] === 'manual' ? 'manual' : 'auto'
    ];
}

function save_completion_settings(
    int $course_id,
    int $progress_required,
    int $exam_required_score,
    int $exam_enabled,
    string $completion_mode
): void {
    ensure_exam_schema();
    $mode = $completion_mode === 'manual' ? 'manual' : 'auto';
    $stmt = db()->prepare("
        INSERT INTO uedu_course_completion_settings
            (course_id, progress_required, exam_required_score, exam_enabled, completion_mode)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            progress_required=VALUES(progress_required),
            exam_required_score=VALUES(exam_required_score),
            exam_enabled=VALUES(exam_enabled),
            completion_mode=VALUES(completion_mode)
    ");
    $stmt->execute([$course_id, $progress_required, $exam_required_score, $exam_enabled, $mode]);
}

function calculate_course_progress(int $user_id, int $course_id): array {
    $stmt = db()->prepare("SELECT COUNT(*) FROM uedu_curriculum WHERE course_id=?");
    $stmt->execute([$course_id]);
    $total = intval($stmt->fetchColumn());

    $stmt = db()->prepare("
        SELECT COUNT(*)
        FROM uedu_curriculum uc
        JOIN uedu_progress p
          ON p.content_id=uc.content_id
         AND p.user_id=? AND p.course_id=? AND p.is_completed=1
        WHERE uc.course_id=?
    ");
    $stmt->execute([$user_id, $course_id, $course_id]);
    $done = intval($stmt->fetchColumn());

    $progress = $total > 0 ? floor(($done / $total) * 100) : 0;

    return [
        'total' => $total,
        'done' => $done,
        'progress' => $progress
    ];
}

function get_active_exam(int $course_id): ?array {
    ensure_exam_schema();
    $stmt = db()->prepare("
        SELECT id, title, bank_id, question_count, pass_score, is_active
        FROM uedu_exams
        WHERE course_id=? AND is_active=1
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$course_id]);
    $exam = $stmt->fetch();
    return $exam ?: null;
}

function get_latest_exam_result(int $user_id, int $exam_id): ?array {
    ensure_exam_schema();
    $stmt = db()->prepare("
        SELECT score, passed, taken_at
        FROM uedu_exam_results
        WHERE user_id=? AND exam_id=?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id, $exam_id]);
    $result = $stmt->fetch();
    return $result ?: null;
}

function get_completion_record(int $user_id, int $course_id): ?array {
    ensure_exam_schema();
    $stmt = db()->prepare("
        SELECT status, completed_at, method
        FROM uedu_course_completions
        WHERE user_id=? AND course_id=?
        LIMIT 1
    ");
    $stmt->execute([$user_id, $course_id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function upsert_completion_record(int $user_id, int $course_id, string $status, string $method): void {
    ensure_exam_schema();
    $validStatus = $status === 'completed' ? 'completed' : 'pending';
    $validMethod = $method === 'manual' ? 'manual' : 'auto';
    $completedAt = $validStatus === 'completed' ? date('Y-m-d H:i:s') : null;

    $stmt = db()->prepare("
        INSERT INTO uedu_course_completions (course_id, user_id, status, completed_at, method)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            status=VALUES(status),
            completed_at=VALUES(completed_at),
            method=VALUES(method)
    ");
    $stmt->execute([$course_id, $user_id, $validStatus, $completedAt, $validMethod]);
}

function evaluate_completion(int $user_id, int $course_id): array {
    $settings = get_completion_settings($course_id);
    $progressInfo = calculate_course_progress($user_id, $course_id);
    $exam = get_active_exam($course_id);
    $examResult = null;
    $examScore = null;

    if ($exam) {
        $examResult = get_latest_exam_result($user_id, intval($exam['id']));
        if ($examResult) {
            $examScore = intval($examResult['score']);
        }
    }

    $meetsProgress = $progressInfo['progress'] >= $settings['progress_required'];
    $examEnabled = intval($settings['exam_enabled']) === 1 && $exam !== null;
    $requiredScore = $settings['exam_required_score'];

    $meetsExam = true;
    if ($examEnabled) {
        $meetsExam = $examScore !== null && $examScore >= $requiredScore;
    }

    return [
        'settings' => $settings,
        'progress' => $progressInfo,
        'exam' => $exam,
        'exam_result' => $examResult,
        'exam_score' => $examScore,
        'meets_progress' => $meetsProgress,
        'meets_exam' => $meetsExam,
        'exam_enabled' => $examEnabled,
        'required_score' => $requiredScore
    ];
}

function sync_completion_status(int $user_id, int $course_id, array $evaluation): array {
    $status = get_completion_record($user_id, $course_id);
    $meetsAll = $evaluation['meets_progress'] && $evaluation['meets_exam'];
    $mode = $evaluation['settings']['completion_mode'];

    if ($meetsAll) {
        if ($mode === 'auto') {
            upsert_completion_record($user_id, $course_id, 'completed', 'auto');
        } else {
            if (!$status || $status['status'] !== 'completed') {
                upsert_completion_record($user_id, $course_id, 'pending', 'manual');
            }
        }
    }

    return get_completion_record($user_id, $course_id) ?? [];
}