<?php
session_start();
require_once '../assets/php/conn.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: Signin.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get current user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

if (!$current_user) {
    session_destroy();
    header('Location: Signin.php');
    exit;
}

// Ensure profile_pic has a value
if (empty($current_user['profile_pic'])) {
    $first_letter = strtoupper(substr($current_user['firstname'], 0, 1));
    $update_stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
    $update_stmt->execute([$first_letter, $user_id]);
    $current_user['profile_pic'] = $first_letter;
}

// Get followers/following counts
$followers_stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE followed_id = ?");
$followers_stmt->execute([$user_id]);
$followers_count = $followers_stmt->fetchColumn() ?: 0;

$following_stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
$following_stmt->execute([$user_id]);
$following_count = $following_stmt->fetchColumn() ?: 0;

// Get suggestions for who to follow
$suggestions_stmt = $pdo->prepare("
    SELECT u.*,
           COALESCE(u.profile_pic, UPPER(SUBSTRING(u.firstname, 1, 1))) as profile_pic
    FROM users u
    WHERE u.id != ? 
      AND u.id NOT IN (SELECT followed_id FROM follows WHERE follower_id = ?)
    ORDER BY RAND()
    LIMIT 3
");
$suggestions_stmt->execute([$user_id, $user_id]);
$suggestions = $suggestions_stmt->fetchAll();

// Handle post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    $caption = trim($_POST['caption'] ?? '');
    $image_url = '';
    
    // Validate caption
    if (empty($caption) || strlen($caption) > 280) {
        $_SESSION['error'] = "Caption must be between 1 and 280 characters.";
    } else {
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
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
        
        // Insert post
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, caption, image_url) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $caption, $image_url]);
        
        header('Location: Dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home / Z</title>
    <link rel="stylesheet" href="../assets/css/Dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Left Sidebar -->
        <div class="sidebar">
            <img src="../assets/icons/Z.png" alt="Z Logo" class="logo" onclick="window.location.href='Dashboard.php'">
            
            <ul class="nav-menu">
                <li><a href="Dashboard.php" class="nav-link active"><i class="fas fa-home nav-icon"></i> <span>Home</span></a></li>
                <li><a href="#" class="nav-link"><i class="fas fa-search nav-icon"></i> <span>Explore</span></a></li>
                <li><a href="#" class="nav-link"><i class="fas fa-bell nav-icon"></i> <span>Notifications</span></a></li>
                <li><a href="#" class="nav-link"><i class="fas fa-envelope nav-icon"></i> <span>Messages</span></a></li>
                <li><a href="#" class="nav-link"><i class="fas fa-bookmark nav-icon"></i> <span>Bookmarks</span></a></li>
                <li><a href="Profile.php" class="nav-link"><i class="fas fa-user nav-icon"></i> <span>Profile</span></a></li>
            </ul>
            
            <button class="post-btn" onclick="openTweetModal()"><span>Tweet</span> <i class="fas fa-feather-alt"></i></button>
            
            <div class="user-profile" onclick="window.location.href='Profile.php?id=<?php echo $user_id; ?>'">
                <div class="profile-pic" data-letter="<?php echo htmlspecialchars($current_user['profile_pic']); ?>">
                    <?php echo htmlspecialchars($current_user['profile_pic']); ?>
                </div>
                <div class="user-info">
                    <div class="username"><?php echo htmlspecialchars($current_user['firstname'] . ' ' . $current_user['lastname']); ?></div>
                    <div class="handle">@<?php echo htmlspecialchars($current_user['username']); ?></div>
                </div>
                <div class="more-btn"><i class="fas fa-ellipsis-h"></i></div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Home</h1>
                <button id="refresh-feed-btn" style="background: transparent; border: none; color: #1d9bf0; cursor: pointer; margin-left: 10px; font-size: 18px;" title="Refresh feed">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            
            <!-- Create Post -->
            <div class="create-post">
                <div class="profile-pic" data-letter="<?php echo htmlspecialchars($current_user['profile_pic']); ?>">
                    <?php echo htmlspecialchars($current_user['profile_pic']); ?>
                </div>
                <div class="post-input">
                    <form id="post-form" method="POST" enctype="multipart/form-data">
                        <textarea name="caption" placeholder="What's happening?" maxlength="280" oninput="updateCharCount(this)"></textarea>
                        <div class="char-count" id="char-count">280</div>
                        
                        <div class="post-actions">
                            <div class="media-icons">
                                <label for="image-upload" class="media-icon" title="Add image">
                                    <i class="fas fa-image"></i>
                                </label>
                                <input type="file" id="image-upload" name="image" accept="image/*" style="display:none" onchange="previewImage(this)">
                                <div class="media-icon" title="Add GIF" onclick="showGifPicker()">
                                    <i class="fas fa-file-image"></i>
                                </div>
                                <div class="media-icon" title="Add poll" onclick="showPollCreator()">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <div class="media-icon" title="Add emoji" onclick="showEmojiPicker()">
                                    <i class="far fa-smile"></i>
                                </div>
                                <div class="media-icon" title="Schedule" onclick="showScheduleOptions()">
                                    <i class="far fa-calendar-alt"></i>
                                </div>
                                <div class="media-icon" title="Add location" onclick="addLocation()">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                            </div>
                            <button type="submit" name="create_post" class="submit-post-btn" id="submit-post">Tweet</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Posts Feed -->
            <div class="posts-feed" id="posts-feed">
                <div class="loading-posts">
                    <div class="spinner"></div>
                    <p>Loading posts...</p>
                </div>
            </div>
        </div>
        
        <!-- Right Sidebar -->
        <div class="right-sidebar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search Z" id="search-input">
            </div>
            
            <?php if (!empty($suggestions)): ?>
            <div class="who-to-follow">
                <h3 class="section-title">Who to follow</h3>
                <?php foreach ($suggestions as $user): ?>
                    <div class="user-to-follow" data-user-id="<?php echo $user['id']; ?>">
                        <div class="follow-user">
                            <div class="profile-pic" data-letter="<?php echo htmlspecialchars($user['profile_pic']); ?>">
                                <?php echo htmlspecialchars($user['profile_pic']); ?>
                            </div>
                            <div class="follow-user-info">
                                <div class="username"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></div>
                                <div class="handle">@<?php echo htmlspecialchars($user['username']); ?></div>
                            </div>
                            <button class="follow-btn" onclick="toggleFollow(<?php echo $user['id']; ?>)">Follow</button>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="show-more">
                    <a href="#" style="color: #1d9bf0; text-decoration: none; font-size: 14px;">Show more</a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Footer -->
            <div class="right-sidebar-footer">
                <a href="#">Terms of Service</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Cookie Policy</a>
                <a href="#">Accessibility</a>
                <a href="#">Ads info</a>
                <a href="#">More</a>
                <div style="margin-top: 10px; color: #6e767d; font-size: 12px;">
                    © 2024 Z Corp.
                </div>
            </div>
        </div>
        
        <!-- Mobile Navigation -->
        <div class="mobile-nav">
            <ul class="mobile-nav-items">
                <li><a href="Dashboard.php" class="mobile-nav-link active"><i class="fas fa-home"></i></a></li>
                <li><a href="#" class="mobile-nav-link"><i class="fas fa-search"></i></a></li>
                <li><a href="#" class="mobile-nav-link"><i class="fas fa-bell"></i></a></li>
                <li><a href="#" class="mobile-nav-link"><i class="fas fa-envelope"></i></a></li>
                <li><a href="Profile.php" class="mobile-nav-link"><i class="fas fa-user"></i></a></li>
            </ul>
        </div>
        
        <button class="mobile-post-btn" onclick="openTweetModal()"><i class="fas fa-feather-alt"></i></button>
    </div>
    
    <!-- ========== MODALS ========== -->
    
    <!-- Comment Modal -->
    <div class="modal-overlay" id="comment-modal" style="display: none;">
        <div class="comment-modal">
            <div class="modal-header">
                <button class="close-modal" onclick="closeComments()"><i class="fas fa-times"></i></button>
                <h3>Comments</h3>
            </div>
            <div class="modal-content" id="modal-content">
                <!-- Comments loaded here -->
            </div>
        </div>
    </div>
    
    <!-- Tweet Modal -->
    <div class="modal-overlay" id="tweet-modal" style="display: none;">
        <div class="tweet-modal">
            <div class="tweet-modal-header">
                <button class="close-modal" onclick="closeTweetModal()"><i class="fas fa-times"></i></button>
                <h3 style="margin: 0; font-size: 18px;">Create Post</h3>
                <div style="width: 35px;"></div>
            </div>
            <div class="tweet-modal-content">
                <div class="create-post" style="border: none; padding: 0;">
                    <div class="profile-pic" data-letter="<?php echo htmlspecialchars($current_user['profile_pic']); ?>">
                        <?php echo htmlspecialchars($current_user['profile_pic']); ?>
                    </div>
                    <div class="post-input">
                        <textarea id="modal-caption" placeholder="What's happening?" maxlength="280" 
                                  oninput="updateModalCharCount(this)"></textarea>
                        <div class="char-count" id="modal-char-count">280</div>
                        
                        <div class="image-preview-container" id="modal-image-preview" style="display: none;">
                            <img id="modal-preview-image" class="image-preview" alt="Image preview">
                            <button type="button" class="remove-image-btn" onclick="removeModalImage()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="modal-buttons">
                            <div class="modal-media-icons">
                                <label for="modal-image-upload" class="modal-media-icon" title="Add image">
                                    <i class="fas fa-image"></i>
                                </label>
                                <input type="file" id="modal-image-upload" accept="image/*" style="display: none;" 
                                       onchange="previewModalImage(this)">
                                <div class="modal-media-icon" title="Add GIF" onclick="showGifPicker()">
                                    <i class="fas fa-file-image"></i>
                                </div>
                                <div class="modal-media-icon" title="Add poll" onclick="showPollCreator()">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <div class="modal-media-icon" title="Add emoji" onclick="showEmojiPicker()">
                                    <i class="far fa-smile"></i>
                                </div>
                                <div class="modal-media-icon" title="Schedule" onclick="showScheduleOptions()">
                                    <i class="far fa-calendar-alt"></i>
                                </div>
                                <div class="modal-media-icon" title="Add location" onclick="addLocation()">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                            </div>
                            <button type="button" class="modal-submit-btn" id="modal-submit-post" onclick="submitModalTweet()">
                                Tweet
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Image Preview Modal -->
    <div class="modal-overlay" id="image-preview-modal" style="display: none;">
        <div class="image-preview-modal" style="background: transparent; max-width: 90%; max-height: 90vh;">
            <div class="modal-header" style="background: transparent; border: none; justify-content: flex-end;">
                <button class="close-modal" onclick="closeImagePreview()" style="background: rgba(0,0,0,0.5);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-content" style="display: flex; justify-content: center; align-items: center;">
                <img id="full-size-image" style="max-width: 100%; max-height: 80vh; border-radius: 10px;">
            </div>
        </div>
    </div>
    
    <!-- Edit Post Modal -->
    <div class="modal-overlay" id="edit-post-modal" style="display: none;">
        <div class="edit-post-modal">
            <div class="edit-post-header">
                <button class="close-modal" onclick="closeEditPostModal()"><i class="fas fa-times"></i></button>
                <h3 style="margin: 0; font-size: 18px;">Edit Post</h3>
                <div style="width: 35px;"></div>
            </div>
            <div class="edit-post-content">
                <div class="create-post" style="border: none; padding: 0;">
                    <div class="profile-pic" data-letter="<?php echo htmlspecialchars($current_user['profile_pic']); ?>">
                        <?php echo htmlspecialchars($current_user['profile_pic']); ?>
                    </div>
                    <div class="post-input">
                        <textarea id="edit-caption" placeholder="Edit your post..." maxlength="280" 
                                  oninput="updateEditCharCount(this)"></textarea>
                        <div class="char-count" id="edit-char-count">280</div>
                        
                        <div class="current-image-container" id="current-image-container" style="display: none;">
                            <span class="current-image-label">Current Image:</span>
                            <img id="current-image-preview" class="current-image" alt="Current post image">
                            <button type="button" class="remove-current-image" onclick="removeCurrentImage()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="image-preview-container" id="edit-image-preview" style="display: none;">
                            <span class="current-image-label">New Image:</span>
                            <img id="edit-preview-image" class="image-preview" alt="New image preview">
                            <button type="button" class="remove-image-btn" onclick="removeEditImage()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="edit-post-buttons">
                            <button type="button" class="delete-post-btn" id="edit-delete-btn" onclick="confirmDeletePost()">
                                Delete Post
                            </button>
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <div class="modal-media-icons">
                                    <label for="edit-image-upload" class="modal-media-icon" title="Change image">
                                        <i class="fas fa-image"></i>
                                    </label>
                                    <input type="file" id="edit-image-upload" accept="image/*" style="display: none;" 
                                           onchange="previewEditImage(this)">
                                </div>
                                <button type="button" class="update-post-btn" id="update-post-btn" onclick="updatePost()">
                                    Update
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="delete-confirm-modal" style="display: none;">
        <div class="delete-confirm-modal">
            <div class="delete-confirm-header">
                <h3>Delete Post?</h3>
                <p>This can't be undone and it will be removed from your profile, the timeline of any accounts that follow you, and from Z search results.</p>
            </div>
            <div class="delete-confirm-buttons">
                <button class="cancel-delete-btn" onclick="cancelDelete()">Cancel</button>
                <button class="confirm-delete-btn" id="confirm-delete-btn" onclick="performDelete()">Delete</button>
            </div>
        </div>
    </div>

    <script>
        // Global variable for current user ID
        const CURRENT_USER_ID = <?php echo $user_id; ?>;
    </script>
    
    <style>
        /* Add CSS for loading spinner and notifications */
        .loading-posts {
            text-align: center;
            padding: 40px;
            color: #6e767d;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #1d9bf0;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            background: #1d9bf0;
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            animation: slideIn 0.3s ease;
            max-width: 350px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        
        .notification.error {
            background: #e0245e;
        }
        
        .notification.success {
            background: #17bf63;
        }
        
        .notification.info {
            background: #1d9bf0;
        }
        
        .notification.fade-out {
            animation: slideOut 0.3s ease forwards;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        /* Post refresh animation */
        .post-card.new-post {
            animation: highlightPost 2s ease;
        }
        
        @keyframes highlightPost {
            0% { background-color: rgba(29, 155, 240, 0.1); }
            100% { background-color: transparent; }
        }
    </style>
    
    <script src="../assets/js/Dashboard.js"></script>
    
    <script>
    // ========== FEED MANAGEMENT FUNCTIONS ==========
    
    let isFeedLoading = false;
    
    // Load posts on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Dashboard loaded, fetching posts...');
        
        // Load posts immediately
        loadPosts();
        
        // Setup refresh button
        const refreshBtn = document.getElementById('refresh-feed-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function(e) {
                e.preventDefault();
                loadPosts();
                // Add spin animation
                this.querySelector('i').style.transform = 'rotate(360deg)';
                this.querySelector('i').style.transition = 'transform 0.5s ease';
                setTimeout(() => {
                    this.querySelector('i').style.transform = 'rotate(0deg)';
                }, 500);
            });
        }
        
        // Auto-refresh every 60 seconds
        setInterval(loadPosts, 60000);
        
        // Handle main form submission
        const mainPostForm = document.getElementById('post-form');
        if (mainPostForm) {
            mainPostForm.addEventListener('submit', function(e) {
                const textarea = this.querySelector('textarea');
                if (!textarea.value.trim() || textarea.value.length > 280) {
                    e.preventDefault();
                    showNotification('Please enter a valid caption (1-280 characters)', 'error');
                }
            });
        }
    });
    
    function loadPosts() {
        if (isFeedLoading) {
            console.log('Feed is already loading, skipping...');
            return;
        }
        
        console.log('Loading posts from server...');
        isFeedLoading = true;
        const feed = document.getElementById('posts-feed');
        if (!feed) {
            isFeedLoading = false;
            return;
        }
        
        // Show loading state
        feed.innerHTML = '<div class="loading-posts"><div class="spinner"></div><p>Loading posts...</p></div>';
        
        // Add timestamp to prevent caching
        const timestamp = new Date().getTime();
        
        fetch('../assets/php/get_post.php?t=' + timestamp)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Posts loaded:', data);
                isFeedLoading = false;
                if (data.success) {
                    displayPosts(data.posts);
                } else {
                    console.error('Server error:', data.error);
                    feed.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-exclamation-circle"></i></div>
                            <h3>Error loading posts</h3>
                            <p>${data.error || 'Server error'}</p>
                            <button onclick="loadPosts()" style="margin-top: 15px; padding: 8px 16px; background: #1d9bf0; color: white; border: none; border-radius: 20px; cursor: pointer;">
                                Try Again
                            </button>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                isFeedLoading = false;
                feed.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-exclamation-circle"></i></div>
                        <h3>Network error</h3>
                        <p>Please check your connection and try again</p>
                        <button onclick="loadPosts()" style="margin-top: 15px; padding: 8px 16px; background: #1d9bf0; color: white; border: none; border-radius: 20px; cursor: pointer;">
                            Try Again
                        </button>
                    </div>
                `;
            });
    }
    
    function displayPosts(posts) {
        const feed = document.getElementById('posts-feed');
        if (!feed) return;
        
        console.log('Displaying', posts ? posts.length : 0, 'posts');
        
        if (!posts || posts.length === 0) {
            feed.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-feather"></i></div>
                    <h3>No posts yet</h3>
                    <p>Be the first to post something!</p>
                    <button class="btn" onclick="openTweetModal()" style="margin-top: 20px; width: auto; padding: 10px 20px; background: #1d9bf0; border: none; border-radius: 30px; color: white; font-weight: bold; cursor: pointer;">
                        Create Your First Post
                    </button>
                </div>
            `;
            return;
        }
        
        let html = '';
        posts.forEach(post => {
            html += `
                <div class="post-card" data-post-id="${post.id}">
                    <div class="post-header">
                        <div class="profile-pic" data-letter="${escapeHtml(post.profile_pic || 'U')}">
                            ${escapeHtml(post.profile_pic || 'U')}
                        </div>
                        <div class="post-user-info">
                            <span class="post-username">${escapeHtml(post.firstname + ' ' + post.lastname)}</span>
                            <span class="post-handle">@${escapeHtml(post.username)}</span>
                            <span class="post-time">· ${formatTime(post.created_at)}</span>
                        </div>
                        <div class="post-menu-container">
                            <div class="post-menu" onclick="togglePostMenu(${post.id}, ${post.user_id})">
                                <i class="fas fa-ellipsis-h"></i>
                            </div>
                            <div class="post-menu-dropdown" id="post-menu-${post.id}">
                                ${post.user_id == CURRENT_USER_ID ? 
                                    `<div class="post-menu-item" onclick="editPost(${post.id})">
                                        <i class="far fa-edit"></i> Edit Post
                                    </div>
                                    <div class="post-menu-item delete" onclick="deletePost(${post.id})">
                                        <i class="far fa-trash-alt"></i> Delete
                                    </div>` : 
                                    `<div class="post-menu-item" onclick="reportPost(${post.id})">
                                        <i class="far fa-flag"></i> Report Post
                                    </div>
                                    <div class="post-menu-item" onclick="muteUser(${post.user_id})">
                                        <i class="fas fa-volume-mute"></i> Mute @${escapeHtml(post.username)}
                                    </div>
                                    <div class="post-menu-item" onclick="blockUser(${post.user_id})">
                                        <i class="fas fa-ban"></i> Block @${escapeHtml(post.username)}
                                    </div>`
                                }
                            </div>
                        </div>
                    </div>
                    
                    <div class="post-content">
                        <div class="post-caption" id="post-caption-${post.id}">${escapeHtml(post.caption || '').replace(/\n/g, '<br>')}</div>
                        ${post.image_url ? 
                            `<img src="../${escapeHtml(post.image_url)}" 
                                 alt="Post image" 
                                 class="post-image-preview"
                                 onclick="openImagePreview('../${escapeHtml(post.image_url)}')">` : 
                            ''
                        }
                    </div>
                    
                    <div class="post-stats">
                        <div class="post-stat comment-btn" onclick="openComments(${post.id})">
                            <i class="far fa-comment"></i>
                            <span class="stat-count" id="comment-count-${post.id}">${post.comment_count || 0}</span>
                        </div>
                        <div class="post-stat like-btn ${post.liked_by_user ? 'liked' : ''}" onclick="toggleLike(${post.id})">
                            <i class="${post.liked_by_user ? 'fas' : 'far'} fa-heart"></i>
                            <span class="stat-count" id="like-count-${post.id}">${post.like_count || 0}</span>
                        </div>
                        <div class="post-stat">
                            <i class="far fa-share-square"></i>
                        </div>
                        <div class="post-stat">
                            <i class="far fa-chart-bar"></i>
                        </div>
                    </div>
                </div>
            `;
        });
        
        feed.innerHTML = html;
        
        // Show success notification if posts loaded
        if (posts.length > 0) {
            console.log('Posts displayed successfully');
        }
    }
    
    function formatTime(dateString) {
        if (!dateString) return 'just now';
        
        try {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffMins < 1) return 'just now';
            if (diffMins < 60) return `${diffMins}m ago`;
            if (diffHours < 24) return `${diffHours}h ago`;
            if (diffDays < 7) return `${diffDays}d ago`;
            
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric',
                year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
            });
        } catch (e) {
            return 'recently';
        }
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function showNotification(message, type = 'info') {
        // Remove existing notifications
        document.querySelectorAll('.notification').forEach(n => n.remove());
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 
                            type === 'success' ? 'check-circle' : 'info-circle'}"></i>
            <span>${escapeHtml(message)}</span>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    // Handle post creation success via AJAX
    function submitModalTweet() {
        const caption = document.getElementById('modal-caption')?.value.trim();
        const submitBtn = document.getElementById('modal-submit-post');
        
        if (!caption || caption.length > 280) {
            showNotification('Caption must be 1-280 characters', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('caption', caption);
        formData.append('create_post', 'true');
        
        // Show loading state
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            submitBtn.disabled = true;
        }
        
        fetch('Dashboard.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                showNotification('Post created successfully!', 'success');
                closeTweetModal();
                // Refresh feed after 1 second
                setTimeout(loadPosts, 1000);
            } else {
                throw new Error('Server error: ' + response.status);
            }
        })
        .catch(error => {
            console.error('Error creating post:', error);
            showNotification('Error creating post', 'error');
            if (submitBtn) {
                submitBtn.innerHTML = 'Tweet';
                submitBtn.disabled = false;
            }
        });
    }
    </script>
</body>
</html>

<?php
function time_elapsed_string($datetime, $full = false) {
    if (empty($datetime)) return 'just now';
    
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