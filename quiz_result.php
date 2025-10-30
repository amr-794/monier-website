<?php
require_once 'includes/functions.php';
if (!is_student()) {
    redirect('login.php');
}
require_once 'includes/header.php'; // Important for session validation

$user = current_user();
$student_id = $user['id'];
$attempt_id = intval($_GET['attempt_id'] ?? 0);
if (!$attempt_id) die('نتيجة غير صالحة.');

require_once 'includes/db_connection.php';
$pdo = get_db_connection();

$stmt = $pdo->prepare("
    SELECT a.*, q.title as quiz_title, l.id as lecture_id 
    FROM student_quiz_attempts a 
    JOIN quizzes q ON a.quiz_id = q.id 
    JOIN lectures l ON q.lecture_id = l.id
    WHERE a.id = ? AND a.student_id = ?
");
$stmt->execute([$attempt_id, $student_id]);
$attempt = $stmt->fetch();

if (!$attempt) die('نتيجة غير صالحة أو لا تملك الصلاحية لعرضها.');
$percentage = ($attempt['total_questions'] > 0) ? round(($attempt['score'] / $attempt['total_questions']) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <title>نتيجة الاختبار</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/result.css">
</head>
<body class="result-page-body">
    <div class="result-container">
        <h1>نتيجة اختبار: <?= htmlspecialchars($attempt['quiz_title']) ?></h1>
        
        <div class="result-box">
            <div class="score-circle">
                <span class="score"><?= $attempt['score'] ?>/<?= $attempt['total_questions'] ?></span>
                <span class="percentage"><?= $percentage ?>%</span>
            </div>
            
            <div class="result-feedback">
                <?php
                if ($percentage >= 85) echo "<h2>أداء ممتاز!</h2><p>لقد أظهرت فهماً رائعاً للمادة. استمر في هذا التفوق.</p>";
                elseif ($percentage >= 65) echo "<h2>نتيجة جيدة!</h2><p>أداؤك جيد، يمكنك تحقيق الأفضل بمزيد من المراجعة.</p>";
                else echo "<h2>حاول مرة أخرى!</h2><p>لا تيأس، المراجعة المستمرة هي مفتاح النجاح. يمكنك طلب المساعدة إذا احتجت.</p>";
                ?>
            </div>
        </div>
        
        <div class="result-actions">
            <a href="view_lecture.php?id=<?= $attempt['lecture_id'] ?>" class="btn btn-primary">العودة للمحاضرة</a>
            <a href="student/index.php" class="btn btn-secondary">الذهاب للصفحة الرئيسية</a>
        </div>
    </div>
</body>
</html>