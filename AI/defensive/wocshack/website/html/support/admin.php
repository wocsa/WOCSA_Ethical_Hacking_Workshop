<?php
require_once 'models/Admin.php'; // Include the Admin model
require_once 'models/User.php'; // Include the User model
require 'tools/csrf.php';

if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if the user is not logged in
    header('Location: ?page=user/login.php');
    exit();
} else {
    $userModel = new User($pdo);
    $userId = $_SESSION['user_id'];
    $isAdmin = $userModel->isAdmin($userId);

    if (!$isAdmin) {
        header('Location: index.php?page=errors/access_denied.php');
        die("Access denied.");
    } else {
        $userModel = new User($pdo);
        $userId = $_SESSION['user_id'];
        $isAdmin = $userModel->isAdmin($userId);
        $isAdminInAnyAssociation = $userModel->isAdminInAnyAssociation($userId);
        $user = $userModel->getUserById($userId); // Get the user details
        $username = $user['username'];
        $profilePicture = $user['profile_picture'] ? $user['profile_picture'] : 'uploads/default.jpg';
        $csrf = new csrf();
        $csrfToken = $csrf->generateCSRFToken(); // Generate and store CSRF token
    }
}

$adminModel = new Admin($pdo);
$response = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promote_user'])) {
    $userId = intval($_POST['user_id']);

    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    if ($adminModel->promoteUserToAdmin($userId)) {
        $response = ['status' => 'success', 'message' => 'User promoted to admin successfully.'];
    } else {
        $response = ['status' => 'error', 'message' => 'Failed to promote user.'];
    }
}

if (isset($_POST['delete_admin'])) {
    $adminId = intval($_POST['admin_id']);

    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    if ($adminModel->deleteAdmin($adminId)) {
        $response = ['status' => 'success', 'message' => 'Admin deleted successfully.'];
    } else {
        $response = ['status' => 'error', 'message' => 'Failed to delete admin.'];
    }
}

if (isset($_POST['delete_user'])) {
    $userId = intval($_POST['user_id']);

    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    if ($adminModel->deleteUser($userId)) {
        $response = ['status' => 'success', 'message' => 'User deleted successfully.'];
    } else {
        $response = ['status' => 'error', 'message' => 'Failed to delete user.'];
    }
}

$users = $adminModel->getAllUsers();
$admins = $adminModel->getAllAdmins();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Admin Management</h1>
                    </div>

                    <!-- Display response messages -->
                    <?php if ($response): ?>
                        <div class="alert alert-<?= $response['status'] ?>">
                            <?= $response['message'] ?>
                        </div>
                    <?php endif; ?>

                    <!-- Promote an existing user to admin -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Promote Existing User to Admin</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="user_id">Select a user:</label>
                                    <select name="user_id" class="form-control" required>
                                        <option value="">Select a user...</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['email']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <button type="submit" name="promote_user" class="btn btn-primary">Promote to Admin</button>
                                <button type="submit" name="delete_user" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete User</button>
                            </form>
                        </div>
                    </div>

                    <!-- Display current admins -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Current Admins</h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($admins)): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($admins as $admin): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= htmlspecialchars($admin['username']) ?> (<?= htmlspecialchars($admin['email']) ?>)
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <button type="submit" name="delete_admin" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this admin?');">Delete</button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-center">No admins found.</p>
                            <?php endif; ?>
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
