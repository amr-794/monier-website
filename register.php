
<?php
require_once 'includes/functions.php';

// إذا كان المستخدم مسجل دخوله بالفعل، يتم توجيهه لللوحة التحكم
if (current_user()) {
    redirect(is_admin() ? 'admin/index.php' : 'student/index.php');
}

$errors = [];
$success_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'includes/auth.php';
    $result = handle_registration($_POST);

    if (isset($result['success'])) {
        $success_message = $result['success'];
    } else {
        $errors = $result['errors'];
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل طالب جديد - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --primary-dark: #5a6fd8;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --light-bg: #f8f9fa;
            --dark-text: #333333;
            --gray-text: #666666;
            --border-color: #e9ecef;
            --shadow: 0 10px 30px rgba(0,0,0,0.1);
            --shadow-sm: 0 2px 10px rgba(0,0,0,0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            line-height: 1.6;
        }

        .auth-container {
            width: 100%;
            max-width: 500px;
        }

        .auth-box {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
            position: relative;
        }

        .auth-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .auth-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .home-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .home-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .auth-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            position: relative;
        }

        .auth-subtitle {
            opacity: 0.9;
            font-size: 1rem;
            position: relative;
        }

        .auth-content {
            padding: 30px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-right: 4px solid transparent;
            position: relative;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .alert ul {
            margin: 10px 0 0 0;
            padding-right: 20px;
        }

        .alert li {
            margin-bottom: 5px;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 600;
            color: var(--dark-text);
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select {
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 15px 25px;
            border: none;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-block {
            width: 100%;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .auth-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            color: var(--gray-text);
        }

        .auth-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .auth-footer a:hover {
            color: var(--secondary-color);
        }

        /* تأثيرات إضافية */
        .auth-box {
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group input,
        .form-group select {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* التجاوب مع الشاشات الصغيرة */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .auth-container {
                max-width: 100%;
            }

            .auth-header {
                padding: 25px 20px;
            }

            .auth-content {
                padding: 25px 20px;
            }

            .auth-title {
                font-size: 1.5rem;
            }

            .auth-subtitle {
                font-size: 0.9rem;
            }

            .home-btn {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .form-group input,
            .form-group select {
                padding: 12px 14px;
                font-size: 0.95rem;
            }

            .btn {
                padding: 14px 20px;
                font-size: 0.95rem;
            }

            .form-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .auth-header {
                padding: 20px 15px;
            }

            .auth-content {
                padding: 20px 15px;
            }

            .auth-title {
                font-size: 1.3rem;
            }

            .home-btn {
                width: 38px;
                height: 38px;
                font-size: 0.9rem;
            }

            .form-group input,
            .form-group select {
                padding: 11px 13px;
            }
        }

        @media (max-width: 360px) {
            body {
                padding: 10px;
            }

            .auth-header {
                padding: 15px 12px;
            }

            .auth-content {
                padding: 15px 12px;
            }

            .auth-title {
                font-size: 1.2rem;
            }

            .auth-subtitle {
                font-size: 0.85rem;
            }
        }

        /* تحسين المظهر عند التركيز */
        .form-group input:valid {
            border-color: #28a745;
        }

        .form-group input:invalid:not(:focus):not(:placeholder-shown) {
            border-color: var(--danger-color);
        }

        /* تأثيرات تحميل */
        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <div class="header-top">
                    <a href="<?= SITE_URL ?>" class="home-btn" title="الصفحة الرئيسية">
                        <i class="fas fa-home"></i>
                    </a>
                    <h1 class="auth-title">إنشاء حساب جديد</h1>
                </div>
                <p class="auth-subtitle">املأ بياناتك وانضم إلى منصتنا التعليمية</p>
            </div>

            <div class="auth-content">
                <?php if ($success_message && empty($errors)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($success_message) ?>
                        <br><br>
                        <div class="form-actions">
                            <a href="login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> تسجيل الدخول الآن
                            </a>
                            <a href="<?= SITE_URL ?>" class="btn btn-secondary">
                                <i class="fas fa-home"></i> الصفحة الرئيسية
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>الرجاء إصلاح الأخطاء التالية:</strong>
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="register.php" method="POST" class="auth-form" id="registerForm">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        
                        <div class="form-group">
                            <label for="name">
                                <i class="fas fa-user"></i> الاسم بالكامل:
                            </label>
                            <input type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="أدخل اسمك بالكامل">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i> رقم الهاتف:
                            </label>
                            <input type="tel" id="phone" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="سيستخدم لتسجيل الدخول">
                        </div>

                        <div class="form-group">
                            <label for="parent_phone">
                                <i class="fas fa-user-friends"></i> رقم هاتف ولي الأمر:
                            </label>
                            <input type="tel" id="parent_phone" name="parent_phone" required value="<?= htmlspecialchars($_POST['parent_phone'] ?? '') ?>" placeholder="رقم هاتف ولي الأمر">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> البريد الإلكتروني:
                            </label>
                            <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="example@email.com">
                        </div>

                        <div class="form-group">
                            <label for="grade">
                                <i class="fas fa-graduation-cap"></i> الصف الدراسي:
                            </label>
                            <select id="grade" name="grade" required>
                                <option value="">-- اختر الصف الدراسي --</option>
                                <option value="first_secondary" <?= ($_POST['grade'] ?? '') == 'first_secondary' ? 'selected' : '' ?>>الأول الثانوي</option>
                                <option value="second_secondary" <?= ($_POST['grade'] ?? '') == 'second_secondary' ? 'selected' : '' ?>>الثاني الثانوي</option>
                                <option value="third_secondary" <?= ($_POST['grade'] ?? '') == 'third_secondary' ? 'selected' : '' ?>>الثالث الثانوي</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i> كلمة المرور:
                            </label>
                            <input type="password" id="password" name="password" required placeholder="أدخل كلمة المرور">
                        </div>

                        <div class="form-group">
                            <label for="password_confirm">
                                <i class="fas fa-lock"></i> تأكيد كلمة المرور:
                            </label>
                            <input type="password" id="password_confirm" name="password_confirm" required placeholder="أعد إدخال كلمة المرور">
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                                <i class="fas fa-user-plus"></i> إنشاء الحساب
                            </button>
                            <a href="<?= SITE_URL ?>" class="btn btn-secondary">
                                <i class="fas fa-home"></i> الصفحة الرئيسية
                            </a>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="auth-footer">
                    <p>لديك حساب بالفعل؟ <a href="login.php">سجل الدخول</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const submitBtn = document.getElementById('submitBtn');
            
            if (form) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="loading"></span> جاري إنشاء الحساب...';
                });
            }

            // التحقق من تطابق كلمات المرور
            const password = document.getElementById('password');
            const passwordConfirm = document.getElementById('password_confirm');
            
            function validatePassword() {
                if (password.value !== passwordConfirm.value) {
                    passwordConfirm.style.borderColor = 'var(--danger-color)';
                } else {
                    passwordConfirm.style.borderColor = 'var(--success-color)';
                }
            }
            
            if (password && passwordConfirm) {
                password.addEventListener('input', validatePassword);
                passwordConfirm.addEventListener('input', validatePassword);
            }
        });
    </script>
</body>
</html>