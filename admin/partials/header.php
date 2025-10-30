
<?php
require_once __DIR__ . '/../../includes/functions.php';

// التحقق من أن المستخدم هو أدمن ومسجل دخوله
if (!is_admin()) {
    redirect(SITE_URL . '/login.php');
}
require_once __DIR__ . '/../../includes/header.php';

$user = current_user();

// جلب عدد المستخدمين المتصلين
$online_users = get_online_users();
$online_students = array_filter($online_users, function($user) {
    return $user['user_type'] === 'student';
});
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'لوحة التحكم' ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin-main.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin-responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="admin-header">
        <div class="header-top">
            <div class="logo">
                <h1><?= SITE_NAME ?></h1>
                <small>لوحة تحكم الأدمن</small>
            </div>
            <div class="user-info">
                <div class="online-indicator">
                    <i class="fas fa-circle" style="color: #28a745; font-size: 0.8rem;"></i>
                    <span>متصل</span>
                </div>
                <span class="welcome-text">أهلاً بك، <strong><?= htmlspecialchars($user['name']) ?></strong></span>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>تسجيل الخروج</span>
                </a>
            </div>
        </div>
        
        <nav class="main-nav">
            <div class="nav-container">
                <a href="index.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>الرئيسية</span>
                </a>
                <a href="students.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'students.php' || basename($_SERVER['PHP_SELF']) == 'student_details.php') ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>إدارة الطلاب</span>
                </a>
                <a href="playlists.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'playlists.php') ? 'active' : '' ?>">
                    <i class="fas fa-list"></i>
                    <span>إدارة القوائم</span>
                </a>
                <a href="lectures.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'lectures.php') ? 'active' : '' ?>">
                    <i class="fas fa-play-circle"></i>
                    <span>إدارة المحاضرات</span>
                </a>
                <a href="codes.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'codes.php') ? 'active' : '' ?>">
                    <i class="fas fa-key"></i>
                    <span>إدارة الأكواد</span>
                </a>
                <a href="quizzes.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'quizzes.php' || basename($_SERVER['PHP_SELF']) == 'manage_quiz.php') ? 'active' : '' ?>">
                    <i class="fas fa-edit"></i>
                    <span>إدارة الاختبارات</span>
                </a>
                <a href="announcements.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'announcements.php') ? 'active' : '' ?>">
                    <i class="fas fa-bullhorn"></i>
                    <span>الإعلانات</span>
                </a>
                <a href="online_users.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'online_users.php') ? 'active' : '' ?>">
                    <i class="fas fa-wifi"></i>
                    <span>المتصلون</span>
                </a>
                <a href="admin_locations.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'admin_locations.php') ? 'active' : '' ?>">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>الأماكن</span>
                </a>
                <a href="switch_mode.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'switch_mode.php') ? 'active' : '' ?>">
                    <i class="fas fa-mobile-alt"></i>
                    <span>وضع الهاتف</span>
                </a>
                <a href="settings.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i>
                    <span>الإعدادات</span>
                </a>
            </div>
        </nav>
    </header>

    <div class="session-alert" id="session-alert" style="display: none;">
        <div class="alert-content">
            <h3>تنبيه الأمان</h3>
            <p>تم تسجيل الدخول إلى حسابك من جهاز آخر. سيتم تسجيل خروجك الآن.</p>
            <button onclick="window.location.href='../logout.php'">موافق</button>
        </div>
    </div>
    
    <main class="admin-container">
