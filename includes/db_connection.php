<?php
// تضمين ملف الإعدادات أولاً
require_once __DIR__ . '/../config/database.php';

// إعدادات PDO للاتصال
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // إنشاء كائن PDO جديد للاتصال
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    // في حالة فشل الاتصال، يتم عرض خطأ وإيقاف التنفيذ
    // في بيئة الإنتاج، يجب تسجيل الخطأ بدلاً من عرضه للمستخدم
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}