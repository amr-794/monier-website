<?php
$page_title = 'إدارة الأكواد';
include 'partials/header.php';
require_once __DIR__ . '/../includes/db_connection.php';

$message = '';
// معالجة إنشاء أكواد جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_codes'])) {
    $lecture_id = intval($_POST['lecture_id']);
    $count = intval($_POST['count']);
    $prefix = preg_replace("/[^a-zA-Z0-9_]/", "", sanitize_input($_POST['prefix'])); // تنقية البادئة

    if ($lecture_id > 0 && $count > 0 && $count <= 5000) { // حد أقصى 5000 كود مرة واحدة
        $generated_codes = [];
        $stmt = $pdo->prepare("INSERT INTO codes (code_value, lecture_id) VALUES (?, ?)");
        for ($i = 0; $i < $count; $i++) {
            $new_code = ($prefix ? $prefix . '-' : '') . strtoupper(bin2hex(random_bytes(6)));
            try {
                $stmt->execute([$new_code, $lecture_id]);
                $generated_codes[] = $new_code;
            } catch(PDOException $e) {
                // في حالة تكرار الكود (نادر جدًا)، حاول مرة أخرى
                $i--; 
            }
        }
        $message = "<div class='alert alert-success'>تم إنشاء " . count($generated_codes) . " أكواد بنجاح.</div>";
        $message .= "<div class='codes-output'>";
        $message .= "<textarea rows='10' class='form-control' readonly>" . implode("\n", $generated_codes) . "</textarea>";
        $message .= "</div>";
    } else {
        $message = "<div class='alert alert-danger'>بيانات غير صالحة. تأكد من اختيار محاضرة وعدد أكواد (1-5000).</div>";
    }
}

// معالجة حذف وتفعيل/تعطيل
if (isset($_GET['action'])) {
    $code_id = intval($_GET['id']);
    if ($_GET['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM codes WHERE id = ? AND is_used = 0"); // لا يمكن حذف كود مستخدم
        $stmt->execute([$code_id]);
    } elseif ($_GET['action'] == 'toggle') {
        $stmt = $pdo->prepare("UPDATE codes SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$code_id]);
    }
}

// جلب كل الأكواد
$codes = $pdo->query("
    SELECT c.*, l.title as lecture_title, s.name as student_name 
    FROM codes c 
    JOIN lectures l ON c.lecture_id = l.id 
    LEFT JOIN students s ON c.used_by_student_id = s.id
    ORDER BY c.created_at DESC LIMIT 500 -- عرض آخر 500 كود فقط للأداء
")->fetchAll();

$lectures = $pdo->query("SELECT id, title FROM lectures WHERE is_active = 1 ORDER BY title")->fetchAll();
?>

<div class="page-header"><h1>إدارة الأكواد</h1></div>

<?= $message ?>

<div class="card">
<div class="card-header"><h2>إنشاء أكواد جديدة</h2></div>
<div class="card-body">
    <form action="codes.php" method="POST" class="form-grid">
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
            <label for="count">عدد الأكواد (1-5000):</label>
            <input type="number" name="count" value="10" min="1" max="5000" required>
        </div>
        <div class="form-group">
            <label for="prefix">بادئة للكود (اختياري):</label>
            <input type="text" name="prefix" placeholder="مثال: CHEM101">
        </div>
        <div class="form-actions">
            <button type="submit" name="generate_codes" class="btn btn-primary">إنشاء الأكواد</button>
        </div>
    </form>
</div>
</div>


<div class="table-container">
    <h2>الأكواد المنشأة (آخر 500)</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>الكود</th>
                <th>المحاضرة</th>
                <th>الحالة</th>
                <th>استخدم بواسطة</th>
                <th>تاريخ الاستخدام</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($codes as $code): ?>
                <tr>
                    <td><code class="code-value"><?= htmlspecialchars($code['code_value']) ?></code></td>
                    <td><?= htmlspecialchars($code['lecture_title']) ?></td>
                    <td>
                        <?php if ($code['is_used']): ?>
                            <span class="status-badge status-used">مستخدم</span>
                        <?php else: ?>
                            <span class="status-badge status-<?= $code['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $code['is_active'] ? 'صالح' : 'معطل' ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($code['student_name'] ?? '---') ?></td>
                    <td><?= $code['used_at'] ? date('Y-m-d H:i', strtotime($code['used_at'])) : '---' ?></td>
                    <td class="actions">
                        <?php if (!$code['is_used']): ?>
                            <a href="?action=toggle&id=<?= $code['id'] ?>" class="btn btn-sm btn-<?= $code['is_active'] ? 'warning' : 'success' ?>">
                                <?= $code['is_active'] ? 'تعطيل' : 'تفعيل' ?>
                            </a>
                             <a href="?action=delete&id=<?= $code['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد؟')">حذف</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'partials/footer.php'; ?>