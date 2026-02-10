<?php
session_start();
require_once 'conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if (!isset($_POST['post_id'])) {
    echo json_encode(['success' => false, 'error' => 'Post ID required']);
    exit;
}

$post_id = intval($_POST['post_id']);
$user_id = $_SESSION['user_id'];

try {
    // Check if post exists and belongs to user
    $check_stmt = $pdo->prepare("SELECT image_url FROM posts WHERE id = ? AND user_id = ?");
    $check_stmt->execute([$post_id, $user_id]);
    $post = $check_stmt->fetch();
    
    if (!$post) {
        echo json_encode(['success' => false, 'error' => 'Post not found or unauthorized']);
        exit;
    }
    
    // Delete image file if exists
    if ($post['image_url'] && file_exists('../' . $post['image_url'])) {
        unlink('../' . $post['image_url']);
    }
    
    // Delete post from database (cascade will delete likes and comments)
    $delete_stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $delete_stmt->execute([$post_id, $user_id]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>