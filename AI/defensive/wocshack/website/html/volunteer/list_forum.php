<?php
// Load the Association and Forum models
require_once 'models/Association.php';
require_once 'models/Forum.php';
require_once 'models/User.php';
require 'tools/csrf.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ?page=user/login.php');
    exit();
} else {
    $csrf = new csrf();
    $csrfToken = $csrf->generateCSRFToken(); // Generate and store CSRF token
    $userModel = new User($pdo);
    $userId = $_SESSION['user_id'];
    $isAdmin = $userModel->isAdmin($userId);
    $isAdminInAnyAssociation = $userModel->isAdminInAnyAssociation($userId);
    $user = $userModel->getUserById($userId); // Get the user details
    $username = $user['username'];
    $profilePicture = $user['profile_picture'] ? $user['profile_picture'] : 'uploads/default.jpg'; // Default image if no profile picture
}

$forumModel = new Forum($pdo);
$associationUUID = $_GET['uuid'];

// Create instances of the models
$associationModel = new Association($pdo);

// Retrieve the association information
$association = $associationModel->getAssociationByUUID($associationUUID);

if (!$association) {
    echo "Association not found.";
    exit;
}

$associationId = $association['id'];

// Retrieve the forums associated with the association
$forums = $forumModel->getForumsByAssociationId($association['id']);

// Check if the user is an admin of this association
$userAssociations = $associationModel->getAssociationsByRole($userId);

$isAdmin = in_array($associationId, array_column($userAssociations['admin'], 'id'));

$isMember = in_array($associationId, array_column($userAssociations['member'], 'id'));


if ($isAdmin != 1 && $isMember != 1) {
    // Code à exécuter si l'utilisateur n'est pas admin si membre
    header('Location: index.php?page=errors/access_denied.php');
    die("Access denied.");
}

// Delete a forum if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_forum'])) {
    $forumId = $_POST['forum_id'];

    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    // Check if the user is an admin
    if ($isAdmin) {
        $forumModel->deleteForum($forumId);
        header("Location: ?page=volunteer/list_forum.php&uuid=$associationUUID");
        exit;
    } else {
        echo "You do not have permission to delete this forum.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forums of the association <?php echo htmlspecialchars($association['name']); ?></title>
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
                        <h1 class="h3 mb-0 text-gray-800">Forums of the association: <?php echo htmlspecialchars($association['name']); ?></h1>
                    </div>

                    <?php if (!empty($forums)): ?>
                        <div class="list-group">
                            <?php foreach ($forums as $forum): ?>
                                <div class="list-group-item">
                                    <h5 class="mb-1">
                                        <a href="index.php?page=volunteer/forum.php&id=<?php echo $forum['id']; ?>&uuid=<?php echo $associationUUID; ?>">
                                            <?php echo htmlspecialchars($forum['title']); ?>
                                        </a>
                                    </h5>
                                    <p class="mb-1"><?php echo htmlspecialchars($forum['description']); ?></p>
                                    <?php if ($isAdmin): ?>
                                        <!-- Delete button -->
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this forum?');" class="d-inline">
                                            <input type="hidden" name="delete_forum" value="true">
                                            <input type="hidden" name="forum_id" value="<?php echo $forum['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" role="alert">
                            No forums associated with this association.
                        </div>
                    <?php endif; ?>

                    <?php if ($isAdmin): ?>
                        <form action="index.php" method="GET" class="mt-3">
                            <input type="hidden" name="page" value="volunteer/create_forum.php">
                            <input type="hidden" name="uuid" value="<?php echo htmlspecialchars($associationUUID); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <button type="submit" class="btn btn-primary">Create a new forum</button>
                        </form>
                    <?php endif; ?>

                    <!-- Back link to the public profile of the association -->
                    <?php if ($isAdmin): ?>
                        <a href="index.php?page=association/profile.php&uuid=<?php echo $associationUUID; ?>" class="btn btn-secondary mt-3">Back to forum management</a>
                    <?php else: ?>
                        <a href="index.php?page=association/public_profile.php&uuid=<?php echo $associationUUID; ?>" class="btn btn-secondary mt-3">Back to the public profile of the association</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php include_once 'footer.php'; ?>
        </div>
    </div>
</body>


<?php include_once 'scripts.php'; ?>

</html>
