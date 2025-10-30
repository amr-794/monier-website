<?php
$page_title = 'إدارة قوائم التشغيل';
include 'partials/header.php';
require_once __DIR__ . '/../includes/db_connection.php';
$pdo = get_db_connection();

// معالجة إضافة أو تعديل قائمة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_playlist'])) {
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    $grade = sanitize_input($_POST['grade']);
    $playlist_id = isset($_POST['playlist_id']) ? intval($_POST['playlist_id']) : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // معالجة رفع صورة الغلاف
    $cover_image = $_POST['current_cover_image'] ?? null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $target_dir = __DIR__ . "/../uploads/playlist_covers/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $cover_image_name = uniqid() . '-' . basename($_FILES["cover_image"]["name"]);
        $cover_image = "uploads/playlist_covers/" . $cover_image_name;
        move_uploaded_file($_FILES["cover_image"]["tmp_name"], __DIR__ . "/../" . $cover_image);
    }

    if (!empty($name)) {
        if ($playlist_id > 0) { // تحديث
            if ($cover_image && !empty($_FILES['cover_image']['name'])) {
                $stmt = $pdo->prepare("UPDATE playlists SET name = ?, description = ?, grade = ?, cover_image = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$name, $description, $grade, $cover_image, $is_active, $playlist_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE playlists SET name = ?, description = ?, grade = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$name, $description, $grade, $is_active, $playlist_id]);
            }
        } else { // إضافة
            $stmt = $pdo->prepare("INSERT INTO playlists (name, description, grade, cover_image, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $grade, $cover_image, $is_active]);
        }
        echo "<div class='alert alert-success'>تم حفظ القائمة بنجاح!</div>";
    } else {
        echo "<div class='alert alert-danger'>اسم القائمة مطلوب.</div>";
    }
}

// معالجة الحذف
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // You should also delete the associated image file here
    $stmt = $pdo->prepare("DELETE FROM playlists WHERE id = ?");
    $stmt->execute([$id]);
}

// جلب بيانات القائمة للتعديل
$edit_playlist = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM playlists WHERE id = ?");
    $stmt->execute([$id]);
    $edit_playlist = $stmt->fetch();
}

// جلب كل القوائم
// --- *** FIX STARTS HERE *** ---
// The `created_at` column was removed from the `playlists` table. Sorting by `sort_order` and `name` is sufficient.
$playlists = $pdo->query("SELECT * FROM playlists ORDER BY sort_order, name ASC")->fetchAll();
// --- *** FIX ENDS HERE *** ---
?>

<div class="card">
    <div class="card-header">
        <h2><?= $edit_playlist ? 'تعديل القائمة' : 'إضافة قائمة تشغيل جديدة' ?></h2>
    </div>
    <div class="card-body">
    <form action="playlists.php" method="post" enctype="multipart/form-data">
        <?php if ($edit_playlist): ?>
            <input type="hidden" name="playlist_id" value="<?= $edit_playlist['id'] ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="name">اسم القائمة:</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($edit_playlist['name'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">وصف القائمة:</label>
            <textarea id="description" name="description" rows="3"><?= htmlspecialchars($edit_playlist['description'] ?? '') ?></textarea>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label for="grade">الصف الدراسي:</label>
                <select id="grade" name="grade" required>
                    <option value="all" <?= (($edit_playlist['grade'] ?? '') == 'all') ? 'selected' : '' ?>>جميع الصفوف</option>
                    <option value="first_secondary" <?= (($edit_playlist['grade'] ?? '') == 'first_secondary') ? 'selected' : '' ?>>الأول الثانوي</option>
                    <option value="second_secondary" <?= (($edit_playlist['grade'] ?? '') == 'second_secondary') ? 'selected' : '' ?>>الثاني الثانوي</option>
                    <option value="third_secondary" <?= (($edit_playlist['grade'] ?? '') == 'third_secondary') ? 'selected' : '' ?>>الثالث الثانوي</option>
                </select>
            </div>
             <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?= (($edit_playlist['is_active'] ?? 1) == 1) ? 'checked' : '' ?>>
                    قائمة نشطة
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="cover_image">صورة الغلاف:</label>
            <input type="file" id="cover_image" name="cover_image" accept="image/*">
            <?php if (!empty($edit_playlist['cover_image'])): ?>
                <div style="margin-top:10px;">
                    <img src="../<?= htmlspecialchars($edit_playlist['cover_image']) ?>" width="150">
                    <input type="hidden" name="current_cover_image" value="<?= htmlspecialchars($edit_playlist['cover_image']) ?>">
                </div>
            <?php endif; ?>
        </div>
                
        <div class="form-actions">
            <button type="submit" name="save_playlist" class="btn btn-primary">حفظ القائمة</button>
            <?php if ($edit_playlist): ?>
                <a href="playlists.php" class="btn btn-secondary">إلغاء</a>
            <?php endif; ?>
        </div>
    </form>
    </div>
</div>


<div class="table-container">
    <h2>قوائم التشغيل الحالية</h2>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>الصورة</th>
                    <th>الاسم</th>
                    <th>الصف</th>
                    <th>الحالة</th>
                    <th>عدد المحاضرات</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($playlists as $playlist): ?>
                    <?php
                        $lecture_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM lectures WHERE playlist_id = ?");
                        $lecture_count_stmt->execute([$playlist['id']]);
                        $lecture_count = $lecture_count_stmt->fetchColumn();
                    ?>
                    <tr>
                        <td>
                            <?php if (!empty($playlist['cover_image'])): ?>
                                <img src="../<?= htmlspecialchars($playlist['cover_image']) ?>" width="60" style="border-radius: 4px;">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($playlist['name']) ?></td>
                        <td><?= get_grade_text($playlist['grade']) ?></td>
                        <td>
                            <span class="status-badge status-<?= $playlist['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $playlist['is_active'] ? 'نشطة' : 'غير نشطة' ?>
                            </span>
                        </td>
                        <td><?= $lecture_count ?></td>
                        <td class="actions">
                            <a href="playlists.php?edit=<?= $playlist['id'] ?>" class="btn btn-sm btn-primary">تعديل</a>
                            <a href="playlists.php?delete=<?= $playlist['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذه القائمة وكل محاضراتها؟')">حذف</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'partials/footer.php'; ?>