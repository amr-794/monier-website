<?php
$page_title = 'إدارة أسئلة الاختبار';
include 'partials/header.php';
require_once __DIR__ . '/../includes/db_connection.php';

$quiz_id = intval($_GET['id'] ?? 0);
if (!$quiz_id) die('معرف اختبار غير صالح.');
$pdo = get_db_connection();

$quiz = $pdo->query("SELECT q.*, l.title as lecture_title FROM quizzes q JOIN lectures l ON q.lecture_id=l.id WHERE q.id = $quiz_id")->fetch();
if (!$quiz) die('الاختبار غير موجود.');

if (isset($_POST['add_question'])) {
    $question_text = sanitize_input($_POST['question_text']);
    $options = $_POST['options'];
    $correct_option_index = intval($_POST['correct_option']);

    if ($question_text && count(array_filter($options)) >= 2) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO quiz_questions (quiz_id, question_text) VALUES (?, ?)");
        $stmt->execute([$quiz_id, $question_text]);
        $question_id = $pdo->lastInsertId();

        $opt_stmt = $pdo->prepare("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
        foreach ($options as $index => $option_text) {
            if (!empty($option_text)) {
                $is_correct = ($index == $correct_option_index) ? 1 : 0;
                $opt_stmt->execute([$question_id, sanitize_input($option_text), $is_correct]);
            }
        }
        $pdo->commit();
    }
}

if(isset($_GET['delete_question'])){
    $question_id = intval($_GET['delete_question']);
    $stmt = $pdo->prepare("DELETE FROM quiz_questions WHERE id = ? AND quiz_id = ?");
    $stmt->execute([$question_id, $quiz_id]);
}

$questions = $pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ?");
$questions->execute([$quiz_id]);
?>

<div class="page-header">
    <h1>إدارة أسئلة اختبار: "<?= htmlspecialchars($quiz['title']) ?>"</h1>
    <p>
        التابع لمحاضرة: "<?= htmlspecialchars($quiz['lecture_title']) ?>" |
        المدة: <?= $quiz['time_limit_minutes'] ?> دقيقة
    </p>
    <a href="quizzes.php" class="btn btn-secondary">العودة لقائمة الاختبارات</a>
</div>

<div class="grid-container" style="grid-template-columns: 1fr 2fr; align-items: flex-start;">
    <div class="card">
        <div class="card-header">
            <h3>إضافة سؤال جديد</h3>
        </div>
        <div class="card-body">
            <form action="manage_quiz.php?id=<?= $quiz_id ?>" method="POST">
                <div class="form-group">
                    <label>نص السؤال:</label>
                    <textarea name="question_text" required rows="4"></textarea>
                </div>
                <hr>
                <h4>الخيارات (حدد الإجابة الصحيحة):</h4>
                <?php for($i = 0; $i < 4; $i++): ?>
                <div class="form-group option-group">
                    <input type="radio" name="correct_option" value="<?= $i ?>" id="radio<?= $i ?>" <?= $i==0 ? 'checked' : ''?>>
                    <label for="radio<?= $i ?>">الخيار <?= $i+1 ?>:</label>
                    <input type="text" name="options[]" >
                </div>
                <?php endfor; ?>
                <div class="form-actions">
                    <button type="submit" name="add_question" class="btn btn-primary">إضافة السؤال</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>الأسئلة الحالية</h3>
        </div>
        <div class="card-body">
        <?php foreach ($questions as $q_index => $question): ?>
            <div class="question-block">
                <div class="question-header">
                    <strong>السؤال <?= $q_index + 1 ?>: <?= htmlspecialchars($question['question_text']) ?></strong>
                    <a href="?id=<?= $quiz_id ?>&delete_question=<?= $question['id'] ?>" onclick="return confirm('متأكد؟')" class="btn btn-sm btn-danger">حذف</a>
                </div>
                <ul class="options-list">
                    <?php
                    $options_stmt = $pdo->prepare("SELECT * FROM question_options WHERE question_id = ?");
                    $options_stmt->execute([$question['id']]);
                    foreach($options_stmt->fetchAll() as $option):
                    ?>
                        <li class="<?= $option['is_correct'] ? 'correct-answer' : '' ?>">
                            <?= htmlspecialchars($option['option_text']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
        <?php if ($questions->rowCount() === 0): echo "<p class='text-center'>لا توجد أسئلة بعد.</p>"; endif; ?>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>