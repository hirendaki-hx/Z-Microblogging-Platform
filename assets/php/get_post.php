<?php
// get_post.php - UPDATED VERSION
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$current_user_id = $_SESSION['user_id'];

try {
    // Get ALL posts from ALL users
    $query = "SELECT p.*, 
              u.username, 
              u.firstname,
              u.lastname,
              COALESCE(u.profile_pic, UPPER(SUBSTRING(u.firstname, 1, 1))) as profile_pic,
              (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
              (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
              EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as liked_by_user,
              EXISTS(SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = p.user_id) as following_author
              FROM posts p
              JOIN users u ON p.user_id = u.id
              ORDER BY p.created_at DESC
              LIMIT 100";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$current_user_id, $current_user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'posts' => $posts
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>