<?php
$page_title = 'إدارة الإعلانات والإشعارات';
include 'partials/header.php';
require_once __DIR__ . '/../includes/db_connection.php';
$pdo = get_db_connection();

// معالجة إضافة أو تعديل
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_announcement'])) {
    $title = sanitize_input($_POST['title']);
    $content = sanitize_input($_POST['content'], true); // السماح بـ HTML
    $id = intval($_POST['id'] ?? 0);
    $is_notification = isset($_POST['is_notification']) ? 1 : 0;
    
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE announcements SET title=?, content=?, is_notification=? WHERE id=?");
        $stmt->execute([$title, $content, $is_notification, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, is_notification) VALUES (?, ?, ?)");
        $stmt->execute([$title, $content, $is_notification]);
    }
    // في حالة الإشعار، يجب إبلاغ الطلاب (سيتم شرح هذه الآلية لاحقاً)
}

if (isset($_GET['action'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($_GET['action'] == 'toggle') {
        $stmt = $pdo->prepare("UPDATE announcements SET is_visible = NOT is_visible WHERE id = ?");
        $stmt->execute([$id]);
    }
}

$edit_ann = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit_ann = $stmt->fetch();
}

$announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll();
?>

<div class="page-header">
    <h1>إدارة الإعلانات والإشعارات</h1>
    <p>قم بإنشاء وتعديل الإعلانات التي تظهر للطلاب أو إرسال إشعارات لهم.</p>
</div>

<div class="card">
    <div class="card-header">
         <h2><?= $edit_ann ? 'تعديل' : 'إضافة' ?> إعلان أو إشعار</h2>
    </div>
    <div class="card-body">
        <form action="announcements.php" method="POST">
            <input type="hidden" name="id" value="<?= $edit_ann['id'] ?? 0 ?>">
            <div class="form-group">
                <label>العنوان:</label>
                <input type="text" name="title" value="<?= htmlspecialchars($edit_ann['title'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>المحتوى:</label>
                <textarea name="content" class="wysiwyg" rows="8" required><?= htmlspecialchars($edit_ann['content'] ?? '') ?></textarea>
            </div>
             <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_notification" value="1" <?= ($edit_ann['is_notification'] ?? 0) ? 'checked' : '' ?>>
                    إرسال كـ "إشعار" للطلاب (سيظهر كتنبيه)
                </label>
             </div>
            <button type="submit" name="save_announcement" class="btn btn-primary">حفظ</button>
        </form>
    </div>
</div>

<h2>القائمة الحالية</h2>
<div class="table-responsive">
<table class="data-table">
    <thead><tr><th>العنوان</th><th>المحتوى</th><th>النوع</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
    <tbody>
        <?php foreach($announcements as $ann): ?>
        <tr>
            <td><?= htmlspecialchars($ann['title']) ?></td>
            <td><?= substr(strip_tags($ann['content']), 0, 100) ?>...</td>
            <td><?= $ann['is_notification'] ? '<span class="badge badge-info">إشعار</span>' : '<span class="badge badge-secondary">إعلان</span>' ?></td>
            <td><?= $ann['is_visible'] ? 'ظاهر' : 'مخفي' ?></td>
            <td class="actions">
                <a href="?edit=<?= $ann['id'] ?>" class="btn btn-sm btn-primary">تعديل</a>
                <a href="?action=toggle&id=<?= $ann['id'] ?>" class="btn btn-sm btn-warning">إخفاء/إظهار</a>
                <a href="?action=delete&id=<?= $ann['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('متأكد؟')">حذف</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php include 'partials/footer.php'; ?>