<?php
session_start();
require_once '../assets/php/conn.php';

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

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: Signin.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Get profile user (default to current user)
$profile_id = isset($_GET['id']) ? intval($_GET['id']) : $current_user_id;

// Get profile user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$profile_id]);
$profile_user = $stmt->fetch();

if (!$profile_user) {
    header('Location: Dashboard.php');
    exit;
}

// Get followers/following counts
$followers_stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE followed_id = ?");
$followers_stmt->execute([$profile_id]);
$followers_count = $followers_stmt->fetchColumn();

$following_stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
$following_stmt->execute([$profile_id]);
$following_count = $following_stmt->fetchColumn();

// Check if current user follows this profile
$is_following = false;
if ($current_user_id != $profile_id) {
    $check_follow_stmt = $pdo->prepare("SELECT * FROM follows WHERE follower_id = ? AND followed_id = ?");
    $check_follow_stmt->execute([$current_user_id, $profile_id]);
    $is_following = $check_follow_stmt->fetch() ? true : false;
}

// Get user's posts - CORRECTED QUERY
$posts_stmt = $pdo->prepare("
    SELECT p.*, 
           u.username, 
           u.firstname,
           u.lastname,
           u.profile_pic,
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
           EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as liked_by_user
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$posts_stmt->execute([$current_user_id, $profile_id]);
$posts = $posts_stmt->fetchAll();

// Handle follow/unfollow
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_follow'])) {
    if ($current_user_id == $profile_id) {
        $_SESSION['error'] = "You cannot follow yourself.";
    } else {
        if ($is_following) {
            // Unfollow
            $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
            $stmt->execute([$current_user_id, $profile_id]);
            $is_following = false;
            $followers_count--;
        } else {
            // Follow
            $stmt = $pdo->prepare("INSERT INTO follows (follower_id, followed_id) VALUES (?, ?)");
            $stmt->execute([$current_user_id, $profile_id]);
            $is_following = true;
            $followers_count++;
        }
    }
    header("Location: Profile.php?id=$profile_id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile_user['username']); ?> / Z</title>
    <link rel="stylesheet" href="../assets/css/Dashboard.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
       /* ======================= */
/*      LOGOUT BUTTON      */
/* ======================= */

/* Logout Button Styles */
.logout-container {
    margin-top: auto;
    padding: 10px 0;
    border-top: 1px solid #2f3336;
}

.logout-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: #e7e9ea;
    text-decoration: none;
    border-radius: 29px;
    transition: all 0.2s ease;
    font-weight: 500;
    background: transparent;
    border: none;
    cursor: pointer;
    font-family: inherit;
    font-size: 15px;
    width: 100%;
    text-align: left;
}

.logout-btn:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: #ff4b4b;
}

.logout-btn i {
    font-size: 18px;
    width: 24px;
    text-align: center;
}

.logout-btn span {
    font-size: 15px;
}

/* Mobile logout button */
.logout-mobile {
    color: #ff4b4b !important;
}

.logout-mobile:hover {
    color: #ff6b6b !important;
}

/* Profile page specific adjustments */
.profile-page .sidebar {
    height: 100vh;
    display: flex;
    flex-direction: column;
}

.profile-page .logout-container {
    margin-bottom: 20px;
}

/* Fix for sidebar to prevent scroll */
.sidebar {
    grid-column: 1;
    border-right: 1px solid #2f3336;
    padding: 20px;
    height: 100vh;
    overflow-y: auto;
    position: sticky;
    top: 0;
    display: flex;
    flex-direction: column;
    max-height: 100vh;
}

/* Make sure the sidebar content fits */
.sidebar-content {
    flex: 1;
    overflow-y: auto;
}

/* Mobile adjustments */
@media (max-width: 1000px) {
    .logout-container {
        margin-top: 15px;
        padding: 8px 0;
    }
    
    .logout-btn {
        padding: 10px 14px;
        font-size: 14px;
    }
    
    .logout-btn i {
        font-size: 16px;
        width: 22px;
    }
}

@media (max-width: 680px) {
    .logout-container {
        display: none; /* Hide on mobile, using mobile nav instead */
    }
    
    .logout-mobile {
        display: flex !important;
    }
}

/* Hide scrollbar for sidebar */
.sidebar::-webkit-scrollbar {
    width: 0;
    background: transparent;
}

.sidebar {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}

/* Remove all scrollbars globally */
html, body {
    overflow-x: hidden;
    max-width: 100%;
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE 10+ */
}

html::-webkit-scrollbar,
body::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}

/* Ensure modals also don't have scrollbars */
.modal-content::-webkit-scrollbar,
.tweet-modal-content::-webkit-scrollbar,
.edit-post-content::-webkit-scrollbar {
    width: 6px;
}

.modal-content::-webkit-scrollbar-track,
.tweet-modal-content::-webkit-scrollbar-track,
.edit-post-content::-webkit-scrollbar-track {
    background: #2f3336;
    border-radius: 3px;
}

.modal-content::-webkit-scrollbar-thumb,
.tweet-modal-content::-webkit-scrollbar-thumb,
.edit-post-content::-webkit-scrollbar-thumb {
    background: #1d9bf0;
    border-radius: 3px;
}
    </style>
</head>
<body class="profile-page">
    <div class="dashboard-container">
        <!-- Left Sidebar (same as Dashboard) -->
        <div class="sidebar">
            <img src="../assets/icons/Z.png" alt="Z Logo" class="logo" onclick="window.location.href='Dashboard.php'">
            
            <ul class="nav-menu">
                <li><a href="Dashboard.php" class="nav-link"><i class="fas fa-home nav-icon"></i> <span>Home</span></a></li>
                <li><a href="#" class="nav-link"><i class="fas fa-search nav-icon"></i> <span>Explore</span></a></li>
                <li><a href="#" class="nav-link"><i class="fas fa-bell nav-icon"></i> <span>Notifications</span></a></li>
                <li><a href="#" class="nav-link"><i class="fas fa-envelope nav-icon"></i> <span>Messages</span></a></li>
                <li><a href="#" class="nav-link"><i class="fas fa-bookmark nav-icon"></i> <span>Bookmarks</span></a></li>
                <li><a href="Profile.php" class="nav-link active"><i class="fas fa-user nav-icon"></i> <span>Profile</span></a></li>
                <li><a href="#" class="nav-link"><i class="fas fa-ellipsis-h nav-icon"></i> <span>More</span></a></li>
           
            
            
            
            <?php
            $current_user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $current_user_stmt->execute([$current_user_id]);
            $current_user = $current_user_stmt->fetch();
            
            // Get first letter for profile picture
            $current_user_initial = !empty($current_user['firstname']) ? strtoupper(substr($current_user['firstname'], 0, 1)) : 'U';
            $profile_user_initial = !empty($profile_user['firstname']) ? strtoupper(substr($profile_user['firstname'], 0, 1)) : 'U';
            ?>
            
            <div class="user-profile" onclick="window.location.href='Profile.php?id=<?php echo $current_user_id; ?>'">
                <div class="profile-pic" data-letter="<?php echo $current_user_initial; ?>">
                    <?php echo $current_user_initial; ?>
                </div>
                <div class="user-info">
                    <div class="username"><?php echo htmlspecialchars($current_user['firstname'] . ' ' . $current_user['lastname']); ?></div>
                    <div class="handle">@<?php echo htmlspecialchars($current_user['username']); ?></div>
                </div>
                <div class="more-btn"><i class="fas fa-ellipsis-h"></i></div>
            </div>
             
            <div class="sidebar">
                <!-- Other menu items -->
                <a href="../assets/php/logout.php" class="logout-btn">Logout</a>
            </div>
                
        
            
            
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1><?php echo htmlspecialchars($profile_user['firstname'] . ' ' . $profile_user['lastname']); ?></h1>
                <div class="tweet-count"><?php echo count($posts); ?> Posts</div>
            </div>
            
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-banner">
                    <!-- Banner image would go here -->
                    <div class="banner-placeholder"></div>
                    <div class="profile-pic-large" data-letter="<?php echo $profile_user_initial; ?>">
                        <?php echo $profile_user_initial; ?>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <?php if ($current_user_id == $profile_id): ?>
                        <button class="edit-profile-btn" onclick="window.location.href='EditProfile.php'">Edit Profile</button>
                    <?php else: ?>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="toggle_follow" class="follow-btn-large <?php echo $is_following ? 'following' : ''; ?>">
                                <?php echo $is_following ? 'Following' : 'Follow'; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($profile_user['firstname'] . ' ' . $profile_user['lastname']); ?></h2>
                    <div class="profile-handle">@<?php echo htmlspecialchars($profile_user['username']); ?></div>
                    
                    <?php if (!empty($profile_user['bio'])): ?>
                        <div class="profile-bio"><?php echo nl2br(htmlspecialchars($profile_user['bio'])); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($profile_user['location'])): ?>
                        <div class="profile-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($profile_user['location']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($profile_user['website'])): ?>
                        <div class="profile-website">
                            <i class="fas fa-link"></i>
                            <a href="<?php echo htmlspecialchars($profile_user['website']); ?>" target="_blank"><?php echo htmlspecialchars($profile_user['website']); ?></a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="profile-joined">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Joined <?php echo date('F Y', strtotime($profile_user['created_at'])); ?></span>
                    </div>
                    
                    <div class="profile-stats">
                        <a href="#" class="stat">
                            <span class="stat-number"><?php echo $following_count; ?></span>
                            <span class="stat-label">Following</span>
                        </a>
                        <a href="#" class="stat">
                            <span class="stat-number"><?php echo $followers_count; ?></span>
                            <span class="stat-label">Followers</span>
                        </a>
                        <a href="#" class="stat">
                            <span class="stat-number"><?php echo count($posts); ?></span>
                            <span class="stat-label">Posts</span>
                        </a>
                    </div>
                </div>
                
                <!-- Profile Tabs -->
                <div class="profile-tabs">
                    <button class="profile-tab active">Posts</button>
                    <button class="profile-tab">Replies</button>
                    <button class="profile-tab">Media</button>
                    <button class="profile-tab">Likes</button>
                </div>
            </div>
            
            <!-- User's Posts -->
            <div class="posts-feed">
                <?php if (empty($posts)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-feather"></i></div>
                        <h3>No posts yet</h3>
                        <p>When <?php echo $current_user_id == $profile_id ? 'you' : htmlspecialchars($profile_user['username']); ?> posts, they'll show up here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post-card" data-post-id="<?php echo $post['id']; ?>">
                            <div class="post-header">
                                <div class="profile-pic" data-letter="<?php echo $profile_user_initial; ?>">
                                    <?php echo $profile_user_initial; ?>
                                </div>
                                <div class="post-user-info">
                                    <span class="post-username"><?php echo htmlspecialchars($profile_user['firstname'] . ' ' . $profile_user['lastname']); ?></span>
                                    <span class="post-handle">@<?php echo htmlspecialchars($profile_user['username']); ?></span>
                                    <span class="post-time">· <?php echo time_elapsed_string($post['created_at']); ?></span>
                                </div>
                                <div class="post-menu"><i class="fas fa-ellipsis-h"></i></div>
                            </div>
                            
                            <div class="post-content">
                                <div class="post-caption"><?php echo nl2br(htmlspecialchars($post['caption'])); ?></div>
                                <?php if ($post['image_url']): ?>
                                    <img src="../<?php echo htmlspecialchars($post['image_url']); ?>" alt="Post image" class="post-image">
                                <?php endif; ?>
                            </div>
                            
                            <div class="post-stats">
                                <div class="post-stat comment-btn" onclick="openComments(<?php echo $post['id']; ?>)">
                                    <i class="far fa-comment"></i>
                                    <span class="stat-count" id="comment-count-<?php echo $post['id']; ?>"><?php echo $post['comment_count']; ?></span>
                                </div>
                                <div class="post-stat like-btn <?php echo $post['liked_by_user'] ? 'liked' : ''; ?>" onclick="toggleLike(<?php echo $post['id']; ?>)">
                                    <i class="<?php echo $post['liked_by_user'] ? 'fas' : 'far'; ?> fa-heart"></i>
                                    <span class="stat-count" id="like-count-<?php echo $post['id']; ?>"><?php echo $post['like_count']; ?></span>
                                </div>
                                <div class="post-stat">
                                    <i class="far fa-share-square"></i>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Right Sidebar (same as Dashboard) -->
        <div class="right-sidebar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search Z">
            </div>
            
            <div class="trending-section">
                <h3 class="section-title">What's happening</h3>
                <div class="trending-item">
                    <div class="trend-category">Trending in Pakistan</div>
                    <div class="trend-name">#WebDevelopment</div>
                    <div class="trend-count">12.5K posts</div>
                </div>
            </div>
        </div>
        
        <!-- Mobile Navigation -->
        <div class="mobile-nav">
            <ul class="mobile-nav-items">
                <li><a href="Dashboard.php" class="mobile-nav-link"><i class="fas fa-home"></i></a></li>
                <li><a href="#" class="mobile-nav-link"><i class="fas fa-search"></i></a></li>
                <li><a href="#" class="mobile-nav-link"><i class="fas fa-bell"></i></a></li>
                <li><a href="Profile.php" class="mobile-nav-link active"><i class="fas fa-user"></i></a></li>
            </ul>
        </div>
        
        
    </div>
    
    <script src="../assets/js/Dashboard.js"></script>
    
    <script>
        // Fix for sidebar on profile page
        document.addEventListener('DOMContentLoaded', function() {
            // Add class to body for profile page specific styles
            document.body.classList.add('profile-page');
            
            // Fix post stats overflow
            const postStats = document.querySelectorAll('.post-stats');
            postStats.forEach(stats => {
                stats.style.overflow = 'visible';
                stats.style.maxWidth = '425px';
            });
            
            // Remove any horizontal scroll
            document.body.style.overflowX = 'hidden';
            document.documentElement.style.overflowX = 'hidden';
        });
    </script>
</body>
</html>