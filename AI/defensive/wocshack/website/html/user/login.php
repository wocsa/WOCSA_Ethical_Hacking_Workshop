<?php
if (isset($_SESSION['user_id'])):
    // Redirect to home
    header('Location: index.php');
    exit();
endif;

require 'models/User.php';

$userModel = new User($pdo);
$error_message = '';
$username = ''; // Initialize username to an empty string

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username']; // Store the entered username
    $password = $_POST['password'];

    // Authenticate the user
    $user = $userModel->authenticateUser($username, $password);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: index.php');
        exit();
    } else {
        $error_message = 'Invalid username or password!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Login</h1>
                    </div>

                    <div class="container">
                        <div class="card o-hidden border-0 shadow-lg my-5">
                            <div class="card-body p-0">
                                <div class="row">
                                    <div class="col-lg-5 d-none d-lg-block bg-login-image" style="background-image: url('startbootstrap-sb-admin-2-gh-pages/img/login.jpg');"></div>
                                    <div class="col-lg-7">
                                        <div class="p-5">
                                            <div class="text-center">
                                                <h1 class="h4 text-gray-900 mb-4">Welcome Back!</h1>
                                            </div>
                                            <?php if ($error_message): ?>
                                                <div class="alert alert-danger" role="alert">
                                                    <?php echo htmlspecialchars($error_message); ?>
                                                </div>
                                            <?php endif; ?>
                                            <form method="POST" class="user">
                                                <div class="form-group">
                                                    <input type="text" class="form-control form-control-user" id="exampleInputUsername"
                                                        name="username" placeholder="Enter Username..."
                                                        value="<?php echo ($username); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <input type="password" class="form-control form-control-user" id="exampleInputPassword"
                                                        name="password" placeholder="Password" required>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-user btn-block">Login</button>
                                            </form>
                                            <hr>
                                            <div class="text-center">
                                                <a class="small" href="?page=user/reset_password.php">Forgot Password?</a>
                                            </div>
                                            <div class="text-center">
                                                <a class="small" href="?page=user/register.php">Create an Account!</a>
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