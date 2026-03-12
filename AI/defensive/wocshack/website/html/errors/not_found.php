<?php
require 'models/User.php';

# Return 404
http_response_code(404);

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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resource not found</title>
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
                    <!-- 403 Error Text -->
                    <div class="text-center">
                        <div class="error mx-auto" data-text="404">404</div>
                        <p class="lead text-gray-800 mb-5">Resource not found</p>
                        <p class="text-gray-500 mb-0">It looks like you are trying to access a resource that does not exist...</p>
                        <a href="index.php">&larr; Back to home page</a>
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