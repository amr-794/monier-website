<?php
require_once 'includes/functions.php';
if (!is_student()) redirect('login.php');

$quiz_id = intval($_GET['id'] ?? 0);
if (!$quiz_id) die('معرف الاختبار مطلوب.');

require_once 'includes/db_connection.php';
$pdo = get_db_connection();
$user_id = current_user()['id'];

$quiz = $pdo->prepare("SELECT q.*, l.title as lecture_title FROM quizzes q JOIN lectures l ON q.lecture_id = l.id WHERE q.id = ? AND q.is_active = 1");
$quiz->execute([$quiz_id]);
$quiz_data = $quiz->fetch();
if (!$quiz_data) die('الاختبار غير متوفر.');

// التحقق من أن الطالب لم يؤد الاختبار من قبل
$attempt_check = $pdo->prepare("SELECT id FROM student_quiz_attempts WHERE student_id = ? AND quiz_id = ?");
$attempt_check->execute([$user_id, $quiz_id]);
if ($attempt_check->fetch()) die('لقد قمت بإجراء هذا الاختبار بالفعل.');

$questions = $pdo->prepare("
    SELECT q.id, q.question_text 
    FROM quiz_questions q 
    WHERE q.quiz_id = ? 
    ORDER BY RAND()");
$questions->execute([$quiz_id]);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الاختبار: <?= htmlspecialchars($quiz_data['title']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="quiz-page-body">
    <div class="quiz-container">
        <header class="quiz-header">
            <h1><?= htmlspecialchars($quiz_data['title']) ?></h1>
            <div id="timer" class="quiz-timer">--:--</div>
        </header>
        
        <form id="quiz-form" action="api/quiz_handler.php" method="POST">
            <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <?php foreach($questions->fetchAll() as $index => $q): ?>
            <div class="question-card">
                <h3>السؤال <?= $index + 1 ?>: <?= htmlspecialchars($q['question_text']) ?></h3>
                <div class="options-list">
                    <?php
                    $options = $pdo->prepare("SELECT id, option_text FROM question_options WHERE question_id = ? ORDER BY RAND()");
                    $options->execute([$q['id']]);
                    foreach($options->fetchAll() as $opt):
                    ?>
                    <label class="option-label">
                        <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $opt['id'] ?>" required>
                        <span><?= htmlspecialchars($opt['option_text']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <footer class="quiz-footer">
                <button type="submit" class="btn btn-primary btn-lg">إنهاء وتسليم الإجابات</button>
            </footer>
        </form>
    </div>

<script>
    const QUIZ_DATA = {
        quizId: <?= $quiz_id ?>,
        timeLimit: <?= $quiz_data['time_limit_minutes'] ?>
    };
</script>
<script src="assets/js/quiz_timer.js"></script>
</body>
</html>