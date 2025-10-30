<?php
$page_title = 'المستخدمون المتصلون';
include 'partials/header.php';
require_once __DIR__ . '/../includes/db_connection.php';
$pdo = get_db_connection();

// معالجة طرد مستخدم
if (isset($_GET['kick_session'])) {
    $session_id = sanitize_input($_GET['kick_session']);
    // يتم تعطيل الجلسة في قاعدة البيانات
    $stmt = $pdo->prepare("UPDATE user_sessions SET is_active = 0 WHERE session_id = ?");
    $stmt->execute([$session_id]);
    
    // هذه الخطوة اختيارية لكنها تساعد في تسريع عملية تسجيل الخروج الفعلية
    // عبر تدمير ملف الجلسة على الخادم إذا كان ممكناً.
    $session_file = session_save_path() . '/sess_' . $session_id;
    if (file_exists($session_file)) {
        unlink($session_file);
    }

    echo "<div class='alert alert-success'>تم طرد المستخدم. قد يستغرق الأمر بضع دقائق حتى يتم تسجيل خروجه بالكامل.</div>";
}

// جلب المستخدمين المتصلين حاليًا (آخر 5 دقائق)
$online_users = get_online_users();
$online_students_count = count(array_filter($online_users, fn($u) => $u['user_type'] === 'student'));
$online_admins_count = count(array_filter($online_users, fn($u) => $u['user_type'] === 'admin'));
?>

<div class="page-header">
    <h1>المستخدمون المتصلون حالياً</h1>
    <p>عرض جميع المستخدمين النشطين خلال آخر 5 دقائق.</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon students-icon">
             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="stat-info">
            <span class="stat-number"><?= $online_students_count ?></span>
            <span class="stat-label">طلاب متصلون</span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon admins-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        </div>
        <div class="stat-info">
            <span class="stat-number"><?= $online_admins_count ?></span>
            <span class="stat-label">أدمن متصل</span>
        </div>
    </div>

     <div class="stat-card">
        <div class="stat-icon total-icon">
           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"></path><path d="M17 9l-5 5-5-5"></path></svg>
        </div>
        <div class="stat-info">
            <span class="stat-number"><?= count($online_users) ?></span>
            <span class="stat-label">إجمالي المتصلين</span>
        </div>
    </div>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>المستخدم</th>
                    <th>النوع</th>
                    <th>وقت الدخول</th>
                    <th>آخر نشاط</th>
                    <th>IP العنوان</th>
                    <th>الجهاز</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($online_users)): ?>
                    <tr>
                        <td colspan="7" class="text-center">لا يوجد مستخدمين متصلين حالياً.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($online_users as $user): ?>
                        <tr>
                            <td>
                                <?php if($user['user_type'] === 'student'): ?>
                                <a href="student_details.php?id=<?= $user['user_id'] ?>"><?= htmlspecialchars($user['user_name']) ?></a>
                                <?php else: ?>
                                <?= htmlspecialchars($user['user_name']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge user-type-<?= $user['user_type'] ?>">
                                    <?= $user['user_type'] === 'student' ? 'طالب' : 'أدمن' ?>
                                </span>
                            </td>
                            <td><?= date('Y-m-d H:i:s', strtotime($user['login_time'])) ?></td>
                            <td><?= time_since($user['last_activity']) ?></td>
                            <td><code><?= htmlspecialchars($user['ip_address']) ?></code></td>
                            <td>
                                <span class="tooltip" data-tooltip="<?= htmlspecialchars($user['user_agent']) ?>">
                                    <?= get_device_from_ua($user['user_agent']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (current_user()['id'] != $user['user_id'] || $user['user_type'] !== 'admin'): // لا تسمح للأدمن بطرد نفسه ?>
                                <button onclick="kickUser('<?= $user['session_id'] ?>')" class="btn btn-sm btn-danger">
                                    طرد
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function kickUser(sessionId) {
    if (confirm('هل أنت متأكد من طرد هذا المستخدم؟ سيتم تسجيل خروجه فوراً.')) {
        window.location.href = 'online_users.php?kick_session=' + sessionId;
    }
}

// تحديث الصفحة كل 60 ثانية لعرض المستخدمين النشطين
setTimeout(() => {
    window.location.reload();
}, 60000);
</script>

<?php include 'partials/footer.php'; ?>