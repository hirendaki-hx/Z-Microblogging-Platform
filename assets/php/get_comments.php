<?php
session_start();
require_once 'conn.php';

if (!isset($_GET['post_id'])) {
    echo '<div class="empty-state">Post ID required</div>';
    exit;
}

$post_id = intval($_GET['post_id']);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Get post info
$post_stmt = $pdo->prepare("
    SELECT p.*, u.username, u.profile_pic 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.id = ?
");
$post_stmt->execute([$post_id]);
$post = $post_stmt->fetch();

if (!$post) {
    echo '<div class="empty-state">Post not found</div>';
    exit;
}
?>

<div class="modal-post">
    <div class="post-header">
        <img src="../assets/uploads/<?php echo htmlspecialchars($post['profile_pic']); ?>" alt="Profile" class="profile-pic">
        <div class="post-user-info">
            <span class="post-username"><?php echo htmlspecialchars($post['username']); ?></span>
        </div>
    </div>
    
    <div class="post-content">
        <div class="post-caption"><?php echo nl2br(htmlspecialchars($post['caption'])); ?></div>
        <?php if ($post['image_url']): ?>
            <img src="../<?php echo htmlspecialchars($post['image_url']); ?>" alt="Post image" class="post-image">
        <?php endif; ?>
    </div>
</div>

<div class="modal-comments">
    <?php
    // Get comments
    $comments_stmt = $pdo->prepare("
        SELECT c.*, u.username, u.profile_pic 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.post_id = ? 
        ORDER BY c.created_at DESC
    ");
    $comments_stmt->execute([$post_id]);
    $comments = $comments_stmt->fetchAll();
    
    if (empty($comments)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="far fa-comment"></i></div>
            <h3>No comments yet</h3>
            <p>Be the first to comment!</p>
        </div>
    <?php else: ?>
        <?php foreach ($comments as $comment): ?>
            <div class="comment-item">
                <img src="../assets/uploads/<?php echo htmlspecialchars($comment['profile_pic']); ?>" alt="Profile" class="profile-pic">
                <div class="comment-content">
                    <div class="comment-header">
                        <span class="comment-username"><?php echo htmlspecialchars($comment['username']); ?></span>
                        <span class="comment-time">· <?php echo time_elapsed_string($comment['created_at']); ?></span>
                    </div>
                    <div class="comment-body"><?php echo nl2br(htmlspecialchars($comment['body'])); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="add-comment">
    <?php if ($user_id): ?>
        <img src="../assets/uploads/<?php 
            $user_stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
            $user_stmt->execute([$user_id]);
            $user = $user_stmt->fetch();
            echo htmlspecialchars($user['profile_pic']);
        ?>" alt="Profile" class="profile-pic">
        <form id="comment-form-<?php echo $post_id; ?>" onsubmit="event.preventDefault(); addComment(<?php echo $post_id; ?>);">
            <textarea placeholder="Tweet your reply" maxlength="500"></textarea>
            <button type="submit" class="comment-btn">Reply</button>
        </form>
    <?php else: ?>
        <p>Please <a href="../public/Signin.php">sign in</a> to comment.</p>
    <?php endif; ?>
</div>

<?php
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>