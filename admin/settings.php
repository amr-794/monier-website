<?php
$page_title = 'الإعدادات';
include 'partials/header.php';
require_once __DIR__ . '/../includes/db_connection.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $admin_id = current_user()['id'];
    $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($current_password, $admin['password_hash'])) {
        if (strlen($new_password) >= 6 && $new_password === $confirm_password) {
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $update_stmt = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
            $update_stmt->execute([$new_password_hash, $admin_id]);
            $message = "<p style='color:green;'>تم تغيير كلمة المرور بنجاح.</p>";
        } else {
            $message = "<p style='color:red;'>كلمة المرور الجديدة غير متطابقة أو قصيرة جدًا.</p>";
        }
    } else {
        $message = "<p style='color:red;'>كلمة المرور الحالية غير صحيحة.</p>";
    }
}
?>

<div class="form-container">
    <h2>تغيير كلمة مرور الأدمن</h2>
    <?= $message ?>
    <form action="settings.php" method="POST">
        <div class="form-group">
            <label for="current_password">كلمة المرور الحالية:</label>
            <input type="password" name="current_password" required>
        </div>
        <div class="form-group">
            <label for="new_password">كلمة المرور الجديدة:</label>
            <input type="password" name="new_password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">تأكيد كلمة المرور الجديدة:</label>
            <input type="password" name="confirm_password" required>
        </div>
        <button type="submit" name="change_password">تغيير كلمة المرور</button>
    </form>
</div>

<?php include 'partials/footer.php'; ?>