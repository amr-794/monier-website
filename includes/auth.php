<?php
// هذا الملف يتم تضمينه ولا يتم الوصول إليه مباشرة
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access Denied');
}

require_once 'functions.php';

/**
 * يعالج طلب تسجيل طالب جديد
 * (هذه الدالة صحيحة ولا تحتاج لتغيير)
 */
function handle_registration($post_data) {
    $pdo = get_db_connection();
    $errors = [];

    // 1. التحقق من CSRF Token
    if (!isset($post_data['csrf_token']) || !verify_csrf_token($post_data['csrf_token'])) {
        $errors[] = 'خطأ في التحقق من صحة الطلب. يرجى إعادة تحميل الصفحة والمحاولة مرة أخرى.';
        return ['errors' => $errors];
    }
    
    // 2. استلام البيانات وتنقيتها
    $name = sanitize_input($post_data['name'] ?? '');
    $phone = sanitize_input($post_data['phone'] ?? '');
    $parent_phone = sanitize_input($post_data['parent_phone'] ?? '');
    $email = filter_var($post_data['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $grade = sanitize_input($post_data['grade'] ?? 'first_secondary');
    $password = $post_data['password'] ?? '';
    $password_confirm = $post_data['password_confirm'] ?? '';

    // 3. التحقق من صحة البيانات (Validation)
    if (empty($name) || empty($phone) || empty($parent_phone) || empty($email) || empty($grade)) {
        $errors[] = 'جميع الحقول مطلوبة.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'صيغة البريد الإلكتروني غير صحيحة.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'يجب أن تكون كلمة المرور 6 أحرف على الأقل.';
    }
    if ($password !== $password_confirm) {
        $errors[] = 'كلمتا المرور غير متطابقتين.';
    }
    
    // 4. التحقق من عدم تكرار البيانات
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT 1 FROM students WHERE email = :email OR phone = :phone");
        $stmt->execute([':email' => $email, ':phone' => $phone]);
        if ($stmt->fetch()) {
            $errors[] = 'البريد الإلكتروني أو رقم الهاتف مسجل بالفعل في حساب آخر.';
        }
    }

    // 5. إذا لم تكن هناك أخطاء، قم بإدخال البيانات
    if (empty($errors)) {
        try {
            $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            do {
                $unique_student_id = mt_rand(1000000000, 9999999999);
                $stmt_check = $pdo->prepare("SELECT 1 FROM students WHERE unique_student_id = ?");
                $stmt_check->execute([$unique_student_id]);
            } while ($stmt_check->fetch());

            $stmt = $pdo->prepare(
                "INSERT INTO students (name, phone, parent_phone, email, grade, password_hash, unique_student_id, created_at) 
                 VALUES (:name, :phone, :parent_phone, :email, :grade, :password_hash, :unique_student_id, NOW())"
            );
            $stmt->execute([
                ':name' => $name,
                ':phone' => $phone,
                ':parent_phone' => $parent_phone,
                ':email' => $email,
                ':grade' => $grade,
                ':password_hash' => $password_hash,
                ':unique_student_id' => $unique_student_id,
            ]);
            
            return ['success' => 'تم إنشاء حسابك بنجاح!'];

        } catch (PDOException $e) {
            error_log("Registration Error: " . $e->getMessage());
            $errors[] = 'حدث خطأ غير متوقع أثناء إنشاء الحساب. يرجى المحاولة لاحقاً.';
        }
    }

    return ['errors' => $errors];
}

/**
 * يعالج طلب تسجيل الدخول (للطلاب والأدمن)
 * (تم تصحيح هذه الدالة)
 */
function handle_login($post_data) {
    $pdo = get_db_connection();

    if (!isset($post_data['csrf_token']) || !verify_csrf_token($post_data['csrf_token'])) {
        return ['error' => 'خطأ في التحقق من صحة الطلب. يرجى تحديث الصفحة.'];
    }

    $identifier = sanitize_input($post_data['login_identifier'] ?? '');
    $password = $post_data['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        return ['error' => 'الرجاء ملء جميع الحقول.'];
    }

    // أولاً: محاولة تسجيل الدخول كـ أدمن
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([$identifier]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_name'] = $admin['username'];
        $_SESSION['user_type'] = 'admin';
        $_SESSION['session_id'] = register_user_session($admin['id'], 'admin');
        
        $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);
        redirect('admin/index.php');
    }

    // ثانياً: محاولة تسجيل الدخول كـ طالب (باستخدام رقم الهاتف أو الإيميل)
    // --- *** START OF FIX *** ---
    $stmt = $pdo->prepare("SELECT * FROM students WHERE (phone = ? OR email = ?) LIMIT 1");
    $stmt->execute([$identifier, $identifier]); // تمرير المتغير مرتين ليتطابق مع عدد علامات الاستفهام
    // --- *** END OF FIX *** ---
    $student = $stmt->fetch();

    if ($student) {
        if ($student['status'] !== 'active') {
             return ['error' => 'حسابك ' . get_status_text($student['status']) . '. يرجى التواصل مع المسؤول.'];
        }
        
        if (password_verify($password, $student['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $student['id'];
            $_SESSION['user_name'] = $student['name'];
            $_SESSION['user_type'] = 'student';
            $_SESSION['user_grade'] = $student['grade'];
            $_SESSION['session_id'] = register_user_session($student['id'], 'student');
            
            $pdo->prepare("UPDATE students SET last_login = NOW(), last_seen = NOW(), login_count = login_count + 1 WHERE id = ?")->execute([$student['id']]);
            redirect('student/index.php');
        }
    }
    
    return ['error' => 'بيانات الدخول غير صحيحة.'];
}


/**
 * يقوم بتسجيل خروج المستخدم وإنهاء جلسته
 * (هذه الدالة صحيحة ولا تحتاج لتغيير)
 */
function handle_logout() {
    $user = current_user();
    
    if ($user && isset($user['session_id'])) {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("UPDATE user_sessions SET is_active = 0 WHERE session_id = ?");
        $stmt->execute([$user['session_id']]);
    }
    
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
    redirect('login.php');
}