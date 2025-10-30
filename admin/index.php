<?php
$page_title = 'الرئيسية';
include 'partials/header.php';
require_once __DIR__ . '/../includes/db_connection.php';

// الإحصائيات الأساسية
$students_count = $pdo->query("SELECT count(*) FROM students")->fetchColumn();
$lectures_count = $pdo->query("SELECT count(*) FROM lectures")->fetchColumn();
$active_codes_count = $pdo->query("SELECT count(*) FROM codes WHERE is_used = 0 AND is_active = 1")->fetchColumn();
$playlists_count = $pdo->query("SELECT count(*) FROM playlists")->fetchColumn();

// آخر 5 طلاب مسجلين
$latest_students = $pdo->query("SELECT id, name, phone, created_at FROM students ORDER BY created_at DESC LIMIT 5")->fetchAll();

// آخر 5 أكواد تم استخدامها
$latest_used_codes = $pdo->query("
    SELECT c.code_value, s.name as student_name, s.id as student_id, l.title as lecture_title, c.used_at 
    FROM codes c 
    JOIN students s ON c.used_by_student_id = s.id 
    JOIN lectures l ON c.lecture_id = l.id 
    WHERE c.is_used = 1 
    ORDER BY c.used_at DESC LIMIT 5
")->fetchAll();

?>
<div class="page-header">
    <h1>لوحة التحكم الرئيسية</h1>
    <p>نظرة عامة سريعة على المنصة.</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon students-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="stat-info">
            <span class="stat-number"><?= $students_count ?></span>
            <span class="stat-label">إجمالي الطلاب</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon lectures-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
        </div>
        <div class="stat-info">
            <span class="stat-number"><?= $lectures_count ?></span>
            <span class="stat-label">إجمالي المحاضرات</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon playlists-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
        </div>
        <div class="stat-info">
            <span class="stat-number"><?= $playlists_count ?></span>
            <span class="stat-label">قوائم التشغيل</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon codes-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
        </div>
        <div class="stat-info">
            <span class="stat-number"><?= $active_codes_count ?></span>
            <span class="stat-label">الأكواد الصالحة</span>
        </div>
    </div>
</div>

<div class="grid-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
    <div class="card">
        <div class="card-header">
            <h3>آخر الطلاب المسجلين</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table minimal">
                    <thead><tr><th>الاسم</th><th>الهاتف</th><th>تاريخ التسجيل</th><th>إجراء</th></tr></thead>
                    <tbody>
                        <?php foreach($latest_students as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['name']) ?></td>
                            <td><?= htmlspecialchars($student['phone']) ?></td>
                            <td><?= date('Y-m-d', strtotime($student['created_at'])) ?></td>
                            <td><a href="student_details.php?id=<?= $student['id'] ?>" class="btn btn-sm btn-primary">عرض التفاصيل</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>آخر الأكواد المستخدمة</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table minimal">
                    <thead><tr><th>الكود</th><th>الطالب</th><th>المحاضرة</th><th>التاريخ</th></tr></thead>
                    <tbody>
                        <?php foreach($latest_used_codes as $code): ?>
                        <tr>
                            <td><code class="code-value"><?= htmlspecialchars($code['code_value']) ?></code></td>
                            <td><a href="student_details.php?id=<?= $code['student_id'] ?>"><?= htmlspecialchars($code['student_name']) ?></a></td>
                            <td><?= htmlspecialchars($code['lecture_title']) ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($code['used_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>