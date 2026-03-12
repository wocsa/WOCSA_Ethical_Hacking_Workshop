<?php
require 'models/User.php';

$userModel = new User($pdo);
$error_message = '';
$user_profile = null;

// Check if UUID is provided
if (!isset($_GET['uuid'])) {
    $error_message = 'No user specified.';
} else {
    $uuid = $_GET['uuid'];
    $user_profile = $userModel->getUserByUUID($uuid);

    if (!$user_profile) {
        $error_message = 'User not found.';
    }
}

// Check if the user is logged in and fetch associations where the user is an admin
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $isAdmin = $userModel->isAdmin($userId);
    $isAdminInAnyAssociation = $userModel->isAdminInAnyAssociation($userId);
    $user = $userModel->getUserById($userId); // Get the user details
    $username = $user['username'];
    $profilePicture = $user['profile_picture'] ? $user['profile_picture'] : 'uploads/default.jpg'; // Default image if no profile picture
    $logged_in_user = true;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Profile</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Public Profile</h1>
                    </div>

                    <!-- Display error message if any -->
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($user_profile): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($user_profile['username']); ?></h5>
                                <p class="card-text"><strong>Email:</strong> <?php echo htmlspecialchars($user_profile['email']); ?></p>
                                <p class="card-text"><strong>Bio:</strong> <?php echo htmlspecialchars($user_profile['bio']); ?></p>
                                <?php if ($user_profile['profile_picture']): ?>
                                    <img src='<?php echo ($user_profile['profile_picture']); ?>' class="img-thumbnail mt-2" width="100" height="100" alt="Profile Picture">
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Link back to the user's profile page if the user is logged in -->
                    <?php if ($logged_in_user): ?>
                        <p><a href="?page=user/profile.php">Back to your profile</a></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- End of Main Content -->
            <?php include_once 'footer.php'; ?>

        </div>
    </div>
</body>

<?php include_once 'scripts.php'; ?>

</html>