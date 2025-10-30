<?php
$page_title = 'إدارة الطلاب';
include 'partials/header.php';
require_once __DIR__ . '/../includes/db_connection.php';

// البحث والفلترة
$search_query = $_GET['search'] ?? '';
$grade_filter = $_GET['grade'] ?? '';
$status_filter = $_GET['status'] ?? '';

$sql = "SELECT * FROM students WHERE 1=1";
$params = [];

if (!empty($search_query)) {
    $sql .= " AND (name LIKE ? OR phone LIKE ? OR email LIKE ? OR unique_student_id LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}
if (!empty($grade_filter)) {
    $sql .= " AND grade = ?";
    $params[] = $grade_filter;
}
if (!empty($status_filter)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>إدارة الطلاب</h1>
    <p>عرض وتصفية الطلاب المسجلين في المنصة.</p>
</div>

<div class="card">
    <div class="card-header">
        <h3>تصفية وبحث</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="students.php" class="filter-form">
            <div class="form-group">
                <input type="text" name="search" placeholder="ابحث بالاسم, الهاتف, الايميل, أو ID..." value="<?= htmlspecialchars($search_query) ?>">
            </div>
            <div class="form-group">
                <select name="grade">
                    <option value="">كل الصفوف</option>
                    <option value="first_secondary" <?= $grade_filter == 'first_secondary' ? 'selected' : '' ?>>الأول الثانوي</option>
                    <option value="second_secondary" <?= $grade_filter == 'second_secondary' ? 'selected' : '' ?>>الثاني الثانوي</option>
                    <option value="third_secondary" <?= $grade_filter == 'third_secondary' ? 'selected' : '' ?>>الثالث الثانوي</option>
                </select>
            </div>
            <div class="form-group">
                <select name="status">
                    <option value="">كل الحالات</option>
                    <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>نشط</option>
                    <option value="suspended" <?= $status_filter == 'suspended' ? 'selected' : '' ?>>موقوف</option>
                    <option value="banned" <?= $status_filter == 'banned' ? 'selected' : '' ?>>محظور</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">تصفية</button>
                <a href="students.php" class="btn btn-secondary">إعادة تعيين</a>
            </div>
        </form>
    </div>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID الطالب</th>
                    <th>الاسم</th>
                    <th>رقم الهاتف</th>
                    <th>الصف الدراسي</th>
                    <th>الحالة</th>
                    <th>آخر ظهور</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="7" class="text-center">لا يوجد طلاب يطابقون معايير البحث.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($student['unique_student_id']) ?></code></td>
                        <td><?= htmlspecialchars($student['name']) ?></td>
                        <td><?= htmlspecialchars($student['phone']) ?></td>
                        <td><?= get_grade_text($student['grade']) ?></td>
                        <td>
                            <span class="status-badge status-<?= htmlspecialchars($student['status']) ?>">
                                <?= get_status_text($student['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($student['last_seen']): ?>
                                <span class="tooltip" data-tooltip="<?= date('Y-m-d H:i', strtotime($student['last_seen'])) ?>">
                                    <?= time_since($student['last_seen']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">لم يظهر</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a href="student_details.php?id=<?= $student['id'] ?>" class="btn btn-sm btn-primary">
                                عرض التفاصيل
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'partials/footer.php'; ?>