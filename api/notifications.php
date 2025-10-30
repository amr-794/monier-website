<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!is_student()) {
    http_response_code(403); exit;
}
$student_id = current_user()['id'];
$pdo = get_db_connection();

$action = $_GET['action'] ?? 'fetch';

if ($action === 'fetch') {
    // جلب كل الإشعارات للطالب، ووضع علامة "جديد" عليها
    $sql_populate = "
        INSERT INTO student_notifications (student_id, announcement_id)
        SELECT ?, id FROM announcements WHERE is_notification = 1
        ON DUPLICATE KEY UPDATE student_id = student_id
    ";
    $pdo->prepare($sql_populate)->execute([$student_id]);
    
    // جلب الإشعارات
    $stmt = $pdo->prepare("
        SELECT a.id as announcement_id, a.title, a.content, a.created_at, sn.is_read
        FROM announcements a
        JOIN student_notifications sn ON a.id = sn.announcement_id
        WHERE sn.student_id = ?
        ORDER BY a.created_at DESC LIMIT 15
    ");
    $stmt->execute([$student_id]);
    $notifications = $stmt->fetchAll();
    
    $unread_count = $pdo->prepare("SELECT COUNT(*) FROM student_notifications WHERE student_id = ? AND is_read = 0");
    $unread_count->execute([$student_id]);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unread_count->fetchColumn()
    ]);

} elseif ($action === 'mark_as_read') {
    $id = intval($_GET['id'] ?? 0);
    if($id > 0) {
         $stmt = $pdo->prepare("UPDATE student_notifications SET is_read = 1, read_at = NOW() WHERE student_id = ? AND announcement_id = ?");
         $stmt->execute([$student_id, $id]);
         echo json_encode(['success' => true]);
    } else {
         echo json_encode(['success' => false]);
    }
}