
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
            try {
                $pdo->beginTransaction();
                
                // تصحيح اسم العمود - يجب أن يكون used_by_student_id بدلاً من used_by
                $update_stmt = $pdo->prepare("UPDATE codes SET is_used = 1, used_by_student_id = ?, used_at = NOW() WHERE id = ?");
                $update_stmt->execute([$student_id, $code['id']]);
                
                // منح الطالب صلاحية الوصول
                $access_stmt = $pdo->prepare("INSERT INTO student_lecture_access (student_id, lecture_id, remaining_views) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE remaining_views = remaining_views + VALUES(remaining_views)");
                $access_stmt->execute([$student_id, $lecture_id, $lecture['max_views']]);
                
                $pdo->commit();
                
                $message = "<div class='alert alert-success'>تم تفعيل الكود بنجاح! سيتم توجيهك للمشاهدة الآن.</div>";
                echo "<meta http-equiv='refresh' content='2;url=../view_lecture.php?id=$lecture_id'>";
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "<div class='alert alert-danger'>حدث خطأ. حاول مرة أخرى.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>الكود غير صحيح أو تم استخدامه أو غير نشط.</div>";
        }
    }
}
?>

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
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <div class="form-group">
                        <label for="code" class="form-label">
                            <i class="fas fa-key"></i> كود التفعيل
                        </label>
                        <input type="text" id="code" name="code" class="form-input" required placeholder="أدخل الكود هنا (مثال: XXXX-XXXX-XXXX)" autocomplete="off">
                        <small class="form-help">أدخل الكود الذي حصلت عليه لتفعيل هذه المحاضرة.</small>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-large">
                            <i class="fas fa-check-circle"></i> تفعيل الكود
                        </button>
                        <a href="view_playlist.php?id=<?= $lecture['playlist_id'] ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> العودة للقائمة
                        </a>
                    </div>
                </form>
            </div>

            <div class="activation-help">
                <h4><i class="fas fa-question-circle"></i> كيف أحصل على كود التفعيل؟</h4>
                <ul>
                    <li>يمكنك شراء الكود من خلال التواصل مع إدارة المنصة</li>
                    <li>تأكد من إدخال الكود بشكل صحيح</li>
                    <li>كل كود صالح لتفعيل المحاضرة مرة واحدة فقط</li>
                    <li>يمكنك مشاهدة المحاضرة بعدد مرات محدد حسب نوع الكود</li>
                    <li>في حالة وجود أي مشكلة، يرجى التواصل مع الدعم الفني</li>
                </ul>
            </div>
        </div>
    </div>
</div>

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
    --shadow: 0 4px 15px rgba(0,0,0,0.1);
    --shadow-sm: 0 2px 10px rgba(0,0,0,0.08);
}

.page-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: white;
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: var(--shadow);
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
    border-radius: 15px;
    box-shadow: var(--shadow);
    overflow: hidden;
}

.activation-card {
    padding: 30px;
}

.lecture-info {
    background: var(--light-bg);
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    border-right: 4px solid var(--primary-color);
    text-align: center;
}

.lecture-info h3 {
    margin: 0 0 20px;
    color: var(--dark-text);
    font-size: 1.4rem;
    line-height: 1.4;
}

.lecture-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
    justify-items: center;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--gray-text);
    font-size: 1rem;
    font-weight: 500;
}

.detail-item i {
    color: var(--primary-color);
    width: 18px;
    font-size: 1.1rem;
}

.activation-form {
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 25px;
}

.form-label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: var(--dark-text);
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-input {
    width: 100%;
    padding: 15px 20px;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    text-align: center;
    letter-spacing: 2px;
    font-weight: 600;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.form-help {
    display: block;
    margin-top: 8px;
    color: var(--gray-text);
    font-size: 0.9rem;
    text-align: center;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 25px;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 15px 25px;
    border: none;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    flex: 1;
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

.btn-large {
    padding: 16px 30px;
    font-size: 1.1rem;
}

.activation-help {
    background: var(--light-bg);
    padding: 25px;
    border-radius: 12px;
    border: 1px solid var(--border-color);
}

.activation-help h4 {
    margin: 0 0 20px;
    color: var(--dark-text);
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 10px;
    justify-content: center;
}

.activation-help ul {
    margin: 0;
    padding-right: 20px;
    color: var(--gray-text);
}

.activation-help li {
    margin-bottom: 12px;
    line-height: 1.5;
    padding-right: 10px;
    position: relative;
}

.activation-help li:before {
    content: "•";
    color: var(--primary-color);
    font-weight: bold;
    position: absolute;
    right: -15px;
}

.alert {
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
    border-right: 4px solid transparent;
    text-align: center;
    font-weight: 500;
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

.alert i {
    margin-left: 8px;
}

/* التجاوب مع الشاشات الصغيرة */
@media (max-width: 768px) {
    .page-container {
        padding: 15px;
    }
    
    .activation-card {
        padding: 20px;
    }
    
    .lecture-details {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .page-header h1 {
        font-size: 1.5rem;
    }
    
    .page-header p {
        font-size: 1rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
    
    .lecture-info {
        padding: 20px;
    }
    
    .lecture-info h3 {
        font-size: 1.2rem;
    }
    
    .activation-help {
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .page-container {
        padding: 10px;
    }
    
    .activation-card {
        padding: 15px;
    }
    
    .page-header {
        padding: 20px;
    }
    
    .page-header h1 {
        font-size: 1.3rem;
    }
    
    .form-input {
        padding: 12px 15px;
        font-size: 1rem;
    }
    
    .detail-item {
        font-size: 0.9rem;
    }
    
    .activation-help h4 {
        font-size: 1.1rem;
    }
    
    .activation-help ul {
        padding-right: 15px;
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

/* تأثيرات ظهور */
.activation-card {
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const submitBtn = form?.querySelector('button[type="submit"]');
    
    if (form && submitBtn) {
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading"></span> جاري التفعيل...';
        });
    }
    
    // تحسين تجربة إدخال الكود
    const codeInput = document.getElementById('code');
    if (codeInput) {
        codeInput.addEventListener('input', function(e) {
            // تحويل إلى أحرف كبيرة تلقائياً
            this.value = this.value.toUpperCase();
            
           
        });
        
        // التركيز على حقل الكود تلقائياً
        codeInput.focus();
    }
});
</script>

<?php include 'partials/footer.php'; ?>
