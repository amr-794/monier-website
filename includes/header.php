<?php
// هذا الملف يتم تضمينه في جميع الصفحات للتحقق من صحة الجلسة
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    exit('Access Denied');
}
require_once 'functions.php';

$user = current_user();
if ($user) {
    // التحقق مما إذا كانت الجلسة الحالية لا تزال هي الجلسة النشطة في قاعدة البيانات.
    // إذا لم تكن كذلك، فهذا يعني أنه تم تسجيل الدخول من جهاز آخر.
    if (!validate_session()) {
        // تدمير الجلسة الحالية وتوجيه المستخدم لصفحة الدخول
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
        
        // استخدام جافاسكربت لإظهار التنبيه وإعادة التوجيه لضمان عمله
        echo "<script>
            alert('تم تسجيل الدخول إلى حسابك من جهاز آخر. سيتم تسجيل خروجك الآن.');
            window.location.href = '" . SITE_URL . "/login.php';
        </script>";
        exit;
    }
    
    // تحديث آخر نشاط للمستخدم
    update_user_activity();
}