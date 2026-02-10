<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<link rel="stylesheet" href="../assets/css/SIstyles.css">
</head>
<body>

<?php
session_start();
// Get errors from session if they exist - ONLY after form submission
$usernameErr = '';
$passwordErr = '';
$Found = '';
$savedUsername = '';

// Check if form was submitted by looking for errors in session
if (isset($_SESSION['errors'])) {
    $usernameErr = $_SESSION['errors']['usernameErr'] ?? '';
    $passwordErr = $_SESSION['errors']['passwordErr'] ?? '';
    $Found = $_SESSION['errors']['Found'] ?? '';
    $savedUsername = $_SESSION['form_data']['username'] ?? '';
    
    // Clear errors after retrieving
    unset($_SESSION['errors']);
    unset($_SESSION['form_data']);
}

// Determine if we should show common error (both username and password are wrong)
$showCommonError = (!empty($usernameErr) && !empty($passwordErr)) || !empty($Found);
?>

<!-- Loading Screen -->
<div id="loading-screen">
    <div class="spinner"></div>
</div>

<!-- Login Form -->
<div class="main-wrapper">
    <img class="logo" src="../assets/icons/Z.png" alt="Logo">

    <div class="container">
        <h2>Sign in</h2>
        <form action="../assets/php/Signin_process.php" method="POST">

            <div class="input-box <?php echo (!empty($usernameErr) && empty($showCommonError)) ? 'has-error' : ''; ?>">
                <input type="text" placeholder="Enter username, email or phone" name="U" required value="<?php echo htmlspecialchars($savedUsername); ?>">
                <label>Username, Email or Phone</label>
            </div>
            <div class="error-space">
                <center><?php echo (!empty($usernameErr) && empty($showCommonError)) ? $usernameErr : ''; ?></center>
            </div>

            <div class="input-box <?php echo (!empty($passwordErr) && empty($showCommonError)) ? 'has-error' : ''; ?>">
                <input type="password" placeholder=" " name="P" required>
                <label>Password</label>
            </div>
            <div class="error-space">
                <center><?php echo (!empty($passwordErr) && empty($showCommonError)) ? $passwordErr : ''; ?></center>
            </div>

            <button class="btn" name="Signin" type="submit">Sign In</button>

            <!-- Common error below the button -->
            <center>
                <div class="error-space">
                    <?php 
                    if ($showCommonError) {
                        if (!empty($Found)) {
                            echo $Found;
                        } else {
                            echo "*Invalid username/email/phone and password*";
                        }
                    }
                    ?>
                </div>
            </center>

            <div class="footer">
                Don't have an account? <a href="Signup.php">Sign Up</a>
            </div>
        </form>
    </div>
</div>

<!-- Loading screen removal -->
<script src="../assets/js/Load.js"></script>

</body>
</html>