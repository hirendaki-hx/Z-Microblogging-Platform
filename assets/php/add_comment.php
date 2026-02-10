<?php
session_start();
require_once 'conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id']) && isset($_POST['comment'])) {
    $user_id = $_SESSION['user_id'];
    $post_id = intval($_POST['post_id']);
    $comment = trim($_POST['comment']);
    
    if (empty($comment)) {
        echo json_encode(['success' => false, 'error' => 'Comment cannot be empty']);
        exit;
    }
    
    if (strlen($comment) > 500) {
        echo json_encode(['success' => false, 'error' => 'Comment too long']);
        exit;
    }
    
    try {
        // Insert comment
        $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, body) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $post_id, $comment]);
        
        // Get updated comment count
        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
        $count_stmt->execute([$post_id]);
        $comment_count = $count_stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'comment_count' => $comment_count
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
?>