<?php
$page_title = 'إدارة المحاضرات';
include 'partials/header.php';
require_once __DIR__ . '/../includes/db_connection.php';
$pdo = get_db_connection();

// معالجة حذف مرفق
if(isset($_GET['delete_attachment'])){
    $att_id = intval($_GET['delete_attachment']);
    $edit_lecture_id = intval($_GET['edit']);
    
    $stmt = $pdo->prepare("DELETE FROM lecture_attachments WHERE id = ?");
    $stmt->execute([$att_id]);
    echo "<div class='alert alert-success'>تم حذف المرفق. جاري تحديث الصفحة...</div>";
    echo "<meta http-equiv='refresh' content='2;url=lectures.php?edit={$edit_lecture_id}'>";
}

// معالجة إضافة وتعديل المحاضرات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_lecture'])) {
    $lecture_id = intval($_POST['lecture_id'] ?? 0);
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $playlist_id = intval($_POST['playlist_id']);
    $price = floatval($_POST['price']);
    $is_free = isset($_POST['is_free']) ? 1 : 0;
    $grade = sanitize_input($_POST['grade']);
    $max_views = intval($_POST['max_views']);
    $media_type = sanitize_input($_POST['media_type']);
    $bunny_library_id = sanitize_input($_POST['bunny_library_id'] ?? null);
    

    // --- *** START OF EMBED FIX (Saving) *** ---
    if ($media_type === 'embed') {
        // Use the specific lenient sanitizer for embed codes
        $media_source = sanitize_embed_content($_POST['media_source'] ?? '');
    } else {
        $media_source = sanitize_input($_POST['media_source'] );
    }
    // --- *** END OF EMBED FIX (Saving) *** ---

    if ($is_free) {
        $price = 0.00;
        $max_views = 9999;
    }

    
    $thumbnail_path = $_POST['current_thumbnail'] ?? null;
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $target_dir = __DIR__ . "/../uploads/thumbnails/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $thumbnail_name = uniqid() . '-' . basename($_FILES["thumbnail"]["name"]);
        $thumbnail_path = "uploads/thumbnails/" . $thumbnail_name;
        move_uploaded_file($_FILES["thumbnail"]["tmp_name"], __DIR__ . "/../" . $thumbnail_path);
    }
    
    if ($lecture_id > 0) { // تحديث
        $sql = "UPDATE lectures SET title=?, description=?, playlist_id=?, price=?, is_free=?, grade=?, max_views=?, media_type=?, media_source=?, bunny_library_id=?, thumbnail_path=? WHERE id=?";
        $params = [$title, $description, $playlist_id, $price, $is_free, $grade, $max_views, $media_type, $media_source, $bunny_library_id, $thumbnail_path, $lecture_id];
    } else { // إضافة
        $sql = "INSERT INTO lectures (title, description, playlist_id, price, is_free, grade, max_views, media_type, media_source, bunny_library_id, thumbnail_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$title, $description, $playlist_id, $price, $is_free, $grade, $max_views, $media_type, $media_source, $bunny_library_id, $thumbnail_path];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $saved_lecture_id = $lecture_id > 0 ? $lecture_id : $pdo->lastInsertId();
    echo "<div class='alert alert-success'>تم حفظ المحاضرة بنجاح.</div>";
}
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // You should also delete the associated files here (thumbnail)
    $stmt = $pdo->prepare("DELETE FROM lectures WHERE id = ?");
    $stmt->execute([$id]);
}


// جلب بيانات المحاضرة للتعديل
$edit_lecture = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM lectures WHERE id = ?");
    $stmt->execute([$id]);
    $edit_lecture = $stmt->fetch();
}

// جلب كل المحاضرات وقوائم التشغيل
// --- *** FIX STARTS HERE *** ---
// The `l.created_at` column was removed. Sorting by title or id.
$lectures = $pdo->query("SELECT l.*, p.name as playlist_name FROM lectures l JOIN playlists p ON l.playlist_id = p.id ORDER BY l.id DESC")->fetchAll();
// --- *** FIX ENDS HERE *** ---
$playlists = $pdo->query("SELECT id, name FROM playlists WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
?>

<div class="form-container">
    <h2><?= $edit_lecture ? 'تعديل محاضرة' : 'إضافة محاضرة جديدة' ?></h2>
    <form action="lectures.php<?= $edit_lecture ? '?edit='.$edit_lecture['id'] : '' ?>" method="post" enctype="multipart/form-data">
        <?php if ($edit_lecture): ?>
            <input type="hidden" name="lecture_id" value="<?= $edit_lecture['id'] ?>">
        <?php endif; ?>
        
        <div class="form-grid">
            <div class="form-group">
                <label for="title">عنوان المحاضرة:</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($edit_lecture['title'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="playlist_id">قائمة التشغيل:</label>
                <select id="playlist_id" name="playlist_id" required>
                    <option value="">-- اختر قائمة --</option>
                    <?php foreach ($playlists as $playlist): ?>
                        <option value="<?= $playlist['id'] ?>" <?= (($edit_lecture['playlist_id'] ?? '') == $playlist['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($playlist['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="grade">الصف الدراسي:</label>
                <select id="grade" name="grade" required>
                    <option value="first_secondary" <?= (($edit_lecture['grade'] ?? '') == 'first_secondary') ? 'selected' : '' ?>>الأول الثانوي</option>
                    <option value="second_secondary" <?= (($edit_lecture['grade'] ?? '') == 'second_secondary') ? 'selected' : '' ?>>الثاني الثانوي</option>
                    <option value="third_secondary" <?= (($edit_lecture['grade'] ?? '') == 'third_secondary') ? 'selected' : '' ?>>الثالث الثانوي</option>
                </select>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_free" id="is_free" value="1" <?= (($edit_lecture['is_free'] ?? 0) == 1) ? 'checked' : '' ?> onchange="togglePriceField()">
                    محاضرة مجانية
                </label>
            </div>

            <div class="form-group" id="price_field">
                <label for="price">سعر المحاضرة:</label>
                <input type="number" step="0.01" id="price" name="price" value="<?= htmlspecialchars($edit_lecture['price'] ?? '0.00') ?>">
            </div>

            <div class="form-group" id="max_views_field">
                <label for="max_views">عدد المشاهدات المسموح بها:</label>
                <input type="number" id="max_views" name="max_views" value="<?= htmlspecialchars($edit_lecture['max_views'] ?? '3') ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="description">وصف المحاضرة:</label>
            <textarea id="description" name="description" rows="4"><?= htmlspecialchars($edit_lecture['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="thumbnail">صورة مصغرة:</label>
            <input type="file" id="thumbnail" name="thumbnail" accept="image/*">
            <?php if (!empty($edit_lecture['thumbnail_path'])): ?>
                <div class="current-file">
                    <p>الصورة الحالية:</p>
                    <img src="../<?= htmlspecialchars($edit_lecture['thumbnail_path']) ?>" width="100">
                    <input type="hidden" name="current_thumbnail" value="<?= htmlspecialchars($edit_lecture['thumbnail_path']) ?>">
                </div>
            <?php endif; ?>
        </div>

        <hr>

        <div class="form-group">
            <label for="media_type">مصدر الفيديو:</label>
            <select id="media_type" name="media_type" onchange="toggleMediaFields()" required>
                <option value="">-- اختر المصدر --</option>
                <option value="bunny" <?= (($edit_lecture['media_type'] ?? '') == 'bunny') ? 'selected' : '' ?>>Bunny Stream</option>
                <option value="youtube" <?= (($edit_lecture['media_type'] ?? '') == 'youtube') ? 'selected' : '' ?>>YouTube</option>
                <option value="gdrive" <?= (($edit_lecture['media_type'] ?? '') == 'gdrive') ? 'selected' : '' ?>>Google Drive</option>
                <option value="peertube" <?= (($edit_lecture['media_type'] ?? '') == 'peertube') ? 'selected' : '' ?>>PeerTube</option>
                <option value="local" <?= (($edit_lecture['media_type'] ?? '') == 'local') ? 'selected' : '' ?>>رفع ملف من الجهاز</option>
                <option value="embed" <?= (($edit_lecture['media_type'] ?? '') == 'embed') ? 'selected' : '' ?>>تضمين (Embed)</option>
            </select>
        </div>

        <!-- حقول Bunny Stream -->
        <div id="bunny_fields" class="media-fields">
            <div class="form-grid">
                <div class="form-group">
                    <label for="bunny_library_id">رقم المكتبة (Library ID):</label>
                    <input type="text" id="bunny_library_id" name="bunny_library_id" value="<?= htmlspecialchars($edit_lecture['bunny_library_id'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="bunny_video_id">معرف الفيديو (Video ID):</label>
                    <input type="text" id="bunny_video_id" name="media_source" value="<?= ($edit_lecture['media_type'] ?? '') == 'bunny' ? htmlspecialchars($edit_lecture['media_source']) : '' ?>">
                </div>
            </div>
            <div class="preview-container" id="bunny_preview"></div>
        </div>

        <!-- حقول YouTube و Google Drive -->
        <div id="link_fields" class="media-fields">
            <div class="form-group">
                <label for="link_source">رابط الفيديو:</label>
                <input type="text" id="link_source" name="media_source" placeholder="https://..." value="<?= in_array(($edit_lecture['media_type'] ?? ''), ['youtube', 'gdrive']) ? htmlspecialchars($edit_lecture['media_source']) : '' ?>">
            </div>
            <div class="preview-container" id="link_preview"></div>
        </div>

        <!-- حقول PeerTube -->
        <div id="peertube_fields" class="media-fields">
            <div class="form-group">
                <label for="peertube_source">رابط فيديو PeerTube:</label>
                <input type="text" id="peertube_source" name="media_source" placeholder="https://..." value="<?= ($edit_lecture['media_type'] ?? '') == 'peertube' ? htmlspecialchars($edit_lecture['media_source']) : '' ?>">
            </div>
            <div class="preview-container" id="peertube_preview"></div>
        </div>

        <!-- حقول رفع ملف محلي -->
        <div id="local_fields" class="media-fields">
            <div class="form-group">
                <label for="local_video">اختر ملف فيديو:</label>
                <input type="file" id="local_video" name="local_video" accept="video/*">
                <?php if (!empty($edit_lecture['media_source']) && ($edit_lecture['media_type'] ?? '') == 'local'): ?>
                    <div class="current-file">
                        <p>الملف الحالي: <?= htmlspecialchars(basename($edit_lecture['media_source'])) ?></p>
                        <input type="hidden" name="current_video" value="<?= htmlspecialchars($edit_lecture['media_source']) ?>">
                    </div>
                <?php endif; ?>
            </div>
            <div class="preview-container" id="local_preview">
                <p>سيظهر معاينة الفيديو هنا بعد الرفع</p>
            </div>
        </div>

        <!-- حقول التضمين -->
        <div id="embed_fields" class="media-fields">
            <div class="form-group">
                <label for="embed_code">كود التضمين الكامل:</label>
                <textarea id="embed_code" name="media_source" rows="8" placeholder='&lt;div class="wistia_responsive_padding" style="padding:56.25% 0 0 0;position:relative;"&gt;...&lt;/div&gt;'><?= ($edit_lecture['media_type'] ?? '') == 'embed' ? htmlspecialchars($edit_lecture['media_source']) : '' ?></textarea>
                <small>قم بلصق كود التضمين الكامل بما في ذلك علامات &lt;script&gt; إذا كانت موجودة</small>
            </div>
            <div class="preview-container" id="embed_preview">
                <p>سيظهر معاينة التضمين هنا بعد الحفظ</p>
            </div>
        </div>

        <hr>

        <div class="form-group">
            <label>إضافة مرفقات (روابط):</label>
            <div id="attachment_links">
                <div class="attachment-link">
                    <input type="text" name="attachment_links[]" placeholder="رابط المرفق (PDF, صورة, إلخ)">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeAttachmentLink(this)">حذف</button>
                </div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="addAttachmentLink()">+ إضافة رابط آخر</button>
        </div>

        <?php if ($edit_lecture): ?>
            <h3>المرفقات الحالية:</h3>
            <?php
            $att_stmt = $pdo->prepare("SELECT * FROM lecture_attachments WHERE lecture_id = ?");
            $att_stmt->execute([$edit_lecture['id']]);
            $attachments = $att_stmt->fetchAll();
            ?>
            <?php if ($attachments): ?>
                <ul class="attachments-list">
                    <?php foreach ($attachments as $att): ?>
                        <li>
                            <a href="<?= htmlspecialchars($att['file_path']) ?>" target="_blank"><?= htmlspecialchars($att['file_name']) ?></a>
                            <a href="lectures.php?edit=<?= $edit_lecture['id'] ?>&delete_attachment=<?= $att['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من حذف هذا المرفق؟')">حذف</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>لا توجد مرفقات.</p>
            <?php endif; ?>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" name="save_lecture" class="btn btn-primary"><?= $edit_lecture ? 'تحديث' : 'إضافة' ?> المحاضرة</button>
            <?php if ($edit_lecture): ?>
                <a href="lectures.php" class="btn btn-secondary">إلغاء</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
function togglePriceField() {
    const isFree = document.getElementById('is_free').checked;
    const priceField = document.getElementById('price_field');
    const maxViewsField = document.getElementById('max_views_field');
    
    if (isFree) {
        priceField.style.display = 'none';
        maxViewsField.style.display = 'none';
        document.getElementById('price').value = '0';
        document.getElementById('max_views').value = '9999';
    } else {
        priceField.style.display = 'block';
        maxViewsField.style.display = 'block';
        document.getElementById('max_views').value = '3';
    }
}

function toggleMediaFields() {
    const mediaType = document.getElementById('media_type').value;
    document.querySelectorAll('.media-fields').forEach(field => {
        field.style.display = 'none';
        field.querySelector('.preview-container').innerHTML = '<p>جاري تحميل المعاينة...</p>';
    });
    
    if (mediaType === 'bunny') {
        document.getElementById('bunny_fields').style.display = 'block';
        updateBunnyPreview();
    } else if (['youtube', 'gdrive'].includes(mediaType)) {
        document.getElementById('link_fields').style.display = 'block';
        updateLinkPreview();
    } else if (mediaType === 'peertube') {
        document.getElementById('peertube_fields').style.display = 'block';
        updatePeerTubePreview();
    } else if (mediaType === 'local') {
        document.getElementById('local_fields').style.display = 'block';
        updateLocalPreview();
    } else if (mediaType === 'embed') {
        document.getElementById('embed_fields').style.display = 'block';
        updateEmbedPreview();
    }
}

function updateBunnyPreview() {
    const libraryId = document.getElementById('bunny_library_id').value;
    const videoId = document.getElementById('bunny_video_id').value;
    const preview = document.getElementById('bunny_preview');
    
    if (libraryId && videoId) {
        preview.innerHTML = `
            <h4>معاينة Bunny Stream:</h4>
            <div style="position:relative;padding-top:56.25%;">
                <iframe src="https://iframe.mediadelivery.net/embed/${libraryId}/${videoId}?autoplay=false" 
                        loading="lazy" 
                        style="border:0;position:absolute;top:0;height:100%;width:100%;" 
                        allow="accelerometer;gyroscope;autoplay;encrypted-media;picture-in-picture;" 
                        allowfullscreen="true"></iframe>
            </div>
        `;
    } else {
        preview.innerHTML = '<p>أدخل بيانات Bunny Stream لعرض المعاينة</p>';
    }
}

function updateLinkPreview() {
    const link = document.getElementById('link_source').value;
    const preview = document.getElementById('link_preview');
    const mediaType = document.getElementById('media_type').value;
    
    if (link) {
        if (mediaType === 'youtube') {
            const videoId = extractYouTubeId(link);
            if (videoId) {
                preview.innerHTML = `
                    <h4>معاينة YouTube:</h4>
                    <div style="position:relative;padding-top:56.25%;">
                        <iframe src="https://www.youtube.com/embed/${videoId}?rel=0" 
                                style="border:0;position:absolute;top:0;height:100%;width:100%;" 
                                allowfullscreen></iframe>
                    </div>
                `;
            } else {
                preview.innerHTML = '<p style="color:red;">رابط YouTube غير صالح</p>';
            }
        } else if (mediaType === 'gdrive') {
            const fileId = extractGoogleDriveId(link);
            if (fileId) {
                preview.innerHTML = `
                    <h4>معاينة Google Drive:</h4>
                    <div style="position:relative;padding-top:56.25%;">
                        <iframe src="https://drive.google.com/file/d/${fileId}/preview" 
                                style="border:0;position:absolute;top:0;height:100%;width:100%;" 
                                allowfullscreen></iframe>
                    </div>
                `;
            } else {
                preview.innerHTML = '<p style="color:red;">رابط Google Drive غير صالح</p>';
            }
        }
    } else {
        preview.innerHTML = '<p>أدخل الرابط لعرض المعاينة</p>';
    }
}

function updatePeerTubePreview() {
    const link = document.getElementById('peertube_source').value;
    const preview = document.getElementById('peertube_preview');
    
    if (link) {
        preview.innerHTML = `
            <h4>معاينة PeerTube:</h4>
            <div style="position:relative;padding-top:56.25%;">
                <iframe src="${link}" 
                        style="border:0;position:absolute;top:0;height:100%;width:100%;" 
                        allowfullscreen></iframe>
            </div>
        `;
    } else {
        preview.innerHTML = '<p>أدخل رابط PeerTube لعرض المعاينة</p>';
    }
}

function updateLocalPreview() {
    const fileInput = document.getElementById('local_video');
    const preview = document.getElementById('local_preview');
    
    if (fileInput.files && fileInput.files[0]) {
        const file = fileInput.files[0];
        const url = URL.createObjectURL(file);
        preview.innerHTML = `
            <h4>معاينة الفيديو المحلي:</h4>
            <video controls width="100%" style="max-width: 600px;">
                <source src="${url}" type="${file.type}">
                متصفحك لا يدعم تشغيل الفيديو.
            </video>
            <p>اسم الملف: ${file.name}</p>
            <p>الحجم: ${(file.size / (1024 * 1024)).toFixed(2)} MB</p>
        `;
    } else {
        preview.innerHTML = '<p>اختر ملف فيديو لعرض المعاينة</p>';
    }
}

function updateEmbedPreview() {
    const embedCode = document.getElementById('embed_code').value;
    const preview = document.getElementById('embed_preview');
    
    if (embedCode.trim()) {
        preview.innerHTML = `
            <h4>معاينة التضمين:</h4>
            <div class="embed-preview-wrapper">
                ${embedCode}
            </div>
        `;
    } else {
        preview.innerHTML = '<p>أدخل كود التضمين لعرض المعاينة</p>';
    }
}

// دوال مساعدة لاستخراج المعرفات
function extractYouTubeId(url) {
    const regExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#&?]*).*/;
    const match = url.match(regExp);
    return (match && match[7].length === 11) ? match[7] : false;
}

function extractGoogleDriveId(url) {
    const match = url.match(/\/d\/([^\/]+)/);
    return match ? match[1] : false;
}

// إضافة event listeners للحقول
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة الحقول
    togglePriceField();
    toggleMediaFields();
    
    // إضافة event listeners للتحديث التلقائي للمعاينة
    document.getElementById('bunny_library_id').addEventListener('input', updateBunnyPreview);
    document.getElementById('bunny_video_id').addEventListener('input', updateBunnyPreview);
    document.getElementById('link_source').addEventListener('input', updateLinkPreview);
    document.getElementById('peertube_source').addEventListener('input', updatePeerTubePreview);
    document.getElementById('local_video').addEventListener('change', updateLocalPreview);
    document.getElementById('embed_code').addEventListener('input', updateEmbedPreview);
    
    // إذا كان هناك بيانات محفوظة مسبقاً، تحديث المعاينة
    <?php if ($edit_lecture): ?>
    setTimeout(() => {
        const mediaType = '<?= $edit_lecture['media_type'] ?>';
        if (mediaType === 'bunny') updateBunnyPreview();
        else if (['youtube', 'gdrive'].includes(mediaType)) updateLinkPreview();
        else if (mediaType === 'peertube') updatePeerTubePreview();
        else if (mediaType === 'embed') updateEmbedPreview();
    }, 500);
    <?php endif; ?>
});

function addAttachmentLink() {
    const container = document.getElementById('attachment_links');
    const newLink = document.createElement('div');
    newLink.className = 'attachment-link';
    newLink.innerHTML = `
        <input type="text" name="attachment_links[]" placeholder="رابط المرفق (PDF, صورة, إلخ)">
        <button type="button" class="btn btn-danger btn-sm" onclick="removeAttachmentLink(this)">حذف</button>
    `;
    container.appendChild(newLink);
}

function removeAttachmentLink(button) {
    button.parentElement.remove();
}
</script>

<style>
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.media-fields {
    border: 1px solid #ddd;
    padding: 20px;
    margin: 15px 0;
    border-radius: 8px;
    background: #f9f9f9;
}

.preview-container {
    margin-top: 15px;
    padding: 15px;
    background: white;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.preview-container h4 {
    margin-top: 0;
    color: #333;
}

.attachment-link {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    align-items: center;
}

.attachment-link input {
    flex: 1;
}

.attachments-list {
    list-style: none;
    padding: 0;
}

.attachments-list li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.attachments-list li:last-child {
    border-bottom: none;
}

.current-file {
    margin-top: 10px;
    padding: 10px;
    background: #f0f0f0;
    border-radius: 6px;
}

.embed-preview-wrapper {
    max-width: 100%;
    overflow: hidden;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .attachment-link {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<?php if (!$edit_lecture): ?>
<div class="table-container">
    <h2>المحاضرات المضافة</h2>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>العنوان</th>
                    <th>القائمة</th>
                    <th>الصف</th>
                    <th>النوع</th>
                    <th>مصدر الفيديو</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lectures)): ?>
                    <tr>
                        <td colspan="7" class="text-center">لا توجد محاضرات.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($lectures as $index => $lecture): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($lecture['title']) ?></td>
                            <td><?= htmlspecialchars($lecture['playlist_name']) ?></td>
                            <td><?= get_grade_text($lecture['grade']) ?></td>
                            <td>
                                <span class="badge <?= $lecture['is_free'] ? 'badge-success' : 'badge-warning' ?>">
                                    <?= $lecture['is_free'] ? 'مجانية' : 'مدفوعة' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($lecture['media_type']) ?></td>
                            <td class="actions">
                                <a href="lectures.php?edit=<?= $lecture['id'] ?>" class="btn btn-sm btn-primary">تعديل</a>
                                <a href="lectures.php?delete=<?= $lecture['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذه المحاضرة؟')">حذف</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include 'partials/footer.php'; ?>