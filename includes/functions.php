<?php
require_once __DIR__ . '/../config/database.php';

// بدء الجلسة في كل الصفحات التي تستخدم هذا الملف
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// دالة للحصول على اتصال قاعدة البيانات
function get_db_connection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (\PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Error connecting to the database. Please try again later.");
        }
    }
    return $pdo;
}

// دالة لتهيئة الموقع من الإعدادات
function getSiteName() {
    return defined('SITE_NAME') ? SITE_NAME : 'المنصة التعليمية';
}

function getSiteDescription() {
     return 'منصة متكاملة لتعليم الكيمياء';
}


function sanitize_embed_content($content) {
    $content = trim($content);
    $content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);
    return $content;
}


function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (empty($token) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function is_admin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function is_student() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student';
}

function current_user() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
        return [
            'id' => $_SESSION['user_id'],
            'type' => $_SESSION['user_type'],
            'name' => $_SESSION['user_name'] ?? '',
            'session_id' => $_SESSION['session_id'] ?? '',
            'grade' => $_SESSION['user_grade'] ?? null,
        ];
    }
    return null;
}

function redirect($url) {
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
         if (strpos($url, $_SERVER['HTTP_HOST']) === false) {
            $url = 'index.php';
         }
    }
    header("Location: " . $url);
    exit();
}

function sanitize_input($data, $allow_html = false) {
    if (is_array($data)) {
        return array_map(function($item) use ($allow_html) {
            return sanitize_input($item, $allow_html);
        }, $data);
    }
    
    $data = trim($data);
    if (!$allow_html) {
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}


function get_grade_text($grade) {
    $grades = [
        'first_secondary' => 'الأول الثانوي',
        'second_secondary' => 'الثاني الثانوي',
        'third_secondary' => 'الثالث الثانوي',
        'all' => 'جميع الصفوف'
    ];
    return $grades[$grade] ?? 'غير محدد';
}

function get_status_text($status) {
    $statuses = [
        'active' => 'نشط',
        'suspended' => 'موقوف',
        'banned' => 'محظور',
        'inactive' => 'غير نشط',
        'used' => 'مستخدم'
    ];
    return $statuses[$status] ?? 'غير معروف';
}

function register_user_session($user_id, $user_type) {
    $pdo = get_db_connection();
    $session_id = session_id();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $stmt_invalidate = $pdo->prepare("UPDATE user_sessions SET is_active = 0 WHERE user_id = ? AND user_type = ?");
    $stmt_invalidate->execute([$user_id, $user_type]);
    
    $stmt_insert = $pdo->prepare("INSERT INTO user_sessions (user_id, user_type, session_id, ip_address, user_agent, is_active, login_time, last_activity) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())");
    $stmt_insert->execute([$user_id, $user_type, $session_id, $ip_address, $user_agent]);
    
    return $session_id;
}

function update_user_activity() {
    $user = current_user();
    if (!$user) return;
    $pdo = get_db_connection();
    
    // تحديث user_sessions
    $stmt = $pdo->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE session_id = ? AND is_active = 1");
    $stmt->execute([$user['session_id']]);

    // تحديث students table
    if($user['type'] === 'student') {
        $stmt_student = $pdo->prepare("UPDATE students SET last_seen = NOW() WHERE id = ?");
        $stmt_student->execute([$user['id']]);
    }
}

function validate_session() {
    $user = current_user();
    if (!$user || !isset($user['session_id'])) {
        return false;
    }
    
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT 1 FROM user_sessions WHERE user_id = ? AND user_type = ? AND session_id = ? AND is_active = 1");
    $stmt->execute([$user['id'], $user['type'], $user['session_id']]);

    return $stmt->fetchColumn() ? true : false;
}


// --- *** FINAL FIX FOR LIVE SERVER *** ---
function get_online_users() {
    $pdo = get_db_connection();
    if (!$pdo) {
        return [];
    }
    try {
        $five_minutes_ago = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        
        // This is a more robust way to handle UNION on servers with strict collation settings.
        // We cast the columns to a common character set.
        $sql = "
            SELECT 
                us.id, us.user_id, us.user_type, us.session_id, us.login_time, us.last_activity, us.ip_address, us.user_agent,
                CAST(s.name AS CHAR CHARACTER SET utf8mb4) AS user_name 
            FROM user_sessions us
            JOIN students s ON us.user_id = s.id
            WHERE us.user_type = 'student' AND us.last_activity >= ? AND us.is_active = 1
            
            UNION ALL 
            
            SELECT 
                us.id, us.user_id, us.user_type, us.session_id, us.login_time, us.last_activity, us.ip_address, us.user_agent,
                CAST(a.username AS CHAR CHARACTER SET utf8mb4) AS user_name
            FROM user_sessions us
            JOIN admins a ON us.user_id = a.id
            WHERE us.user_type = 'admin' AND us.last_activity >= ? AND us.is_active = 1
            
            ORDER BY last_activity DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$five_minutes_ago, $five_minutes_ago]); 
        return $stmt->fetchAll();

    } catch (PDOException $e) {
        // If it fails on a live server, return an empty array and log the error
        // instead of crashing the admin dashboard.
        error_log("get_online_users() failed: " . $e->getMessage());
        return [];
    }
}
// --- *** END OF FIX *** ---

function time_since($datetime, $full = false) {
    if(!$datetime) return 'أبدًا';
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = ['y' => 'سنة','m' => 'شهر','w' => 'أسبوع','d' => 'يوم','h' => 'ساعة','i' => 'دقيقة','s' => 'ثانية'];
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v;
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? 'منذ ' . implode(', ', $string) : 'الآن';
}


function get_device_from_ua($ua) {
    if (preg_match('/(android)/i', $ua)) return 'Android';
    if (preg_match('/(iphone|ipod|ipad)/i', $ua)) return 'iOS Device';
    if (preg_match('/(windows phone)/i', $ua)) return 'Windows Phone';
    if (preg_match('/(windows|win)/i', $ua)) return 'Windows PC';
    if (preg_match('/(macintosh|mac os)/i', $ua)) return 'Mac';
    if (preg_match('/(linux)/i', $ua)) return 'Linux';
    return 'Unknown';
}


function get_setting($setting_name, $default = null) {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_name = ?");
    $stmt->execute([$setting_name]);
    $result = $stmt->fetchColumn();
    return ($result !== false) ? $result : $default;
}

function set_setting($setting_name, $setting_value) {
    $pdo = get_db_connection();
    $sql = "INSERT INTO settings (setting_name, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$setting_name, $setting_value, $setting_value]);
}