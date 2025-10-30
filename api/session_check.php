<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

// هذا الملف يتم استدعاؤه بشكل دوري للتحقق من صلاحية الجلسة
$is_valid = validate_session();

echo json_encode(['valid' => $is_valid]);