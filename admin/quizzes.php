<?php
$page_title = 'إدارة الاختبارات';
include 'partials/header.php';
require_once __DIR__ . '/../includes/db_connection.php';
$pdo = get_db_connection();

if (isset($_GET['action'])) {
    $quiz_id = intval($_GET['id']);
    if ($_GET['action'] == 'toggle') {
        $stmt = $pdo->prepare("UPDATE quizzes SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$quiz_id]);
    } elseif ($_GET['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
        $stmt->execute([$quiz_id]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_quiz'])) {
    $lecture_id = intval($_POST['lecture_id']);
    $title = sanitize_input($_POST['title']);
    $time_limit = intval($_POST['time_limit']);
    
    if ($lecture_id && $title && $time_limit > 0) {
        $stmt = $pdo->prepare("INSERT INTO quizzes (lecture_id, title, time_limit_minutes, is_active) VALUES (?, ?, ?, 1)");
        $stmt->execute([$lecture_id, $title, $time_limit]);
    }
}

// --- *** FIX STARTS HERE *** ---
// The `q.created_at` column was removed. Sorting by `id` DESC to get newest first.
$quizzes = $pdo->query("
    SELECT q.*, l.title as lecture_title, 
           (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id) as question_count
    FROM quizzes q 
    JOIN lectures l ON q.lecture_id = l.id 
    ORDER BY q.id DESC 
")->fetchAll();
// --- *** FIX ENDS HERE *** ---


$lectures = $pdo->query("SELECT id, title FROM lectures ORDER BY title")->fetchAll();
?>

<div class="page-header">
    <h1>إدارة الاختبارات</h1>
</div>

<div class="card">
    <div class="card-header">
        <h3>إنشاء اختبار جديد</h3>
    </div>
    <div class="card-body">
        <form action="quizzes.php" method="POST" class="form-grid">
            <div class="form-group">
                <label for="lecture_id">اختر المحاضرة:</label>
                <select name="lecture_id" required>
                    <option value="">-- اختر محاضرة --</option>
                    <?php foreach ($lectures as $lecture): ?>
                        <option value="<?= $lecture['id'] ?>"><?= htmlspecialchars($lecture['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="title">عنوان الاختبار:</label>
                <input type="text" name="title" required placeholder="مثال: اختبار الفصل الأول">
            </div>
            <div class="form-group">
                <label for="time_limit">مدة الاختبار (بالدقائق):</label>
                <input type="number" name="time_limit" min="1" value="30" required>
            </div>
            <div class="form-actions">
                <button type="submit" name="create_quiz" class="btn btn-primary">إنشاء الاختبار</button>
            </div>
        </form>
    </div>
</div>

<div class="table-container">
    <h2>الاختبارات الحالية</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>عنوان الاختبار</th>
                <th>المحاضرة التابعة له</th>
                <th>عدد الأسئلة</th>
                <th>المدة</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($quizzes as $quiz): ?>
            <tr>
                <td><strong><?= htmlspecialchars($quiz['title']) ?></strong></td>
                <td><?= htmlspecialchars($quiz['lecture_title']) ?></td>
                <td><?= $quiz['question_count'] ?></td>
                <td><?= $quiz['time_limit_minutes'] ?> دقيقة</td>
                <td>
                    <span class="status-badge status-<?= $quiz['is_active'] ? 'active' : 'inactive' ?>">
                        <?= $quiz['is_active'] ? 'مفعل' : 'معطل' ?>
                    </span>
                </td>
                <td class="actions">
                    <a href="manage_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-sm btn-primary">إدارة الأسئلة</a>
                    <a href="?action=toggle&id=<?= $quiz['id'] ?>" class="btn btn-sm btn-<?= $quiz['is_active'] ? 'warning' : 'success' ?>">
                        <?= $quiz['is_active'] ? 'تعطيل' : 'تفعيل' ?>
                    </a>
                    <a href="?action=delete&id=<?= $quiz['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('متأكد من حذف هذا الاختبار وكل أسئلته؟')">حذف</a>
                </td>
            </tr>
            <?php endforeach; ?>
             <?php if(empty($quizzes)): ?>
             <tr><td colspan="6" class="text-center">لا توجد اختبارات.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'partials/footer.php'; ?>