
<?php
$page_title = 'عرض القائمة';
include 'partials/header.php';
require_once __DIR__ . '/../includes/db_connection.php';
$pdo = get_db_connection();

$student_id = $user['id'];
$student_grade = $user['grade'];
$playlist_id = intval($_GET['id'] ?? 0);
if (!$playlist_id) {
    redirect('index.php');
}

// جلب بيانات القائمة
$playlist_stmt = $pdo->prepare("SELECT * FROM playlists WHERE id = ? AND is_active = 1");
$playlist_stmt->execute([$playlist_id]);
$playlist = $playlist_stmt->fetch();
if (!$playlist) {
    redirect('index.php');
}

// جلب صلاحيات وصول الطالب
$access_stmt = $pdo->prepare("SELECT lecture_id, remaining_views FROM student_lecture_access WHERE student_id = ?");
$access_stmt->execute([$student_id]);
$my_lectures_access = $access_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// جلب محاضرات القائمة للصف الدراسي الحالي
$lectures_stmt = $pdo->prepare("SELECT * FROM lectures WHERE playlist_id = ? AND (grade = ? OR grade = 'all') AND is_active = 1 ORDER BY id ASC");
$lectures_stmt->execute([$playlist_id, $student_grade]);
$lectures = $lectures_stmt->fetchAll();
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-list"></i> <?= htmlspecialchars($playlist['name']) ?></h1>
        <p><?= htmlspecialchars($playlist['description'] ?? '') ?></p>
        <div class="page-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> العودة لكل القوائم
            </a>
        </div>
    </div>

    <div class="lectures-container">
        <div class="lectures-header">
            <h2><i class="fas fa-play-circle"></i> محاضرات القائمة</h2>
            <span class="lectures-count"><?= count($lectures) ?> محاضرة</span>
        </div>

        <div class="lectures-list">
            <?php if(empty($lectures)): ?>
                <div class="empty-state">
                    <i class="fas fa-video-slash"></i>
                    <p>لا توجد محاضرات متاحة في هذه القائمة حالياً.</p>
                </div>
            <?php else: ?>
                <?php foreach ($lectures as $index => $lecture): ?>
                <div class="lecture-card">
                    <div class="lecture-number">
                        <?= $index + 1 ?>
                    </div>
                    <div class="lecture-thumb">
                        <img src="../<?= htmlspecialchars($lecture['thumbnail_path'] ?? 'assets/images/default-thumb.png') ?>" alt="<?= htmlspecialchars($lecture['title']) ?>">
                        <?php if($lecture['is_free']): ?>
                            <span class="free-badge">مجانية</span>
                        <?php endif; ?>
                    </div>
                    <div class="lecture-content">
                        <h3 class="lecture-title"><?= htmlspecialchars($lecture['title']) ?></h3>
                        <p class="lecture-description"><?= htmlspecialchars(mb_substr($lecture['description'] ?? '', 0, 150)) ?>...</p>
                        <div class="lecture-meta">
                            <span class="meta-item">
                                <i class="fas fa-user-graduate"></i>
                                <?= get_grade_text($lecture['grade']) ?>
                            </span>
                            <?php if(!$lecture['is_free']): ?>
                            <span class="meta-item price">
                                <i class="fas fa-tag"></i>
                                <?= (float)$lecture['price'] ?> جنيه
                            </span>
                            <?php endif; ?>
                            <span class="meta-item">
                                <i class="fas fa-eye"></i>
                                <?= $lecture['max_views'] ?> مشاهدة
                            </span>
                        </div>
                    </div>
                    <div class="lecture-actions">
                        <?php if ($lecture['is_free']): ?>
                            <a href="../view_lecture.php?id=<?= $lecture['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-play"></i> مشاهدة
                            </a>
                        <?php elseif (isset($my_lectures_access[$lecture['id']]) && $my_lectures_access[$lecture['id']] > 0): ?>
                            <a href="../view_lecture.php?id=<?= $lecture['id'] ?>" class="btn btn-success">
                                <i class="fas fa-play"></i> مشاهدة (<?= $my_lectures_access[$lecture['id']] ?> متبقي)
                            </a>
                        <?php elseif (isset($my_lectures_access[$lecture['id']])): ?>
                            <a href="activate_lecture.php?lecture_id=<?= $lecture['id'] ?>" class="btn btn-warning">
                                <i class="fas fa-sync-alt"></i> تجديد الكود
                            </a>
                        <?php else: ?>
                            <a href="activate_lecture.php?lecture_id=<?= $lecture['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-key"></i> تفعيل بكود
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.page-container {
    max-width: 1000px;
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

.page-actions {
    margin-top: 15px;
}

.lectures-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.lectures-header {
    background: #f8f9fa;
    padding: 20px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e9ecef;
}

.lectures-header h2 {
    margin: 0;
    color: #333;
    font-size: 1.4rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.lectures-count {
    background: #667eea;
    color: white;
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 0.9rem;
    font-weight: 600;
}

.lectures-list {
    padding: 0;
}

.lecture-card {
    display: grid;
    grid-template-columns: auto 120px 1fr auto;
    align-items: center;
    gap: 20px;
    padding: 20px 25px;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s ease;
}

.lecture-card:hover {
    background: #f8f9fa;
}

.lecture-card:last-child {
    border-bottom: none;
}

.lecture-number {
    font-size: 24px;
    font-weight: bold;
    color: #ddd;
    width: 40px;
    text-align: center;
}

.lecture-thumb {
    position: relative;
    width: 120px;
    height: 70px;
    border-radius: 8px;
    overflow: hidden;
}

.lecture-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.free-badge {
    position: absolute;
    top: 5px;
    left: 5px;
    background: #28a745;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: bold;
}

.lecture-content {
    flex: 1;
}

.lecture-title {
    margin: 0 0 8px;
    color: #333;
    font-size: 1.2rem;
    line-height: 1.3;
}

.lecture-description {
    margin: 0 0 12px;
    color: #666;
    font-size: 0.95rem;
    line-height: 1.4;
}

.lecture-meta {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
    font-size: 0.9rem;
}

.meta-item.price {
    color: #e74c3c;
    font-weight: 600;
}

.lecture-actions {
    min-width: 160px;
    text-align: center;
}

.lecture-actions .btn {
    width: 100%;
    margin-bottom: 5px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 20px;
    color: #ccc;
}

.empty-state p {
    margin: 0;
    font-size: 1.1rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.btn-primary {
    background: #667eea;
    color: white;
}

.btn-primary:hover {
    background: #5a6fd8;
    transform: translateY(-2px);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
    transform: translateY(-2px);
}

.btn-warning {
    background: #ffc107;
    color: #212529;
}

.btn-warning:hover {
    background: #e0a800;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .page-container {
        padding: 15px;
    }
    
    .lecture-card {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 15px;
        padding: 15px;
    }
    
    .lecture-number {
        width: 100%;
        font-size: 20px;
    }
    
    .lecture-thumb {
        margin: 0 auto;
    }
    
    .lecture-meta {
        justify-content: center;
    }
    
    .lectures-header {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
    
    .lecture-actions {
        min-width: auto;
    }
    
    .lecture-actions .btn {
        width: auto;
        min-width: 160px;
    }
}
</style>

<?php include 'partials/footer.php'; ?>
