
<?php
$page_title = 'تفعيل المحاضرة';
require_once __DIR__ . '/../includes/functions.php';
if (!is_student()) {
    redirect('../login.php');
}
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db_connection.php';
$pdo = get_db_connection();

$student_id = current_user()['id'];
$student_grade = current_user()['grade'];
$message = '';
$lecture_id = intval($_GET['lecture_id'] ?? 0);
if (!$lecture_id) {
    die("المحاضرة غير محددة.");
}

$lecture_stmt = $pdo->prepare("SELECT * FROM lectures WHERE id = ? AND (grade = ? OR grade = 'all')");
$lecture_stmt->execute([$lecture_id, $student_grade]);
$lecture = $lecture_stmt->fetch();

if (!$lecture) {
    die("المحاضرة غير موجودة أو غير متاحة لصفك الدراسي.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!verify_csrf_token($_POST['csrf_token'])){
         $message = "<div class='alert alert-danger'>خطأ في التحقق.</div>";
    } else {
        $code_value = sanitize_input($_POST['code']);
        
        $code_stmt = $pdo->prepare("SELECT * FROM codes WHERE code_value = ? AND lecture_id = ? AND is_used = 0 AND is_active = 1");
        $code_stmt->execute([$code_value, $lecture_id]);
        $code = $code_stmt->fetch();
        
        if ($code) {
            // تفعيل الكود
            $update_stmt = $pdo->prepare("UPDATE codes SET is_used = 1, used_by = ?, used_at = NOW() WHERE id = ?");
            $update_stmt->execute([$student_id, $code['id']]);
            
            // منح الطالب صلاحية الوصول
            $access_stmt = $pdo->prepare("INSERT INTO student_lecture_access (student_id, lecture_id, remaining_views) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE remaining_views = VALUES(remaining_views)");
            $access_stmt->execute([$student_id, $lecture_id, $lecture['max_views']]);
            
            $message = "<div class='alert alert-success'>تم تفعيل الكود بنجاح! يمكنك الآن مشاهدة المحاضرة.</div>";
        } else {
            $message = "<div class='alert alert-danger'>الكود غير صحيح أو مستخدم من قبل.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="student-header">
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
                <a href="index.php" class="nav-link">
                    <i class="fas fa-play-circle"></i> المحاضرات
                </a>
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-user"></i> مالي الشخصي
                </a>
            </nav>
            <div class="user-actions">
                <div class="welcome-message">
                    <span>أهلاً بك،</span>
                    <strong><?= htmlspecialchars(current_user()['name']) ?></strong>
                </div>
                <a href="../logout.php" class="logout-btn-student">
                    <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                </a>
            </div>
        </div>
    </header>

    <main class="student-container">
        <div class="page-container">
            <div class="page-header">
                <h1><i class="fas fa-key"></i> تفعيل المحاضرة</h1>
                <p>أدخل كود التفعيل لمشاهدة المحاضرة</p>
                <a href="view_playlist.php?id=<?= $lecture['playlist_id'] ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-right"></i> العودة للقائمة
                </a>
            </div>

            <div class="activation-container">
                <div class="activation-card">
                    <div class="lecture-info">
                        <h3>المحاضرة: <?= htmlspecialchars($lecture['title']) ?></h3>
                        <div class="lecture-details">
                            <div class="detail-item">
                                <i class="fas fa-tag"></i>
                                <span><?= $lecture['is_free'] ? 'مجانية' : ((float)$lecture['price'] . ' جنيه') ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-eye"></i>
                                <span><?= $lecture['max_views'] ?> مشاهدة</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-user-graduate"></i>
                                <span><?= get_grade_text($lecture['grade']) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="activation-form">
                        <?= $message ?>
                        <form method="POST">
                            <?= csrf_token_field() ?>
                            <div class="form-group">
                                <label for="code" class="form-label">
                                    <i class="fas fa-key"></i> كود التفعيل
                                </label>
                                <input type="text" id="code" name="code" class="form-input" required placeholder="أدخل الكود هنا">
                                <small class="form-help">أدخل الكود الذي حصلت عليه لتفعيل هذه المحاضرة.</small>
                            </div>
                            <button type="submit" class="btn btn-primary btn-large">
                                <i class="fas fa-check-circle"></i> تفعيل الكود
                            </button>
                        </form>
                    </div>

                    <div class="activation-help">
                        <h4><i class="fas fa-question-circle"></i> كيف أحصل على كود التفعيل؟</h4>
                        <ul>
                            <li>يمكنك شراء الكود من خلال التواصل مع إدارة المنصة</li>
                            <li>تأكد من إدخال الكود بشكل صحيح</li>
                            <li>كل كود صالح لتفعيل المحاضرة مرة واحدة فقط</li>
                            <li>يمكنك مشاهدة المحاضرة بعدد مرات محدد حسب نوع الكود</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

<style>
.page-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    text-align: center;
}

.page-header h1 {
    margin: 0 0 10px;
    font-size: 1.8rem;
}

.page-header p {
    margin: 0 0 15px;
    opacity: 0.9;
    font-size: 1.1rem;
}

.activation-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.activation-card {
    padding: 30px;
}

.lecture-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    border-right: 4px solid #667eea;
}

.lecture-info h3 {
    margin: 0 0 15px;
    color: #333;
    font-size: 1.3rem;
}

.lecture-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: 0.95rem;
}

.detail-item i {
    color: #667eea;
    width: 16px;
}

.activation-form {
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-input:focus {
    outline: none;
    border-color: #667eea;
}

.form-help {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 0.85rem;
}

.btn-large {
    padding: 15px 30px;
    font-size: 1.1rem;
    width: 100%;
}

.activation-help {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.activation-help h4 {
    margin: 0 0 15px;
    color: #333;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.activation-help ul {
    margin: 0;
    padding-right: 20px;
    color: #666;
}

.activation-help li {
    margin-bottom: 8px;
    line-height: 1.4;
}

.alert {
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    border-right: 4px solid transparent;
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

@media (max-width: 768px) {
    .page-container {
        padding: 15px;
    }
    
    .activation-card {
        padding: 20px;
    }
    
    .lecture-details {
        grid-template-columns: 1fr;
    }
    
    .page-header h1 {
        font-size: 1.5rem;
    }
}
</style>
