<?php
// Database connection
$host = 'localhost';
$db   = 'z_users';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Auto-create tables if they don't exist
    create_tables_if_not_exist($pdo);
    
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

function create_tables_if_not_exist($pdo) {
    // Check if posts table exists
    $check = $pdo->query("SHOW TABLES LIKE 'posts'");
    if ($check->rowCount() == 0) {
        // Create posts table
        $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            caption VARCHAR(280),
            image_url VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_created (user_id, created_at)
        )");
    }
    
    // Check if follows table exists
    $check = $pdo->query("SHOW TABLES LIKE 'follows'");
    if ($check->rowCount() == 0) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS follows (
            follower_id INT NOT NULL,
            followed_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (follower_id, followed_id),
            FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_follower (follower_id),
            INDEX idx_followed (followed_id)
        )");
    }
    
    // Check if likes table exists
    $check = $pdo->query("SHOW TABLES LIKE 'likes'");
    if ($check->rowCount() == 0) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS likes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_like (user_id, post_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            INDEX idx_post (post_id)
        )");
    }
    
    // Check if comments table exists
    $check = $pdo->query("SHOW TABLES LIKE 'comments'");
    if ($check->rowCount() == 0) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            body VARCHAR(500) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            INDEX idx_post (post_id),
            INDEX idx_user_post (user_id, post_id)
        )");
    }
}
?>