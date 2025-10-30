<?php
require_once 'includes/functions.php';

// التأكد من أن الطلب هو POST ومن نوع JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // إذا كان الطلب مباشراً (GET)، إرجاع خطأ JSON بدلاً من محتوى HTML
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => "Invalid request method"]);
        exit;
    } else {
        // إذا كان طلباً عادياً، توجيه إلى الصفحة الرئيسية
        header('Location: index.php');
        exit;
    }
}

// الاستمرار في المعالجة للطلبات POST فقط
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$student = current_user();

if (!$student || $student['type'] !== 'student') {
    echo json_encode(["success" => false, "message" => "غير مسجل الدخول"]);
    exit;
}

$lecture_id = intval($input['lectureId'] ?? 0);
$csrf = $input['csrf'] ?? '';

if (!verify_csrf_token($csrf)) {
    echo json_encode(["success" => false, "message" => "رمز CSRF غير صالح"]);
    exit;
}

require_once 'includes/db_connection.php';

// التحقق من أن المحاضرة ليست مجانية
$lecture_stmt = $pdo->prepare("SELECT is_free FROM lectures WHERE id = ?");
$lecture_stmt->execute([$lecture_id]);
$lecture = $lecture_stmt->fetch();

if ($lecture && $lecture['is_free']) {
    // المحاضرة مجانية - لا تحتاج إلى عد المشاهدات
    echo json_encode([
        "success" => true,
        "remaining" => "unlimited",
        "message" => "المحاضرة مجانية"
    ]);
    exit;
}

// جلب سجل المشاهدات الحالي
$stmt = $pdo->prepare("SELECT id, remaining_views FROM student_lecture_access WHERE student_id = ? AND lecture_id = ?");
$stmt->execute([$student['id'], $lecture_id]);
$access = $stmt->fetch();

if (!$access) {
    echo json_encode(["success" => false, "message" => "لا تملك صلاحية لهذه المحاضرة"]);
    exit;
}

if ($access['remaining_views'] <= 0) {
    echo json_encode(["success" => false, "message" => "تم استنفاذ جميع المشاهدات"]);
    exit;
}

// إنقاص عدد المشاهدات
$stmt = $pdo->prepare("UPDATE student_lecture_access SET remaining_views = remaining_views - 1, last_viewed = NOW() WHERE id = ?");
$stmt->execute([$access['id']]);

// جلب عدد المشاهدات المتبقية الجديد
$stmt = $pdo->prepare("SELECT remaining_views FROM student_lecture_access WHERE id = ?");
$stmt->execute([$access['id']]);
$new_access = $stmt->fetch();

$new_remaining = $new_access ? $new_access['remaining_views'] : 0;

echo json_encode([
    "success" => true,
    "remaining" => $new_remaining,
    "message" => "تم تسجيل المشاهدة بنجاح"
]);