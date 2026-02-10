<?php
// assets/php/Signin_process.php
session_start();
require_once 'conn.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../../public/Dashboard.php');
    exit;
}

$errors = ['usernameErr' => '', 'passwordErr' => '', 'Found' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Signin'])) {
    $username = trim($_POST['U']);
    $password = $_POST['P'];
    
    // Validate input
    if (empty($username)) {
        $errors['usernameErr'] = "*Username/Email/Phone is required*";
    }
    
    if (empty($password)) {
        $errors['passwordErr'] = "*Password is required*";
    }
    
    // Only proceed if no validation errors
    if (empty($errors['usernameErr']) && empty($errors['passwordErr'])) {
        // Check credentials
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? OR phone = ?");
        $stmt->execute([$username, $username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Update last login
                $update_stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $update_stmt->execute([$user['id']]);
                
                // Regenerate session ID
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['firstname'] = $user['firstname'];
                $_SESSION['lastname'] = $user['lastname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['profile_pic'] = $user['profile_pic']; // This is now just a letter
                $_SESSION['bio'] = $user['bio'];
                $_SESSION['location'] = $user['location'];
                $_SESSION['website'] = $user['website'];
                $_SESSION['created_at'] = $user['created_at'];
                $_SESSION['login_time'] = time();
                
                // Redirect to dashboard
                header('Location: ../../public/Dashboard.php');
                exit;
            } else {
                $errors['Found'] = "*Invalid username or password*";
            }
        } else {
            $errors['Found'] = "*Invalid username or password*";
        }
    }
    
    $_SESSION['errors'] = $errors;
    $_SESSION['form_data']['username'] = $username;
    header('Location: ../../public/Signin.php');
    exit;
}

header('Location: ../../public/Signin.php');
exit;
?>