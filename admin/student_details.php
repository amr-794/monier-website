<?php
$page_title = 'تفاصيل الطالب';
include 'partials/header.php';
require_once __DIR__ . '/../includes/db_connection.php';

$student_id = intval($_GET['id'] ?? 0);
if (!$student_id) { die('معرف الطالب غير صالح.'); }

$pdo = get_db_connection();
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();
if (!$student) { die('الطالب غير موجود.'); }

$message = '';
// --- معالجة الأوامر ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // تحديث بيانات الطالب (نفس الكود السابق)
    if (isset($_POST['update_student'])) { /* ... */ }

    // تعليق الحساب
    if (isset($_POST['suspend_account'])) {
        $duration = sanitize_input($_POST['suspension_duration']);
        $suspend_until = null;
        if ($duration !== 'permanent') {
            $suspend_until = (new DateTime())->modify($duration)->format('Y-m-d H:i:s');
        }
        $reason = sanitize_input($_POST['suspension_reason']);
        $new_status = 'suspended';
        
        $stmt_suspend = $pdo->prepare("UPDATE students SET status = ?, notes = CONCAT(IFNULL(notes,''), ?), suspend_until = ? WHERE id = ?");
        $stmt_suspend->execute([$new_status, "\n[إيقاف]: " . $reason, $suspend_until, $student_id]);
        $message = "<div class='alert alert-success'>تم إيقاف حساب الطالب.</div>";
    }

    // إعادة تفعيل الحساب
    if (isset($_POST['reactivate_account'])) {
        $stmt_reactivate = $pdo->prepare("UPDATE students SET status = 'active', suspend_until = NULL WHERE id = ?");
        $stmt_reactivate->execute([$student_id]);
        $message = "<div class='alert alert-success'>تم إعادة تفعيل حساب الطالب.</div>";
    }

    // منح صلاحية محاضرة
    if (isset($_POST['grant_access'])) {
        $lecture_id = intval($_POST['lecture_id']);
        $views = intval($_POST['views']);
        if ($lecture_id > 0 && $views > 0) {
            $sql = "INSERT INTO student_lecture_access (student_id, lecture_id, remaining_views) VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE remaining_views = remaining_views + VALUES(remaining_views)";
            $stmt_grant = $pdo->prepare($sql);
            $stmt_grant->execute([$student_id, $lecture_id, $views]);
            $message = "<div class='alert alert-success'>تم منح $views مشاهدات إضافية للطالب.</div>";
        }
    }
}
// طرد المستخدم (يتم عبر GET لسهولة الاستخدام)
if(isset($_GET['action']) && $_GET['action'] === 'kick') {
     $stmt_kick = $pdo->prepare("UPDATE user_sessions SET is_active = 0 WHERE user_id = ? AND user_type = 'student'");
     $stmt_kick->execute([$student_id]);
     $message = "<div class='alert alert-warning'>تم إرسال أمر الطرد. سيتم تسجيل خروج الطالب من جميع الأجهزة خلال الدقائق القادمة.</div>";
}
// تحديث بيانات الطالب للعرض
$stmt->execute([$student_id]);
$student = $stmt->fetch();

$lectures = $pdo->query("SELECT id, title FROM lectures ORDER BY title")->fetchAll();
?>

<div class="page-header">
    <h1>تفاصيل الطالب: <?= htmlspecialchars($student['name']) ?></h1>
    <a href="students.php" class="btn btn-secondary">العودة</a>
</div>
<?= $message ?>
<div class="grid-container" style="grid-template-columns: 2fr 1fr; align-items: flex-start;">
    <!-- الجزء الأيسر: Tabs -->
    <div class="card">
         <!-- (الكود الخاص بعرض معلومات الطالب ومنحه صلاحيات كما في النسخ السابقة) -->
          <div class="card-header"><h3>منح صلاحية محاضرة (يدويًا)</h3></div>
          <div class="card-body">
              <form method="POST">
                  <select name="lecture_id" required>
                      <?php foreach ($lectures as $lecture): ?>
                      <option value="<?= $lecture['id'] ?>"><?= htmlspecialchars($lecture['title']) ?></option>
                      <?php endforeach; ?>
                  </select>
                  <input type="number" name="views" value="3" min="1">
                  <button type="submit" name="grant_access" class="btn btn-success">منح مشاهدات</button>
              </form>
          </div>
    </div>
    
    <!-- الجزء الأيمن: أدوات التحكم -->
    <div class="card">
        <div class="card-header"><h3>إجراءات التحكم</h3></div>
        <div class="card-body">
             <div class="admin-actions">
                 <!-- زر الطرد -->
                 <a href="?id=<?= $student_id ?>&action=kick" class="btn btn-warning btn-block" onclick="return confirm('هل أنت متأكد من طرد هذا الطالب؟ سيتم تسجيل خروجه من جميع جلساته النشطة.')">طرد الطالب</a>
                 <hr>
                
                <?php if ($student['status'] === 'active'): ?>
                    <h4>إيقاف الحساب</h4>
                    <form method="POST">
                        <div class="form-group">
                            <label>مدة الإيقاف:</label>
                            <select name="suspension_duration" class="form-control">
                                <option value="+1 hour">ساعة واحدة</option>
                                <option value="+1 day">يوم واحد</option>
                                <option value="+1 week">أسبوع</option>
                                <option value="+1 month">شهر</option>
                                <option value="permanent">إيقاف دائم (حظر)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>سبب الإيقاف (سيظهر في الملاحظات):</label>
                            <textarea name="suspension_reason" rows="2" class="form-control" required></textarea>
                        </div>
                        <button type="submit" name="suspend_account" class="btn btn-danger btn-block">تأكيد الإيقاف</button>
                    </form>
                <?php else: // الحساب موقوف حاليًا ?>
                     <h4>إعادة تفعيل الحساب</h4>
                     <p>هذا الحساب موقوف حاليًا. <?php if($student['suspend_until']) echo "حتى تاريخ: " . $student['suspend_until']; ?></p>
                     <form method="POST">
                        <button type="submit" name="reactivate_account" class="btn btn-success btn-block">إعادة التفعيل الآن</button>
                     </form>
                <?php endif; ?>
             </div>
        </div>
    </div>
</div>