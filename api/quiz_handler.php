<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

if (!is_student()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}
if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$student_id = current_user()['id'];
$quiz_id = intval($_POST['quiz_id'] ?? 0);
$answers = $_POST['answers'] ?? []; // [question_id => option_id]

$pdo = get_db_connection();
// logic لحساب النتيجة
$score = 0;
$correct_answers_stmt = $pdo->prepare("SELECT id FROM question_options WHERE question_id = ? AND is_correct = 1");

foreach ($answers as $question_id => $option_id) {
    $correct_answers_stmt->execute([intval($question_id)]);
    $correct_option_id = $correct_answers_stmt->fetchColumn();
    if ($correct_option_id && intval($option_id) === $correct_option_id) {
        $score++;
    }
}
$total_questions = count($answers);

// حفظ المحاولة
$save_stmt = $pdo->prepare("INSERT INTO student_quiz_attempts (student_id, quiz_id, score, total_questions) VALUES (?, ?, ?, ?)");
$save_stmt->execute([$student_id, $quiz_id, $score, $total_questions]);
$attempt_id = $pdo->lastInsertId();

// إرجاع النتيجة
echo json_encode([
    'success' => true,
    'attempt_id' => $attempt_id,
    'score' => $score,
    'total' => $total_questions
]);
exit;