<?php
require 'models/User.php'; // No session_start and config.php
require 'tools/csrf.php';

$csrf = new csrf();

$userModel = new User($pdo);
$success_message = '';
$error_message = '';
$csrfToken = $csrf->generateCSRFToken(); // Generate and store CSRF token

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    // Password policy: Minimum 8 characters, at least one uppercase, one lowercase, one number, and one special character
    $password_pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';

    // Validate password match
    if ($new_password !== $confirm_password) {
        $error_message = 'Passwords do not match!';
    }
    // Validate password against policy
    elseif (!preg_match($password_pattern, $new_password)) {
        $error_message = 'Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.';
    }
    // Reset the password if validation passes
    else {
        if ($userModel->resetPassword($token, $new_password)) {
            $success_message = 'Your password has been successfully reset. <a href="?page=user/login.php">Login</a>';
        } else {
            $error_message = 'Invalid or expired token.';
        }
    }
} else {
    $token = $_GET['token'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <?php include_once 'style.php'; ?>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <?php include_once 'navbar.php'; ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <?php include_once 'topbar.php'; ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Reset Password</h1>
                    </div>

                    <div class="row justify-content-center">
                        <div class="col-xl-6 col-lg-7">
                            <div class="card shadow mb-4">
                                <div class="card-body">
                                    <!-- Success Message -->
                                    <?php if ($success_message): ?>
                                        <div class="alert alert-success" role="alert">
                                            <?php echo $success_message; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Error Message -->
                                    <?php if ($error_message): ?>
                                        <div class="alert alert-danger" role="alert">
                                            <?php echo $error_message; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Reset Password Form -->
                                    <?php if (!$success_message): ?>
                                        <form method="POST" class="user">
                                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                                            
                                            <div class="form-group">
                                                <label for="new_password">New Password</label>
                                                <input type="password" class="form-control form-control-user" name="new_password" id="new_password" required placeholder="Enter new password">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="confirm_password">Confirm Password</label>
                                                <input type="password" class="form-control form-control-user" name="confirm_password" id="confirm_password" required placeholder="Confirm new password">
                                            </div>

                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                                            <button type="submit" class="btn btn-primary btn-user btn-block">
                                                Reset Password
                                            </button>

                                            <div class="mt-2 text-muted small">
                                                Password must be at least 8 characters long, with at least one uppercase letter, one lowercase letter, one number, and one special character.
                                            </div>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Back to Login Link -->
                                    <div class="text-center mt-3">
                                        <a class="small" href="?page=user/login.php">Back to Login</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once 'footer.php'; ?>
        </div>
    </div>
</body>

<?php include_once 'scripts.php'; ?>

</html>