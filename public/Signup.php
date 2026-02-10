<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up</title>
<link rel="stylesheet" href="../assets/css/SUstyles.css">
</head>
<body>

<?php
session_start();
// Get errors from session if they exist
$errors = $_SESSION['errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];

// Clear errors after retrieving
unset($_SESSION['errors']);
unset($_SESSION['form_data']);
?>

<!-- Loading Screen -->
<div id="loading-screen">
    <div class="spinner"></div>
</div>

<!-- Main Wrapper -->
<div class="main-wrapper">
    
    <img class="logo" src="../assets/icons/Z.png" alt="Logo">

    <div class="container">
        <h2>Sign up</h2>

       <form action="../assets/php/Signup_process.php" method="POST">

            <!-- Row 1 -->
            <div class="row">
                <div class="input-box <?php echo !empty($errors['username']) ? 'has-error' : ''; ?>">
                    <input type="text" name="username" placeholder=" " required value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>">
                    <label>Username</label>
                </div>
                <div class="input-box <?php echo !empty($errors['dob']) ? 'has-error' : ''; ?>">
                    <input type="date" name="dob" placeholder=" " required value="<?php echo htmlspecialchars($form_data['dob'] ?? ''); ?>">
                    <label>Date of birth</label>
                </div>
            </div>
            <div class="row">
                <div class="error-space"><?php echo $errors['username'] ?? ''; ?></div>
                <div class="error-space"><?php echo $errors['dob'] ?? ''; ?></div>
            </div>

            <!-- Row 2 -->
            <div class="row">
                <div class="input-box <?php echo !empty($errors['firstname']) ? 'has-error' : ''; ?>">
                    <input type="text" name="firstname" placeholder=" " required value="<?php echo htmlspecialchars($form_data['firstname'] ?? ''); ?>">
                    <label>First Name</label>
                </div>
                <div class="input-box <?php echo !empty($errors['lastname']) ? 'has-error' : ''; ?>">
                    <input type="text" name="lastname" placeholder=" " required value="<?php echo htmlspecialchars($form_data['lastname'] ?? ''); ?>">
                    <label>Last Name</label>
                </div>
            </div>
            <div class="row">
                <div class="error-space"><?php echo $errors['firstname'] ?? ''; ?></div>
                <div class="error-space"><?php echo $errors['lastname'] ?? ''; ?></div>
            </div>

            <!-- Row 3 -->
            <div class="row">
                <div class="input-box <?php echo !empty($errors['email']) ? 'has-error' : ''; ?>">
                    <input type="email" name="email" placeholder=" " required value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
                    <label>Email</label>
                </div>
                <div class="input-box <?php echo !empty($errors['phone']) ? 'has-error' : ''; ?>">
                    <input type="tel" name="phone" placeholder=" " required 
                           pattern="[0-9]{10}" 
                           title="Please enter exactly 10 digits"
                           maxlength="10"
                           value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>">
                    <label>Phone Number</label>
                </div>
            </div>
            <div class="row">
                <div class="error-space"><?php echo $errors['email'] ?? ''; ?></div>
                <div class="error-space"><?php echo $errors['phone'] ?? ''; ?></div>
            </div>

            <!-- Address (Full width) -->
            <div class="full-width">
                <div class="input-box <?php echo !empty($errors['address']) ? 'has-error' : ''; ?>">
                    <input type="text" name="address" placeholder=" " value="<?php echo htmlspecialchars($form_data['address'] ?? ''); ?>">
                    <label>Address</label>
                </div>
                <div class="error-space"><?php echo $errors['address'] ?? ''; ?></div>
            </div>

            <!-- Row 4 - Passwords -->
            <div class="row">
                <div class="input-box <?php echo !empty($errors['password']) ? 'has-error' : ''; ?>">
                    <input type="password" name="password" placeholder=" " required>
                    <label>Password</label>
                </div>
                <div class="input-box <?php echo !empty($errors['confirm_password']) ? 'has-error' : ''; ?>">
                    <input type="password" name="confirm_password" placeholder=" " required>
                    <label>Confirm Password</label>
                </div>
            </div>
            <div class="row">
                <div class="error-space"><?php echo $errors['password'] ?? ''; ?></div>
                <div class="error-space"><?php echo $errors['confirm_password'] ?? ''; ?></div>
            </div>

            <!-- Common error message -->
            <?php if (!empty($errors['common'])): ?>
                <div class="common-error">
                    <?php echo $errors['common']; ?>
                </div>
            <?php endif; ?>

            <button class="btn" type="submit" name="signup">Create Account</button>

            <div class="footer">
                Already have an account? <a href="Signin.php">Login</a>
            </div>

        </form>

    </div>

</div>

<script src="../assets/js/Load.js"></script>

</body>
</html>