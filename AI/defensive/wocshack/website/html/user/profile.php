<?php
require 'models/User.php';
require 'models/Association.php';
require 'tools/csrf.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ?page=user/login.php');
    exit();
}

// Initialize the User and Association models
$userModel = new User($pdo);
$associationModel = new Association($pdo);
$userId = $_SESSION['user_id'];
$isAdmin = $userModel->isAdmin($userId);
$isAdminInAnyAssociation = $userModel->isAdminInAnyAssociation($userId);
$user = $userModel->getUserById($userId); // Get the user details
$username = $user['username'];
$profilePicture = $user['profile_picture'] ? $user['profile_picture'] : 'uploads/default.jpg';
$csrf = new csrf();
$csrfToken = $csrf->generateCSRFToken(); // Generate and store CSRF token

// Fetch all associations
$allAssociations = $associationModel->getUserAssociations($userId);

$error_message = '';
$success_message = '';

// Handle profile update and account deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_account'])) {
        // Handle account deletion
        $userModel->deleteUser($_SESSION['user_id']);

        // Log the user out after deletion
        session_destroy();

        // Redirect to a confirmation page or login page
        header('Location: index.php');
        exit();
    }

    // Handle profile update
    $email = $_POST['email'];
    $bio = $_POST['bio'];
    $password = !empty($_POST['password']) ? $_POST['password'] : null;
    $confirm_password = !empty($_POST['confirm_password']) ? $_POST['confirm_password'] : null;

    // Initialize profile picture variable with the current one
    $profile_picture = $user['profile_picture'];

    // Handle file upload for profile picture
    if (!empty($_FILES['profile_picture']['name'])) {
        $targetDir = "uploads/";
        $uploadResult = $userModel->validateAndUploadProfilePicture($_FILES['profile_picture'], $targetDir);

        if (!str_starts_with($uploadResult, 'uploads')) {
            // If the result is an error message string
            $error_message = $uploadResult;
        } else {
            // Update the profile picture variable
            $profile_picture = $uploadResult;
        }
    }

    // Check if the email is already registered (but allow the user to keep their own email)
    $existingUser = $userModel->getUserByEmail($email);
    if ($existingUser && $existingUser['id'] != $_SESSION['user_id']) {
        $error_message = 'Email is already registered!';
    }

    // Password policy verification
    if (!$error_message && $password) {
        $policy_check = $userModel->verifyPasswordPolicy($password);
        if ($policy_check !== true) {
            $error_message = $policy_check;
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match!';
        }
    }

    // Proceed with update if no errors
    if (!$error_message) {
        $userModel->updateUser($_SESSION['user_id'], $user['username'], $email, $bio, $profile_picture, $password);
        $success_message = 'Profile updated successfully!';
    }

    // Update associations (commented out as in original code)
    /*
    $associationIds = isset($_POST['associations']) ? $_POST['associations'] : [];
    $userModel->updateUserAssociations($_SESSION['user_id'], $associationIds);
    */

    // Refresh user data
    $user = $userModel->getUserById($_SESSION['user_id']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Your Profile</h1>
                    </div>

                    <!-- Display error or success messages -->
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php elseif ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Profile Update Form -->
                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="5"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="profile_picture">Profile Picture</label>
                            <input type="file" class="form-control-file" id="profile_picture" name="profile_picture" accept="image/*">
                            <?php if ($user['profile_picture']): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" class="img-thumbnail mt-2" width="100" height="100" alt="Profile Picture">
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password if changing">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password if changing">
                        </div>

                        <h3 class="mt-4">Your Associations</h3>
                        <ul class="list-group">
                            <?php if (empty($allAssociations)): ?>
                                <li class="list-group-item">No associations joined.</li>
                            <?php else: ?>
                                <?php foreach ($allAssociations as $association): ?>
                                    <li class="list-group-item">
                                        <a href="index.php?page=association/public_profile.php&uuid=<?php echo $association['uuid']; ?>">
                                            <?php echo htmlspecialchars($association['name']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>

                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                        <button type="submit" class="btn btn-primary mt-3">Update Profile</button>
                    </form>

                    <!-- Delete Account Form -->
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');" class="mt-3">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <button type="submit" name="delete_account" class="btn btn-danger">Delete Account</button>
                    </form>

                    <!-- Link to Public Profile -->
                    <p class="mt-3"><a href="?page=user/public_profile.php&uuid=<?php echo htmlspecialchars($user['uuid']); ?>">View Your Public Profile</a></p>

                    <p><a href="?page=user/logout.php">Logout</a></p>

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->
            <?php include_once 'footer.php'; ?>
        </div>
        <!-- End of Content Wrapper -->
    </div>
</body>

<?php include_once 'scripts.php'; ?>

</html>
