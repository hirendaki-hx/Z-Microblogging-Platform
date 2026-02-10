<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
    $user_id = $_SESSION['user_id'];
    $post_id = intval($_POST['post_id']);
    
    // Check if already liked
    $check_stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $check_stmt->execute([$user_id, $post_id]);
    $existing_like = $check_stmt->fetch();
    
    if ($existing_like) {
        // Unlike
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
        $liked = false;
    } else {
        // Like
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $post_id]);
        $liked = true;
    }
    
    // Get updated like count
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $count_stmt->execute([$post_id]);
    $like_count = $count_stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'like_count' => $like_count
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
?>