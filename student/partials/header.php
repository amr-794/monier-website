
<?php
require_once __DIR__ . '/../../includes/functions.php';

// التحقق من أن المستخدم هو طالب ومسجل دخوله
if (!is_student()) {
    redirect(SITE_URL . '/login.php');
}

// هذا السطر مهم جدًا لتفعيل نظام الحماية من تعدد الجلسات
require_once __DIR__ . '/../../includes/header.php';

$user = current_user();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'لوحة التحكم' ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --primary-dark: #5a6fd8;
            --secondary-color: #764ba2;
            --white: #ffffff;
            --light-bg: #f8f9fa;
            --dark-text: #333333;
            --gray-text: #666666;
            --border-color: #e9ecef;
            --shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light-bg);
            color: var(--dark-text);
            line-height: 1.6;
        }

        /* الهيدر المحسن */
        .student-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: var(--white);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            min-height: 70px;
            gap: 15px;
        }

        /* الجزء الأيسر من الهيدر */
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
            min-width: 200px;
        }

        .home-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            color: var(--white);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .home-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .logo-link {
            text-decoration: none;
            color: var(--white);
        }

        .logo-link h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        /* التنقل */
        .student-nav {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 2;
            justify-content: center;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            color: var(--white);
            text-decoration: none;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 500;
            white-space: nowrap;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.25);
            font-weight: 600;
        }

        /* إجراءات المستخدم */
        .user-actions {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
            justify-content: flex-end;
        }

        /* الإشعارات */
        .notification-icon {
            position: relative;
            cursor: pointer;
            padding: 10px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .notification-icon:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .notification-count {
            position: absolute;
            top: -5px;
            left: -5px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* رسالة الترحيب */
        .welcome-message {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            padding: 8px 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            white-space: nowrap;
        }

        .welcome-message strong {
            font-weight: 600;
        }

        /* زر تسجيل الخروج */
        .logout-btn-student {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            color: var(--white);
            text-decoration: none;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 500;
            white-space: nowrap;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .logout-btn-student:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        /* لوحة الإشعارات */
        .notification-panel {
            position: absolute;
            top: 100%;
            left: 20px;
            right: 20px;
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            max-width: 400px;
            margin: 0 auto;
            display: none;
            z-index: 1001;
        }

        .notification-panel.active {
            display: block;
        }

        .panel-header {
            padding: 15px 20px;
            background: var(--light-bg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px 10px 0 0;
        }

        .panel-body {
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
        }

        /* المحتوى الرئيسي */
        .student-container {
            min-height: calc(100vh - 70px);
            padding: 0;
        }

        /* القائمة المتنقلة للشاشات الصغيرة */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 10px;
        }

        /* التجاوب مع الشاشات الصغيرة */
        @media (max-width: 1024px) {
            .header-content {
                padding: 0 15px;
                gap: 10px;
            }

            .nav-link {
                padding: 10px 15px;
                font-size: 0.9rem;
            }

            .welcome-message {
                font-size: 0.9rem;
                padding: 6px 12px;
            }

            .logout-btn-student {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                padding: 15px;
                gap: 15px;
                min-height: auto;
            }

            .header-left {
                width: 100%;
                justify-content: space-between;
            }

            .student-nav {
                width: 100%;
                order: 3;
                justify-content: center;
                gap: 5px;
            }

            .nav-link {
                flex: 1;
                justify-content: center;
                padding: 12px 10px;
                font-size: 0.85rem;
            }

            .user-actions {
                width: 100%;
                order: 2;
                justify-content: space-between;
                gap: 10px;
            }

            .welcome-message {
                flex: 1;
                justify-content: center;
                text-align: center;
            }

            .notification-icon {
                order: -1;
            }

            .logout-btn-student {
                flex-shrink: 0;
            }

            .logo-link h1 {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 480px) {
            .header-content {
                padding: 10px;
            }

            .nav-link {
                font-size: 0.8rem;
                padding: 10px 8px;
            }

            .nav-link span {
                display: none;
            }

            .nav-link i {
                font-size: 1.1rem;
                margin: 0;
            }

            .welcome-message {
                font-size: 0.8rem;
                padding: 8px 10px;
            }

            .welcome-message span {
                display: none;
            }

            .logout-btn-student span {
                display: none;
            }

            .logout-btn-student {
                padding: 10px;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                justify-content: center;
            }

            .logo-link h1 {
                font-size: 1.2rem;
            }
        }

        /* تحسينات إضافية للشاشات الصغيرة جداً */
        @media (max-width: 360px) {
            .header-left {
                min-width: auto;
            }

            .student-nav {
                gap: 2px;
            }

            .nav-link {
                padding: 8px 6px;
                font-size: 0.75rem;
            }

            .user-actions {
                gap: 5px;
            }

            .welcome-message {
                padding: 6px 8px;
                font-size: 0.75rem;
            }
        }

        /* تأثيرات سلسة */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* تحسين المظهر عند التمرير */
        .student-header.scrolled {
            background: var(--primary-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <header class="student-header fade-in">
        <div class="header-content">
            <div class="header-left">
                <a href="<?= SITE_URL ?>" class="home-btn" title="الصفحة الرئيسية">
                    <i class="fas fa-home"></i>
                </a>
                <a href="index.php" class="logo-link">
                    <h1><?= SITE_NAME ?></h1>
                </a>
            </div>

            <nav class="student-nav">
                <a href="index.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>">
                    <i class="fas fa-play-circle"></i>
                    <span>المحاضرات</span>
                </a>
                <a href="profile.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : '' ?>">
                    <i class="fas fa-user"></i>
                    <span>ملفي الشخصي</span>
                </a>
            </nav>

            <div class="user-actions">
                <div class="notification-icon" id="notification-bell">
                    <i class="fas fa-bell"></i>
                    <span class="notification-count" id="notification-count">0</span>
                </div>
                
                <div class="welcome-message">
                    <span>أهلاً بك،</span>
                    <strong><?= htmlspecialchars($user['name']) ?></strong>
                </div>
                
                <a href="../logout.php" class="logout-btn-student" title="تسجيل الخروج">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>تسجيل الخروج</span>
                </a>
            </div>
        </div>

        <div class="notification-panel" id="notification-panel">
            <div class="panel-header">
                <span>الإشعارات</span>
                <button id="close-notifications" style="background: none; border: none; color: var(--gray-text); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="panel-body" id="notification-list">
                <div class="empty-notifications" style="text-align: center; padding: 20px; color: var(--gray-text);">
                    <i class="fas fa-bell-slash" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                    <p>لا توجد إشعارات جديدة</p>
                </div>
            </div>
        </div>
    </header>

    <main class="student-container">
        <script>
            // إدارة الإشعارات
            document.addEventListener('DOMContentLoaded', function() {
                const notificationBell = document.getElementById('notification-bell');
                const notificationPanel = document.getElementById('notification-panel');
                const closeNotifications = document.getElementById('close-notifications');

                // فتح وإغلاق لوحة الإشعارات
                if (notificationBell && notificationPanel) {
                    notificationBell.addEventListener('click', function(e) {
                        e.stopPropagation();
                        notificationPanel.classList.toggle('active');
                    });

                    closeNotifications.addEventListener('click', function() {
                        notificationPanel.classList.remove('active');
                    });

                    // إغلاق لوحة الإشعارات عند النقر خارجها
                    document.addEventListener('click', function(e) {
                        if (!notificationPanel.contains(e.target) && !notificationBell.contains(e.target)) {
                            notificationPanel.classList.remove('active');
                        }
                    });
                }

                // تأثير التمرير
                window.addEventListener('scroll', function() {
                    const header = document.querySelector('.student-header');
                    if (window.scrollY > 50) {
                        header.classList.add('scrolled');
                    } else {
                        header.classList.remove('scrolled');
                    }
                });

                // تحميل الإشعارات
                function loadNotifications() {
                    fetch('../api/get_notifications.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.notifications.length > 0) {
                                const notificationList = document.getElementById('notification-list');
                                const notificationCount = document.getElementById('notification-count');
                                
                                notificationCount.textContent = data.notifications.length;
                                
                                let notificationsHTML = '';
                                data.notifications.forEach(notification => {
                                    notificationsHTML += `
                                        <div class="notification-item" style="padding: 10px; border-bottom: 1px solid var(--border-color);">
                                            <div style="font-weight: 600;">${notification.title}</div>
                                            <div style="font-size: 0.9rem; color: var(--gray-text);">${notification.message}</div>
                                            <div style="font-size: 0.8rem; color: var(--gray-text); margin-top: 5px;">${notification.time}</div>
                                        </div>
                                    `;
                                });
                                
                                notificationList.innerHTML = notificationsHTML;
                            }
                        })
                        .catch(error => {
                            console.error('Error loading notifications:', error);
                        });
                }

                // تحميل الإشعارات عند فتح اللوحة
                notificationBell.addEventListener('click', loadNotifications);
            });
        </script>
