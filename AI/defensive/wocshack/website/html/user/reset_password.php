<?php
require 'models/User.php';
require 'tools/csrf.php';

$csrf = new csrf();

$userModel = new User($pdo);
$success_message = '';
$error_message = '';
$csrfToken = $csrf->generateCSRFToken(); // Generate and store CSRF token

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capture the email entered by the user
    $email = $_POST['email'];

    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    // Generate password reset token
    $token = $userModel->generatePasswordResetToken($email);

    if ($token) {
        // Retrieve the server name from the environment variable
        $server_name = getenv('SERVER_NAME') ?: 'localhost:8080';
        // Construct the reset link
        $reset_link = "http://$server_name/index.php?page=user/reset_password_form.php&token=$token";

        // Prepare the email content
        $subject = "Password Reset Request";
        $content = "Click the following link to reset your <a href='$reset_link' target='_blank'>password</a>";

        // Build the query string with proper encoding
        $queryData = [
            'source' => 'My Association',
            'destination' => $email,
            'subject' => $subject,
            'content' => $content
        ];

        // Build the API URL with encoded parameters
        $sendEmailUrl = 'http://192.168.20.4/send?' . http_build_query($queryData);

        // Execute the request to send the email
        $response = file_get_contents($sendEmailUrl);

        if ($response == "0") {
            $success_message = "A password reset link has been sent to your email address.";
        } else {
            $error_message = "There was an issue sending the email. Please try again.";
        }
    } else {
        $success_message = "A password reset link has been sent to your email address.";
    }
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
                                    <form method="POST" class="user">
                                        <div class="form-group">
                                            <label for="email">Email Address</label>
                                            <input type="email" class="form-control form-control-user" name="email" id="email" required placeholder="Enter your email address">
                                        </div>
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            Send Reset Link
                                        </button>
                                    </form>

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
