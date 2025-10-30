
<?php
$page_title = 'المحاضرات';
include 'partials/header.php';
require_once __DIR__ . '/../includes/db_connection.php';
$pdo = get_db_connection();

$student_grade = $user['grade'];

// جلب الإعلانات العامة
$announcements = $pdo->query("SELECT * FROM announcements WHERE is_visible = 1 AND is_notification = 0 ORDER BY created_at DESC LIMIT 3")->fetchAll();

// جلب قوائم التشغيل المتاحة لصف الطالب
$stmt = $pdo->prepare("
    SELECT p.*, (SELECT COUNT(*) FROM lectures l WHERE l.playlist_id = p.id AND l.is_active = 1 AND (l.grade = ? OR l.grade = 'all')) as lecture_count
    FROM playlists p 
    WHERE p.is_active = 1 AND (p.grade = ? OR p.grade = 'all')
    HAVING lecture_count > 0
    ORDER BY p.sort_order, p.name
");
$stmt->execute([$student_grade, $student_grade]);
$playlists = $stmt->fetchAll();
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-play-circle"></i> قوائم التشغيل المتاحة</h1>
        <p>تصفح قوائم التشغيل واختر ما تريد مشاهدته.</p>
    </div>

    <?php if (!empty($announcements)): ?>
    <div class="announcements-section">
        <h2 class="section-title"><i class="fas fa-bullhorn"></i> الإعلانات</h2>
        <div class="announcements-grid">
            <?php foreach ($announcements as $ann): ?>
            <div class="announcement-card">
                <div class="announcement-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="announcement-content">
                    <h4><?= htmlspecialchars($ann['title']) ?></h4>
                    <div class="announcement-text"><?= $ann['content'] ?></div>
                    <div class="announcement-date">
                        <i class="far fa-calendar"></i> <?= date('Y-m-d', strtotime($ann['created_at'])) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="playlists-section">
        <h2 class="section-title"><i class="fas fa-list"></i> قوائم التشغيل</h2>
        <div class="playlists-grid">
            <?php if (empty($playlists)): ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <p>لا توجد قوائم تشغيل متاحة لصفك الدراسي حالياً.</p>
                </div>
            <?php else: ?>
                <?php foreach ($playlists as $playlist): ?>
                <div class="playlist-card">
                    <a href="view_playlist.php?id=<?= $playlist['id'] ?>" class="playlist-link">
                        <div class="playlist-cover">
                            <?php if (!empty($playlist['cover_image'])): ?>
                                <img src="../<?= htmlspecialchars($playlist['cover_image']) ?>" alt="<?= htmlspecialchars($playlist['name']) ?>">
                            <?php else: ?>
                                <div class="default-cover">
                                    <i class="fas fa-film"></i>
                                </div>
                            <?php endif; ?>
                            <div class="playlist-overlay">
                                <span class="view-playlist-btn">عرض المحاضرات</span>
                            </div>
                        </div>
                        <div class="playlist-info">
                            <h3 class="playlist-title"><?= htmlspecialchars($playlist['name']) ?></h3>
                            <p class="playlist-description"><?= htmlspecialchars($playlist['description'] ?? 'لا يوجد وصف') ?></p>
                            <div class="playlist-meta">
                                <span class="lecture-count">
                                    <i class="fas fa-play-circle"></i> <?= $playlist['lecture_count'] ?> محاضرة
                                </span>
                                <?php if($playlist['grade'] != 'all'): ?>
                                <span class="playlist-grade">
                                    <i class="fas fa-user-graduate"></i> <?= get_grade_text($playlist['grade']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.page-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.page-header h1 {
    margin: 0 0 10px;
    font-size: 2rem;
}

.page-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.section-title {
    margin: 30px 0 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
    color: #333;
    font-size: 1.5rem;
}

.section-title i {
    margin-left: 10px;
    color: #667eea;
}

/* تنسيق الإعلانات */
.announcements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.announcement-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-right: 4px solid #667eea;
    display: flex;
    transition: transform 0.3s ease;
}

.announcement-card:hover {
    transform: translateY(-5px);
}

.announcement-icon {
    font-size: 24px;
    color: #667eea;
    margin-left: 15px;
    margin-top: 5px;
}

.announcement-content {
    flex: 1;
}

.announcement-content h4 {
    margin: 0 0 10px;
    color: #333;
    font-size: 1.2rem;
}

.announcement-text {
    color: #666;
    margin-bottom: 10px;
    line-height: 1.5;
}

.announcement-date {
    color: #888;
    font-size: 0.9rem;
}

/* تنسيق قوائم التشغيل */
.playlists-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
}

.playlist-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.playlist-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.playlist-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.playlist-cover {
    position: relative;
    height: 160px;
    overflow: hidden;
}

.playlist-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.playlist-card:hover .playlist-cover img {
    transform: scale(1.1);
}

.default-cover {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 48px;
}

.playlist-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.playlist-card:hover .playlist-overlay {
    opacity: 1;
}

.view-playlist-btn {
    color: white;
    background: #667eea;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
}

.playlist-info {
    padding: 20px;
}

.playlist-title {
    margin: 0 0 10px;
    font-size: 1.3rem;
    color: #333;
    line-height: 1.3;
}

.playlist-description {
    color: #666;
    margin-bottom: 15px;
    line-height: 1.4;
    font-size: 0.95rem;
}

.playlist-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
    color: #888;
}

.lecture-count, .playlist-grade {
    display: flex;
    align-items: center;
    gap: 5px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
    grid-column: 1 / -1;
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

@media (max-width: 768px) {
    .page-container {
        padding: 15px;
    }
    
    .page-header h1 {
        font-size: 1.6rem;
    }
    
    .announcements-grid {
        grid-template-columns: 1fr;
    }
    
    .playlists-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
    
    .announcement-card {
        flex-direction: column;
        text-align: center;
    }
    
    .announcement-icon {
        margin-left: 0;
        margin-bottom: 10px;
    }
}
</style>

<?php include 'partials/footer.php'; ?>
