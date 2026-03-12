<?php
require 'models/User.php';

// Check if the user is logged in and fetch associations where the user is an admin
if (isset($_SESSION['user_id'])) {
    $userModel = new User($pdo);
    $userId = $_SESSION['user_id'];
    $isAdmin = $userModel->isAdmin($userId);
    $isAdminInAnyAssociation = $userModel->isAdminInAnyAssociation($userId);
    $user = $userModel->getUserById($userId); // Get the user details
    $username = $user['username'];
    $profilePicture = $user['profile_picture'] ? $user['profile_picture'] : 'uploads/default.jpg'; // Default image if no profile picture
}

$server_name = getenv('SERVER_NAME') ?: 'localhost:8080';
$webmail_link = "https://webmail.$server_name";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to My Association</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Tutorial</h1>
                        <h2 class="h3 mb-0 text-gray-800">How to properly use <strong>My Association</strong></h2>
                    </div>

                    <!-- Presentation Section -->
                    <div class="row">
                        <div class="col-xl-12 col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">How to use:</h6>
                                </div>
                                <div class="card-body">
                                    <p>To use properly <strong>My association</strong> you will need an E-mail account form our sponsor <strong>Tbox Compagny</strong> <br>
                                        available here </p>
                                        <a href="<?php echo $webmail_link;?>" target="_blank">
                                            <img src="./startbootstrap-sb-admin-2-gh-pages/img/tbox_logo.png" alt='192.168.20.4'/>
                                        </a>
                                        <br>
                                        <h5>Tanks so much to them !!!</h5>
                                        <h5>Be aware that any other e-mail provider will not properly work </h5>
                                </div>
                        </div>
                    </div>

                    <!-- About the Team Section -->
                    <div class="row">
                        <div class="col-xl-12 col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">About Our Team</h6>
                                </div>
                                <div class="card-body">
                                    <p>Behind My Association is a dedicated team of passionate individuals who have no idea what they're doing but somehow make things work.</p>
                                    <p>From our coffee-fueled developers to our ever-enthusiastic designers, we bring chaos and creativity together to build something that (mostly) functions.</p>
                                    <p>Our mission? To create a platform that doesn’t crash (too often) and makes your life easier when managing associations. If it doesn’t, well… we tried.</p>
                                    <p>Thank you for using My Association. Now, go click some buttons!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include_once 'footer.php'; ?>
            <!-- End of Footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->
</body>

<?php include_once 'scripts.php'; ?>

</html>
