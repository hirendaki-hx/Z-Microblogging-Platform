<?php
session_start();
require_once 'conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if (!isset($_POST['post_id']) || !isset($_POST['caption'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

$post_id = intval($_POST['post_id']);
$user_id = $_SESSION['user_id'];
$caption = trim($_POST['caption']);

// Validate caption
if (empty($caption) || strlen($caption) > 280) {
    echo json_encode(['success' => false, 'error' => 'Caption must be 1-280 characters']);
    exit;
}

// Check if post exists and belongs to user
$check_stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
$check_stmt->execute([$post_id, $user_id]);
$post = $check_stmt->fetch();

if (!$post) {
    echo json_encode(['success' => false, 'error' => 'Post not found or unauthorized']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Handle image updates
    $image_url = $post['image_url'];
    
    // Remove image if requested
    if (isset($_POST['remove_image']) && $_POST['remove_image'] === 'true') {
        if ($image_url && file_exists('../' . $image_url)) {
            unlink('../' . $image_url);
        }
        $image_url = '';
    }
    
    // Replace with new image
    if (isset($_POST['replace_image']) && $_POST['replace_image'] === 'true' && isset($_FILES['image'])) {
        // Remove old image if exists
        if ($image_url && file_exists('../' . $image_url)) {
            unlink('../' . $image_url);
        }
        
        // Upload new image
        $upload_dir = '../assets/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $file_path = $upload_dir . $file_name;
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                $image_url = 'assets/uploads/' . $file_name;
            }
        }
    }
    
    // Update post
    $update_stmt = $pdo->prepare("UPDATE posts SET caption = ?, image_url = ? WHERE id = ? AND user_id = ?");
    $update_stmt->execute([$caption, $image_url, $post_id, $user_id]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>