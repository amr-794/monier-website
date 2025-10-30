
<?php
require_once 'includes/functions.php';

// التحقق من تسجيل الدخول
if (!current_user()) {
    redirect('login.php');
}

// التحقق من وجود معرف المحاضرة
if (!isset($_GET['id'])) {
    die('معرف المحاضرة مطلوب.');
}

$lecture_id = intval($_GET['id']);
require_once 'includes/db_connection.php';

// جلب بيانات المحاضرة
$stmt = $pdo->prepare("SELECT l.*, p.name as playlist_name FROM lectures l JOIN playlists p ON l.playlist_id = p.id WHERE l.id = ?");
$stmt->execute([$lecture_id]);
$lecture = $stmt->fetch();

if (!$lecture) {
    die('المحاضرة غير موجودة.');
}

// التحقق من صلاحية الوصول للمحاضرة
$user = current_user();
$has_access = false;
$remaining_views = 0;

if ($user['type'] === 'admin') {
    // الأدمن لديه وصول كامل
    $has_access = true;
} elseif ($user['type'] === 'student') {
    // التحقق من أن المحاضرة للصف الدراسي الصحيح
    if ($lecture['grade'] !== 'all' && $lecture['grade'] !== $user['grade']) {
        die('هذه المحاضرة غير متاحة لصفك الدراسي.');
    }
    
    // التحقق من الوصول للمحاضرة
    if ($lecture['is_free']) {
        // المحاضرة مجانية - الوصول مفتوح
        $has_access = true;
        $remaining_views = 'unlimited';
    } else {
        // التحقق من وجود كود تفعيل
        $stmt = $pdo->prepare("SELECT * FROM student_lecture_access WHERE student_id = ? AND lecture_id = ?");
        $stmt->execute([$user['id'], $lecture_id]);
        $access = $stmt->fetch();
        
        if ($access && $access['remaining_views'] > 0) {
            $has_access = true;
            $remaining_views = $access['remaining_views'];
        } else {
            // التحقق من وجود كود غير مستخدم
            $stmt = $pdo->prepare("SELECT * FROM codes WHERE lecture_id = ? AND is_used = 0 LIMIT 1");
            $stmt->execute([$lecture_id]);
            $available_code = $stmt->fetch();
            
            if ($available_code) {
                echo "<div class='access-denied-container'>
                        <div class='access-denied-card'>
                            <div class='access-icon'>
                                <i class='fas fa-key'></i>
                            </div>
                            <h3>هذه المحاضرة تتطلب كود تفعيل</h3>
                            <p>يجب عليك تفعيل المحاضرة باستخدام كود قبل مشاهدتها.</p>
                            <a href='student/activate_lecture.php?lecture_id={$lecture_id}' class='btn btn-primary'>
                                <i class='fas fa-check-circle'></i> تفعيل المحاضرة
                            </a>
                        </div>
                      </div>";
            } else {
                echo "<div class='access-denied-container'>
                        <div class='access-denied-card'>
                            <div class='access-icon'>
                                <i class='fas fa-times-circle'></i>
                            </div>
                            <h3>لا توجد أكواد متاحة</h3>
                            <p>لا توجد أكواد متاحة لهذه المحاضرة حالياً.</p>
                            <a href='student/index.php' class='btn btn-secondary'>
                                <i class='fas fa-arrow-right'></i> العودة للقائمة
                            </a>
                        </div>
                      </div>";
            }
            exit;
        }
    }
}

if (!$has_access) {
    die('ليس لديك صلاحية الوصول لهذه المحاضرة.');
}

// جلب المرفقات
$att_stmt = $pdo->prepare("SELECT * FROM lecture_attachments WHERE lecture_id = ?");
$att_stmt->execute([$lecture_id]);
$attachments = $att_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lecture['title']) ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --primary-dark: #5a6fd8;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-bg: #f8f9fa;
            --dark-text: #333;
            --gray-text: #666;
            --border-color: #e9ecef;
            --shadow: 0 4px 15px rgba(0,0,0,0.1);
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light-bg);
            color: var(--dark-text);
            line-height: 1.6;
        }

        .lecture-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* الهيدر المحسن */
        .lecture-header {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
            border-right: 4px solid var(--primary-color);
        }

        .lecture-header h1 {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: var(--dark-text);
            line-height: 1.3;
        }

        .lecture-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-text);
            font-size: 0.95rem;
        }

        .meta-item i {
            color: var(--primary-color);
            width: 16px;
        }

        .views-info {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            text-align: center;
            border-right: 4px solid var(--warning-color);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .views-info.expired {
            background: #f8d7da;
            border-right: 4px solid var(--danger-color);
        }

        .views-info i {
            font-size: 1.2rem;
        }

        /* حاوية الفيديو المحسنة */
        .video-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
            position: relative;
            width: 100%;
        }

        .video-wrapper {
            width: 100%;
            position: relative;
            background: #000;
        }

        /* تحسينات متجاوبة للفيديو */
        .responsive-video-container {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 56.25%; /* نسبة 16:9 */
            overflow: hidden;
        }

        .responsive-video-container iframe,
        .responsive-video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }

        .video-placeholder {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, #333, #555);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .video-placeholder i {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .video-controls {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            padding: 20px;
            color: white;
            display: none;
        }

        .video-container:hover .video-controls {
            display: block;
        }

        /* معلومات المحاضرة */
        .lecture-info {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
        }

        .lecture-info h2 {
            margin-bottom: 20px;
            color: var(--dark-text);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .lecture-info h2 i {
            color: var(--primary-color);
        }

        .lecture-description {
            color: var(--gray-text);
            font-size: 1.05rem;
            line-height: 1.7;
            margin-bottom: 25px;
        }

        /* المرفقات */
        .attachments-section h3 {
            margin-bottom: 15px;
            color: var(--dark-text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .attachments {
            list-style: none;
            padding: 0;
        }

        .attachment-item {
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .attachment-item:hover {
            border-color: var(--primary-color);
            transform: translateX(-5px);
        }

        .attachment-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .attachment-icon {
            width: 40px;
            height: 40px;
            background: var(--light-bg);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        /* قسم الاختبار */
        .quiz-section {
            background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
            border-right: 4px solid var(--success-color);
        }

        .quiz-section h3 {
            margin-bottom: 10px;
            color: #155724;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1.4rem;
        }

        .quiz-section p {
            color: #155724;
            margin-bottom: 20px;
            font-size: 1.05rem;
        }

        /* الأزرار المحسنة */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            min-width: 120px;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-large {
            padding: 15px 30px;
            font-size: 1.1rem;
        }

        /* العد التنازلي */
        .countdown {
            font-size: 0.9rem;
            color: var(--gray-text);
            margin-top: 8px;
            font-weight: 500;
        }

        /* صفحة الوصول المرفوض */
        .access-denied-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--light-bg);
            padding: 20px;
        }

        .access-denied-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .access-icon {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .access-denied-card h3 {
            margin-bottom: 15px;
            color: var(--dark-text);
            font-size: 1.5rem;
        }

        .access-denied-card p {
            color: var(--gray-text);
            margin-bottom: 25px;
            font-size: 1.1rem;
        }

        /* التجاوب مع الشاشات الصغيرة */
        @media (max-width: 768px) {
            .lecture-container {
                padding: 15px;
            }

            .lecture-header {
                padding: 20px;
            }

            .lecture-header h1 {
                font-size: 1.4rem;
            }

            .lecture-meta {
                flex-direction: column;
                gap: 10px;
            }

            .meta-item {
                justify-content: center;
            }

            .video-container {
                border-radius: 10px;
            }

            .lecture-info {
                padding: 20px;
            }

            .attachment-item {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .attachment-info {
                justify-content: center;
            }

            .quiz-section {
                padding: 20px;
            }

            .quiz-section h3 {
                font-size: 1.2rem;
            }

            .btn {
                width: 100%;
                margin-bottom: 10px;
            }

            .views-info {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .lecture-container {
                padding: 10px;
            }

            .lecture-header {
                padding: 15px;
            }

            .lecture-header h1 {
                font-size: 1.2rem;
            }

            .lecture-info {
                padding: 15px;
            }

            .access-denied-card {
                padding: 25px;
            }

            .access-icon {
                font-size: 3rem;
            }
        }

        /* تحسينات للعرض على الشاشات الكبيرة */
        @media (min-width: 1200px) {
            .lecture-header h1 {
                font-size: 2rem;
            }
        }

        /* تأثيرات تحميل سلسة */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* شريط التقدم */
        .progress-bar {
            width: 100%;
            height: 4px;
            background: var(--border-color);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary-color);
            width: 0%;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="lecture-container fade-in">
        <!-- رأس المحاضرة -->
        <div class="lecture-header">
            <h1><?= htmlspecialchars($lecture['title']) ?></h1>
            
            <div class="lecture-meta">
                <div class="meta-item">
                    <i class="fas fa-list"></i>
                    <span>القائمة: <?= htmlspecialchars($lecture['playlist_name']) ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-user-graduate"></i>
                    <span>الصف: <?= get_grade_text($lecture['grade']) ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-tag"></i>
                    <span>النوع: <?= $lecture['is_free'] ? 'مجاني' : 'مدفوع' ?></span>
                </div>
            </div>
            
            <?php if ($user['type'] === 'student' && !$lecture['is_free']): ?>
                <div class="views-info <?= $remaining_views <= 0 ? 'expired' : '' ?>" id="views-info">
                    <i class="fas fa-eye"></i>
                    <span>المشاهدات المتبقية: <strong id="remaining-views"><?= $remaining_views ?></strong> مشاهدة</span>
                    <div class="countdown" id="countdown"></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- حاوية الفيديو -->
        <div class="video-container">
            <div class="video-wrapper">
                <?php if ($lecture['media_type'] === 'bunny'): ?>
                    <div class="responsive-video-container">
                        <iframe src="https://iframe.mediadelivery.net/embed/<?= htmlspecialchars($lecture['bunny_library_id']) ?>/<?= htmlspecialchars($lecture['media_source']) ?>?autoplay=false" 
                                loading="lazy" 
                                allow="accelerometer;gyroscope;autoplay;encrypted-media;picture-in-picture;" 
                                allowfullscreen="true"></iframe>
                    </div>
                <?php elseif ($lecture['media_type'] === 'youtube'): ?>
                    <div class="responsive-video-container">
                        <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($lecture['media_source']) ?>?rel=0" 
                                allowfullscreen></iframe>
                    </div>
                <?php elseif ($lecture['media_type'] === 'gdrive'): ?>
                    <div class="responsive-video-container">
                        <iframe src="https://drive.google.com/file/d/<?= htmlspecialchars($lecture['media_source']) ?>/preview" 
                                allowfullscreen></iframe>
                    </div>
                <?php elseif ($lecture['media_type'] === 'peertube'): ?>
                    <div class="responsive-video-container">
                        <iframe src="<?= htmlspecialchars($lecture['media_source']) ?>" 
                                allowfullscreen></iframe>
                    </div>
                <?php elseif ($lecture['media_type'] === 'local'): ?>
                    <div class="responsive-video-container">
                        <video controls id="local-video">
                            <source src="<?= htmlspecialchars($lecture['media_source']) ?>" type="video/mp4">
                            متصفحك لا يدعم تشغيل الفيديو.
                        </video>
                    </div>
                <?php elseif ($lecture['media_type'] === 'embed'): ?>
                    <div class="responsive-video-container">
                        <?= $lecture['media_source'] ?>
                    </div>
                <?php else: ?>
                    <div class="video-placeholder">
                        <div style="text-align: center;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>نوع الفيديو غير معروف</p>
                            <small>تعذر تحميل محتوى الفيديو. يرجى التواصل مع المسؤول.</small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- معلومات ووصف المحاضرة -->
        <div class="lecture-info">
            <h2><i class="fas fa-info-circle"></i> وصف المحاضرة</h2>
            <div class="lecture-description">
                <?= nl2br(htmlspecialchars($lecture['description'] ?: 'لا يوجد وصف للمحاضرة')) ?>
            </div>
            
            <?php if (!empty($attachments)): ?>
                <div class="attachments-section">
                    <h3><i class="fas fa-paperclip"></i> المرفقات</h3>
                    <ul class="attachments">
                        <?php foreach ($attachments as $attachment): ?>
                            <li class="attachment-item">
                                <div class="attachment-info">
                                    <div class="attachment-icon">
                                        <i class="fas fa-file-download"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($attachment['file_name']) ?></div>
                                        <small style="color: var(--gray-text);"><?= round($attachment['file_size'] / 1024, 1) ?> KB</small>
                                    </div>
                                </div>
                                <a href="<?= htmlspecialchars($attachment['file_path']) ?>" target="_blank" class="btn btn-primary" download>
                                    <i class="fas fa-download"></i> تحميل
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <!-- قسم الاختبار -->
        <?php if ($user['type'] === 'student'): ?>
            <div class="quiz-section">
                <?php
                $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE lecture_id = ? AND is_active = 1");
                $stmt->execute([$lecture_id]);
                $quiz = $stmt->fetch();
                
                if ($quiz): ?>
                    <h3><i class="fas fa-edit"></i> اختبر فهمك للمحاضرة</h3>
                    <p>اختبر معلوماتك من خلال حل هذا الاختبار.</p>
                    <a href="take_quiz.php?lecture_id=<?= $lecture_id ?>" class="btn btn-success btn-large">
                        <i class="fas fa-play-circle"></i> بدء الاختبار
                    </a>
                <?php else: ?>
                    <p>لا يوجد اختبار متاح لهذه المحاضرة حالياً.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- أزرار التنقل -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="<?= $user['type'] === 'admin' ? 'admin/index.php' : 'student/index.php' ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> العودة إلى القائمة الرئيسية
            </a>
        </div>
    </div>

    <script>
        // متغيرات التحكم في العد
        let viewCounted = false;
        let countdownActive = false;
        let countdownSeconds = 15;
        let countdownInterval;

        // دالة لعرض العد التنازلي
        function startCountdown() {
            if (countdownActive) return;
            
            countdownActive = true;
            const countdownElement = document.getElementById('countdown');
            if (!countdownElement) return;
            
            countdownElement.textContent = `سيتم احتساب مشاهدة بعد ${countdownSeconds} ثانية...`;
            
            countdownInterval = setInterval(() => {
                countdownSeconds--;
                countdownElement.textContent = `سيتم احتساب مشاهدة بعد ${countdownSeconds} ثانية...`;
                
                if (countdownSeconds <= 0) {
                    clearInterval(countdownInterval);
                    countView();
                    countdownElement.textContent = 'تم احتساب المشاهدة';
                }
            }, 1000);
        }

        // دالة لإيقاف العد التنازلي
        function stopCountdown() {
            if (countdownInterval) {
                clearInterval(countdownInterval);
                countdownActive = false;
            }
            const countdownElement = document.getElementById('countdown');
            if (countdownElement) {
                countdownElement.textContent = '';
            }
        }

        // دالة لتسجيل المشاهدة عبر AJAX (مرة واحدة فقط)
        function countView() {
            <?php if ($user['type'] === 'student' && !$lecture['is_free'] && $remaining_views > 0): ?>
            
            if (viewCounted) {
                console.log('تم احتساب المشاهدة مسبقاً');
                return;
            }
            
            viewCounted = true;
            stopCountdown();
            
            fetch('count_view.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    lectureId: <?= $lecture_id ?>,
                    csrf: '<?= generate_csrf_token() ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // تحديث عدد المشاهدات المتبقية
                    const remainingElement = document.getElementById('remaining-views');
                    const viewsInfo = document.getElementById('views-info');
                    
                    if (remainingElement) {
                        if (data.remaining === 'unlimited') {
                            remainingElement.textContent = 'غير محدود';
                        } else {
                            remainingElement.textContent = data.remaining;
                            
                            // إذا نفذت المشاهدات، تغيير نمط العرض
                            if (data.remaining <= 0) {
                                if (viewsInfo) {
                                    viewsInfo.classList.add('expired');
                                    viewsInfo.innerHTML = '<i class="fas fa-times-circle"></i><span>تم استنفاذ جميع المشاهدات</span>';
                                }
                            }
                        }
                    }
                    
                    console.log('تم احتساب المشاهدة بنجاح');
                } else {
                    console.error('Error counting view:', data.message);
                    viewCounted = false; // السماح بإعادة المحاولة في حالة الخطأ
                }
            })
            .catch(error => {
                console.error('Error:', error);
                viewCounted = false; // السماح بإعادة المحاولة في حالة الخطأ
            });
            <?php else: ?>
            console.log('لا يحتاج إلى احتساب مشاهدة (محاضرة مجانية أو مشاهدات منتهية)');
            <?php endif; ?>
        }

        // دالة للتحقق من تشغيل الفيديو
        function checkVideoPlayback() {
            // للفيديو المحلي
            const localVideo = document.getElementById('local-video');
            if (localVideo) {
                localVideo.addEventListener('play', function() {
                    if (!viewCounted) {
                        startCountdown();
                    }
                });
                
                localVideo.addEventListener('pause', function() {
                    stopCountdown();
                });
            }
            
            // للفيديوهات المضمنة (iframes)
            const iframes = document.querySelectorAll('iframe');
            iframes.forEach(iframe => {
                iframe.addEventListener('load', function() {
                    // بدء العد التنازلي بعد تحميل iframe
                    setTimeout(() => {
                        if (!viewCounted) {
                            startCountdown();
                        }
                    }, 2000);
                });
            });
            
            // بدء العد التنازلي تلقائياً بعد 3 ثوانٍ (للفيديوهات التلقائية)
            setTimeout(() => {
                if (!viewCounted) {
                    startCountdown();
                }
            }, 3000);
        }

        // تهيئة الصفحة عند التحميل
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($user['type'] === 'student' && !$lecture['is_free'] && $remaining_views > 0): ?>
            // بدء التحقق من تشغيل الفيديو
            checkVideoPlayback();
            <?php endif; ?>
            
            // منع إعادة تحميل الصفحة من احتساب مشاهدة جديدة
            window.addEventListener('beforeunload', function() {
                if (viewCounted) {
                    // تم احتساب المشاهدة مسبقاً، لا داعي لفعل anything
                }
            });
        });

        // منع إعادة إرسال النموذج (double submission)
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
