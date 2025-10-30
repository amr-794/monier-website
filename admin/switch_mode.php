<?php
$page_title = 'وضع الهاتف (التبديل)';
include 'partials/header.php';
require_once __DIR__ . '/../includes/db_connection.php';
$pdo = get_db_connection();
$message = '';

// معالجة تحديث الإعدادات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // تحديث حالة وضع الهاتف
    $mobile_only_mode = isset($_POST['mobile_only_mode']) ? '1' : '0';
    set_setting('mobile_only_mode', $mobile_only_mode);

    // تحديث رسالة المتصفح
    $browser_message = sanitize_input($_POST['browser_message'], true); // السماح ببعض الـ HTML
    set_setting('browser_message', $browser_message);
    
    // تحديث روابط التحميل
    $app_links = [];
    if(isset($_POST['app_link_text']) && isset($_POST['app_link_url'])) {
        for($i = 0; $i < count($_POST['app_link_text']); $i++) {
            if(!empty($_POST['app_link_text'][$i]) && !empty($_POST['app_link_url'][$i])) {
                $app_links[] = [
                    'text' => sanitize_input($_POST['app_link_text'][$i]),
                    'url' => sanitize_input($_POST['app_link_url'][$i])
                ];
            }
        }
    }
    set_setting('app_download_links', json_encode($app_links));

    // معالجة رفع ملف APK
    if (isset($_FILES['apk_file']) && $_FILES['apk_file']['error'] == 0) {
        $target_dir = __DIR__ . "/../../uploads/apks/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        
        $file_name = uniqid() . '-' . basename($_FILES["apk_file"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["apk_file"]["tmp_name"], $target_file)) {
            $current_apks = json_decode(get_setting('app_apk_files', '[]'), true);
            $current_apks[] = [
                'name' => sanitize_input($_POST['apk_file_name']),
                'path' => 'uploads/apks/' . $file_name
            ];
            set_setting('app_apk_files', json_encode($current_apks));
            $message .= "<div class='alert alert-success'>تم رفع ملف APK بنجاح.</div>";
        } else {
            $message .= "<div class='alert alert-danger'>حدث خطأ أثناء رفع الملف.</div>";
        }
    }

    $message .= "<div class='alert alert-success'>تم حفظ الإعدادات بنجاح!</div>";
}

// معالجة حذف APK
if(isset($_GET['delete_apk'])){
    $apk_path_to_delete = $_GET['delete_apk'];
    $current_apks = json_decode(get_setting('app_apk_files', '[]'), true);
    $updated_apks = array_filter($current_apks, function($apk) use ($apk_path_to_delete) {
        return $apk['path'] !== $apk_path_to_delete;
    });
    
    // حذف الملف الفعلي من السيرفر
    if(file_exists(__DIR__ . "/../../" . $apk_path_to_delete)) {
        unlink(__DIR__ . "/../../" . $apk_path_to_delete);
    }
    
    set_setting('app_apk_files', json_encode(array_values($updated_apks)));
    $message .= "<div class='alert alert-success'>تم حذف ملف APK.</div>";
}

// جلب الإعدادات الحالية
$current_mode = get_setting('mobile_only_mode', '0');
$current_message = get_setting('browser_message', '<h2>المشاهدة من التطبيق فقط</h2><p>عذراً، لا يمكنك مشاهدة المحاضرة من المتصفح. يرجى تحميل التطبيق الخاص بنا للمتابعة.</p>');
$current_links = json_decode(get_setting('app_download_links', '[]'), true);
$current_apks = json_decode(get_setting('app_apk_files', '[]'), true);

?>

<div class="page-header">
    <h1>وضع المشاهدة من الهاتف فقط</h1>
    <p>تحكم في كيفية وصول الطلاب للمحاضرات، وإجبارهم على استخدام تطبيق الهاتف.</p>
</div>
<?= $message ?>
<form method="POST" action="switch_mode.php" enctype="multipart/form-data">
    <div class="card">
        <div class="card-header">
            <h3>تفعيل / تعطيل الوضع</h3>
        </div>
        <div class="card-body">
            <div class="form-group toggle-switch-container">
                <label for="mobile_only_mode" class="toggle-label">تفعيل وضع الهاتف فقط:</label>
                <label class="switch">
                    <input type="checkbox" id="mobile_only_mode" name="mobile_only_mode" value="1" <?= $current_mode == '1' ? 'checked' : '' ?>>
                    <span class="slider round"></span>
                </label>
                <small class="form-text">عند التفعيل، لن يتمكن الطلاب من مشاهدة المحاضرات من متصفح الويب وستظهر لهم الرسالة المخصصة أدناه.</small>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>تخصيص رسالة المتصفح</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="browser_message">الرسالة التي تظهر للطالب:</label>
                <textarea id="browser_message" name="browser_message" class="wysiwyg" rows="8"><?= htmlspecialchars($current_message) ?></textarea>
                 <small class="form-text">يمكنك استخدام HTML بسيط لتنسيق الرسالة.</small>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>إدارة روابط التحميل</h3>
        </div>
        <div class="card-body" id="app-links-container">
            <?php if(empty($current_links)): ?>
            <div class="link-group">
                <input type="text" name="app_link_text[]" placeholder="نص الزر (مثال: Google Play)">
                <input type="url" name="app_link_url[]" placeholder="رابط التحميل">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeLink(this)">حذف</button>
            </div>
            <?php else: ?>
                <?php foreach($current_links as $link): ?>
                <div class="link-group">
                    <input type="text" name="app_link_text[]" value="<?= htmlspecialchars($link['text']) ?>" placeholder="نص الزر">
                    <input type="url" name="app_link_url[]" value="<?= htmlspecialchars($link['url']) ?>" placeholder="رابط التحميل">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeLink(this)">حذف</button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <button type="button" class="btn btn-secondary" onclick="addLink()">+ إضافة رابط جديد</button>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>إدارة ملفات APK المباشرة</h3>
        </div>
        <div class="card-body">
            <h4>الملفات الحالية:</h4>
            <?php if(empty($current_apks)): ?>
                <p>لا توجد ملفات مرفوعة حالياً.</p>
            <?php else: ?>
            <ul>
                <?php foreach($current_apks as $apk): ?>
                    <li>
                        <?= htmlspecialchars($apk['name']) ?> 
                        (<a href="../<?= htmlspecialchars($apk['path']) ?>">تحميل</a>) - 
                        <a href="switch_mode.php?delete_apk=<?= urlencode($apk['path']) ?>" class="text-danger" onclick="return confirm('هل أنت متأكد؟')">حذف</a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <hr>
            <h4>رفع ملف جديد:</h4>
            <div class="form-group">
                <label for="apk_file_name">اسم الملف (كما سيظهر للطالب):</label>
                <input type="text" name="apk_file_name" class="form-control" placeholder="مثال: تطبيق المنصة نسخة 1.1">
            </div>
            <div class="form-group">
                <label for="apk_file">اختر ملف APK:</label>
                <input type="file" name="apk_file" class="form-control" accept=".apk">
            </div>
        </div>
    </div>

    <div class="form-actions sticky-footer">
        <button type="submit" class="btn btn-primary btn-lg">حفظ كل الإعدادات</button>
    </div>
</form>

<script>
function addLink() {
    const container = document.getElementById('app-links-container');
    const newLinkGroup = document.createElement('div');
    newLinkGroup.className = 'link-group';
    newLinkGroup.innerHTML = `
        <input type="text" name="app_link_text[]" placeholder="نص الزر">
        <input type="url" name="app_link_url[]" placeholder="رابط التحميل">
        <button type="button" class="btn btn-danger btn-sm" onclick="removeLink(this)">حذف</button>
    `;
    container.appendChild(newLinkGroup);
}

function removeLink(button) {
    button.parentElement.remove();
}
</script>
<?php include 'partials/footer.php'; ?>