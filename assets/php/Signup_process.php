<?php
// assets/php/Signup_process.php
session_start();
require_once 'conn.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../../public/Dashboard.php');
    exit;
}

$errors = [];
$form_data = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    // Username validation
    if (empty($_POST['username'])) {
        $errors['username'] = "*Username is required*";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $_POST['username'])) {
        $errors['username'] = "*Username must be 3-30 characters (letters, numbers, underscores only)*";
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$_POST['username']]);
        if ($stmt->fetch()) {
            $errors['username'] = "*Username already taken*";
        }
    }

    // First name validation
    if (empty($_POST['firstname'])) {
        $errors['firstname'] = "*First name is required*";
    } elseif (strlen($_POST['firstname']) < 2) {
        $errors['firstname'] = "*First name must be at least 2 characters*";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $_POST['firstname'])) {
        $errors['firstname'] = "*First name can only contain letters and spaces*";
    }

    // Last name validation
    if (empty($_POST['lastname'])) {
        $errors['lastname'] = "*Last name is required*";
    } elseif (strlen($_POST['lastname']) < 2) {
        $errors['lastname'] = "*Last name must be at least 2 characters*";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $_POST['lastname'])) {
        $errors['lastname'] = "*Last name can only contain letters and spaces*";
    }

    // Email validation
    if (empty($_POST['email'])) {
        $errors['email'] = "*Email is required*";
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "*Invalid email format*";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$_POST['email']]);
        if ($stmt->fetch()) {
            $errors['email'] = "*Email already registered*";
        }
    }

    // Phone validation
    if (empty($_POST['phone'])) {
        $errors['phone'] = "*Phone number is required*";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $_POST['phone'])) {
        $errors['phone'] = "*Phone must be 10-15 digits*";
    } else {
        // Check if phone exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$_POST['phone']]);
        if ($stmt->fetch()) {
            $errors['phone'] = "*Phone number already registered*";
        }
    }

    // DOB validation
    if (empty($_POST['dob'])) {
        $errors['dob'] = "*Date of birth is required*";
    } else {
        $dob = new DateTime($_POST['dob']);
        $today = new DateTime();
        $age = $today->diff($dob)->y;
        
        if ($age < 13) {
            $errors['dob'] = "*You must be at least 13 years old*";
        }
        
        if ($age > 120) {
            $errors['dob'] = "*Please enter a valid date of birth*";
        }
    }

    // Address validation (optional)
    if (!empty($_POST['address']) && strlen($_POST['address']) > 500) {
        $errors['address'] = "*Address is too long (max 500 characters)*";
    }

    // Password validation
    if (empty($_POST['password'])) {
        $errors['password'] = "*Password is required*";
    } elseif (strlen($_POST['password']) < 8) {
        $errors['password'] = "*Password must be at least 8 characters*";
    } elseif (!preg_match('/[A-Z]/', $_POST['password'])) {
        $errors['password'] = "*Password must contain at least one uppercase letter*";
    } elseif (!preg_match('/[a-z]/', $_POST['password'])) {
        $errors['password'] = "*Password must contain at least one lowercase letter*";
    } elseif (!preg_match('/[0-9]/', $_POST['password'])) {
        $errors['password'] = "*Password must contain at least one number*";
    }

    // Confirm password validation
    if (empty($_POST['confirm_password'])) {
        $errors['confirm_password'] = "*Please confirm your password*";
    } elseif ($_POST['password'] !== $_POST['confirm_password']) {
        $errors['confirm_password'] = "*Passwords do not match*";
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Get first letter for profile picture
            $first_letter = strtoupper(substr($_POST['firstname'], 0, 1));
            
            // Hash password
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            // Insert user (profile_pic column will store the first letter)
            $stmt = $pdo->prepare("INSERT INTO users (username, firstname, lastname, email, phone, address, dob, password, profile_pic) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['username'],
                $_POST['firstname'],
                $_POST['lastname'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['address'] ?? '',
                $_POST['dob'],
                $hashed_password,
                $first_letter  // Store only the first letter
            ]);
            
            // Get user ID
            $user_id = $pdo->lastInsertId();
            
            // Create welcome post
            $welcome_post = $pdo->prepare("INSERT INTO posts (user_id, caption) VALUES (?, ?)");
            $welcome_post->execute([
                $user_id,
                "Hello Z! Just joined the platform. Excited to connect with everyone! 🚀 #NewUser #Welcome"
            ]);
            
            // Commit transaction
            $pdo->commit();
            
            // Set session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $_POST['username'];
            $_SESSION['firstname'] = $_POST['firstname'];
            $_SESSION['lastname'] = $_POST['lastname'];
            $_SESSION['email'] = $_POST['email'];
            $_SESSION['profile_pic'] = $first_letter; // Store first letter
            $_SESSION['bio'] = '';
            $_SESSION['location'] = '';
            $_SESSION['website'] = '';
            $_SESSION['login_time'] = time();
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Redirect to dashboard
            header('Location: ../../public/Dashboard.php');
            exit;
            
        } catch(PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            
            $errors['common'] = "Registration failed. Please try again.";
            
            // Log error
            error_log("Signup error: " . $e->getMessage());
            
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = $form_data;
            header('Location: ../../public/Signup.php');
            exit;
        }
    } else {
        // Store errors in session and redirect back
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $form_data;
        header('Location: ../../public/Signup.php');
        exit;
    }
}

// If not a POST request, redirect to signup
header('Location: ../../public/Signup.php');
exit;
?>