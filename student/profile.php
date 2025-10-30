
<?php
$page_title = 'الملف الشخصي';
include 'partials/header.php';
require_once __DIR__ . '/../includes/db_connection.php';
$pdo = get_db_connection();

$student_id = $user['id'];
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-user"></i> مالي الشخصي</h1>
        <p>هذه هي بياناتك المسجلة في المنصة. لا يمكنك تعديلها مباشرة.</p>
    </div>

    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="profile-title">
                    <h2><?= htmlspecialchars($student['name']) ?></h2>
                    <p>طالب في <?= get_grade_text($student['grade']) ?></p>
                </div>
            </div>
            
            <div class="profile-body">
                <div class="profile-section">
                    <h3><i class="fas fa-id-card"></i> المعلومات الأساسية</h3>
                    <div class="profile-grid">
                        <div class="profile-field">
                            <label>ID الخاص بك:</label>
                            <span class="field-value"><?= htmlspecialchars($student['unique_student_id']) ?></span>
                        </div>
                        <div class="profile-field">
                            <label>الاسم بالكامل:</label>
                            <span class="field-value"><?= htmlspecialchars($student['name']) ?></span>
                        </div>
                        <div class="profile-field">
                            <label>رقم الهاتف:</label>
                            <span class="field-value"><?= htmlspecialchars($student['phone']) ?></span>
                        </div>
                        <div class="profile-field">
                            <label>رقم هاتف ولي الأمر:</label>
                            <span class="field-value"><?= htmlspecialchars($student['parent_phone']) ?></span>
                        </div>
                        <div class="profile-field">
                            <label>البريد الإلكتروني:</label>
                            <span class="field-value"><?= htmlspecialchars($student['email']) ?></span>
                        </div>
                        <div class="profile-field">
                            <label>الصف الدراسي:</label>
                            <span class="field-value"><?= get_grade_text($student['grade']) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="profile-section">
                    <h3><i class="fas fa-info-circle"></i> معلومات الحساب</h3>
                    <div class="profile-grid">
                        <div class="profile-field">
                            <label>حالة الحساب:</label>
                            <span class="field-value">
                                <span class="status-badge status-<?= htmlspecialchars($student['status']) ?>">
                                    <?= get_status_text($student['status']) ?>
                                </span>
                            </span>
                        </div>
                        <div class="profile-field">
                            <label>تاريخ التسجيل:</label>
                            <span class="field-value"><?= date('Y-m-d', strtotime($student['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="profile-note">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>لتعديل أي من هذه البيانات، يرجى التواصل مع مسؤول المنصة.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-container {
    max-width: 800px;
    margin: 0 auto;
}

.profile-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
}

.profile-title h2 {
    margin: 0 0 5px;
    font-size: 1.8rem;
}

.profile-title p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.profile-body {
    padding: 30px;
}

.profile-section {
    margin-bottom: 30px;
}

.profile-section:last-child {
    margin-bottom: 0;
}

.profile-section h3 {
    margin: 0 0 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
    color: #333;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.profile-section h3 i {
    color: #667eea;
}

.profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.profile-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.profile-field label {
    font-weight: 600;
    color: #555;
    font-size: 0.95rem;
}

.field-value {
    color: #333;
    font-size: 1.05rem;
    padding: 8px 0;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-align: center;
    display: inline-block;
    min-width: 80px;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.profile-note {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px 20px;
    margin-top: 30px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.profile-note i {
    color: #856404;
    font-size: 1.2rem;
}

.profile-note p {
    margin: 0;
    color: #856404;
    font-size: 0.95rem;
}

@media (max-width: 768px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
        padding: 20px;
    }
    
    .profile-body {
        padding: 20px;
    }
    
    .profile-grid {
        grid-template-columns: 1fr;
    }
    
    .profile-field {
        text-align: center;
    }
}
</style>

<?php include 'partials/footer.php'; ?>
