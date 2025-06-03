<?php
require_once 'php/config.php';
require_once 'php/auth.php';

// Process login form submission
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else if (authenticateUser($username, $password)) {
        // Successful login, redirect to dashboard
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}

$pageTitle = "Login | Invitation Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-form-container">
            <div class="login-header">
                <h1>Invitation Management System</h1>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form class="login-form" method="post" action="login.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">
                        Se connecter
                    </button>
                </div>
            </form>
        </div>
        
        <div class="login-decoration">
            <div class="decoration-overlay"></div>
            <div class="decoration-content">
                <img src="assets/img/logo-golf.png" style="width:60%">
                <h2>Manage Your Event</h2>
                <p>Send invitations, track confirmations, and create personalized experiences for your guests</p>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>