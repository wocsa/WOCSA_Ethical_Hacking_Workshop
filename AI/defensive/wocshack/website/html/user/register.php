<?php
require 'models/User.php';
require 'tools/csrf.php';

$userModel = new User($pdo);
$error_message = '';
$success_message = '';
$username = ''; // Initialize username
$email = ''; // Initialize email
$password = ''; // Initialize password
$confirm_password = ''; // Initialize confirm_password
$is_admin = false; // Initialize admin checkbox
$csrf = new csrf();
$csrfToken = $csrf->generateCSRFToken(); // Generate and store CSRF token

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username']; // Store the entered username
    $email = $_POST['email']; // Store the entered email
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0; // Checkbox for admin status

    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    // Check if username is already taken
    if ($userModel->getUserByUsername($username)) {
        $error_message = 'Username is already taken!';
    }
    // Check if email is already taken
    elseif ($userModel->getUserByEmail($email)) {
        $error_message = 'Email is already registered!';
    }
    // Check if passwords match
    elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match!';
    }
    // Validate email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email format!';
    }
    // Check password policy using the verifyPasswordPolicy method
    elseif (($policy_check = $userModel->verifyPasswordPolicy($password)) !== true) {
        $error_message = $policy_check;
    } else {
        // Create a new user
        $userModel->createUser($username, $email, $password, $is_admin);
        $success_message = 'Registration successful!';
        header('Location: index.php?page=user/login.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Register</h1>
                    </div>

                    <div class="container">
                        <div class="card o-hidden border-0 shadow-lg my-5">
                            <div class="card-body p-0">
                                <div class="row">
                                    <div class="col-lg-5 d-none d-lg-block bg-login-image" style="background-image: url('startbootstrap-sb-admin-2-gh-pages/img/register.jpg');"></div>
                                    <div class="col-lg-7">
                                        <div class="p-5">
                                            <div class="text-center">
                                                <h1 class="h4 text-gray-900 mb-4">Create an Account!</h1>
                                            </div>
                                            <?php if ($error_message): ?>
                                                <div class="alert alert-danger" role="alert">
                                                    <?php echo htmlspecialchars($error_message); ?>
                                                </div>
                                            <?php elseif ($success_message): ?>
                                                <div class="alert alert-success" role="alert">
                                                    <?php echo htmlspecialchars($success_message); ?>
                                                </div>
                                            <?php endif; ?>
                                            <form method="POST" class="user">
                                                <!-- A form group containing an input field for the username. -->
                                                <div class="form-group">
                                                    <input type="text" class="form-control form-control-user" id="exampleInputUsername"
                                                        name="username" placeholder="Enter Username..."
                                                        value="<?php echo htmlspecialchars($username); ?>" required>
                                                </div>
                                                <!-- A form group containing an input field for the email. -->
                                                <div class="form-group">
                                                    <input type="email" class="form-control form-control-user" id="exampleInputEmail"
                                                        name="email" placeholder="Email Address"
                                                        value="<?php echo htmlspecialchars($email); ?>" required>
                                                </div>
                                                <!-- A form group containing two input fields for the password and confirm password. -->
                                                <div class="form-group row">
                                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                                        <input type="password" class="form-control form-control-user" id="exampleInputPassword"
                                                            name="password" placeholder="Password" required>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <input type="password" class="form-control form-control-user" id="exampleRepeatPassword"
                                                            name="confirm_password" placeholder="Repeat Password" required>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <!-- A form group containing a checkbox for admin status. -->
                                                <button type="submit" class="btn btn-primary btn-user btn-block">Register Account</button>
                                            </form>
                                            <hr>
                                            <div class="text-center">
                                                <a class="small" href="?page=user/reset_password.php">Forgot Password?</a>
                                            </div>
                                            <div class="text-center">
                                                <a class="small" href="?page=user/login.php">Already have an account? Login!</a>
                                            </div>
                                        </div>
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