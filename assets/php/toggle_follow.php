<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $follower_id = $_SESSION['user_id'];
    $followed_id = intval($_POST['user_id']);
    
    if ($follower_id == $followed_id) {
        echo json_encode(['success' => false, 'error' => 'Cannot follow yourself']);
        exit;
    }
    
    // Check if already following
    $check_stmt = $pdo->prepare("SELECT * FROM follows WHERE follower_id = ? AND followed_id = ?");
    $check_stmt->execute([$follower_id, $followed_id]);
    $existing_follow = $check_stmt->fetch();
    
    if ($existing_follow) {
        // Unfollow
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
        $stmt->execute([$follower_id, $followed_id]);
        $following = false;
    } else {
        // Follow
        $stmt = $pdo->prepare("INSERT INTO follows (follower_id, followed_id) VALUES (?, ?)");
        $stmt->execute([$follower_id, $followed_id]);
        $following = true;
    }
    
    echo json_encode([
        'success' => true,
        'following' => $following
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
?>