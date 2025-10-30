
<?php
require_once 'includes/functions.php';

// إذا كان المستخدم مسجل دخوله بالفعل، يتم توجيهه للوحة التحكم
if (current_user()) {
    redirect(is_admin() ? 'admin/index.php' : 'student/index.php');
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'includes/auth.php';
    $result = handle_login($_POST);
    
    if (isset($result['error'])) {
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - <?= SITE_NAME ?></title>
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
            max-width: 450px;
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

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
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

        .form-group input {
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input:focus {
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

        .form-group input {
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

            .form-group input {
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

            .form-group input {
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

        /* معلومات إضافية */
        .login-info {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            border-right: 4px solid var(--primary-color);
        }

        .login-info h4 {
            margin: 0 0 10px;
            color: var(--dark-text);
            font-size: 0.95rem;
        }

        .login-info p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--gray-text);
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
                    <h1 class="auth-title">تسجيل الدخول</h1>
                </div>
                <p class="auth-subtitle">مرحباً بعودتك! أدخل بياناتك للمتابعة</p>
            </div>

            <div class="auth-content">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="auth-form" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    
                    <div class="form-group">
                        <label for="login_identifier">
                            <i class="fas fa-user"></i> رقم الهاتف / البريد الإلكتروني:
                        </label>
                        <input type="text" id="login_identifier" name="login_identifier" required autofocus placeholder="أدخل رقم الهاتف أو البريد الإلكتروني">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> كلمة المرور:
                        </label>
                        <input type="password" id="password" name="password" required placeholder="أدخل كلمة المرور">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                            <i class="fas fa-sign-in-alt"></i> دخول
                        </button>
                        <a href="<?= SITE_URL ?>" class="btn btn-secondary">
                            <i class="fas fa-home"></i> الصفحة الرئيسية
                        </a>
                    </div>
                </form>

                <div class="login-info">
                    <h4><i class="fas fa-info-circle"></i> معلومات الدخول:</h4>
                    <p>• استخدم رقم الهاتف أو البريد الإلكتروني المسجل</p>
                    <p></p>
                </div>

                <div class="auth-footer">
                    <p>ليس لديك حساب؟ <a href="register.php">أنشئ حساباً جديداً</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            
            if (form) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="loading"></span> جاري تسجيل الدخول...';
                });
            }

            // التركيز التلقائي على حقل الإدخال
            const identifierField = document.getElementById('login_identifier');
            if (identifierField) {
                identifierField.focus();
            }
        });
    </script>
</body>
</html>
