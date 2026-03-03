<?php
require_once 'models/Forum.php';
require_once 'models/Association.php';
require_once 'models/User.php';
require 'tools/csrf.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=user/login.php');
    exit;
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

// Check if the forum ID is provided
if (!isset($_GET['id'])) {
    echo "No forum specified.";
    exit;
}

$forumId = $_GET['id'];
$associationUUID = $_GET['uuid'];
$userId = $_SESSION['user_id'];  // The ID of the logged-in user

// Create an instance of the Forum model
$forumModel = new Forum($pdo);

// Retrieve the forum information
$forum = $forumModel->getForumById($forumId);

if (!$forum) {
    echo "Forum not found.";
    exit;
}

// Create instances of the models
$associationModel = new Association($pdo);

$association = $associationModel->getAssociationByUUID($associationUUID);

if (!$association) {
    echo "Association not found.";
    exit;
}

$associationId = $association['id'];

// Check if the user is an admin of this association
$userAssociations = $associationModel->getAssociationsByRole($userId);

$isAdmin = in_array($associationId, array_column($userAssociations['admin'], 'id'));

$isMember = in_array($associationId, array_column($userAssociations['member'], 'id'));


// Retrieve the forum comments with user information
$comments = $forumModel->getCommentsByForumId($forumId);

// Handle adding a comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = $_POST['content'];

    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    if (!empty($content)) {
        $forumModel->createComment($content, $userId, $forumId);
        header("Location: index.php?page=volunteer/forum.php&id=$forumId&uuid=$associationUUID");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($forum['title']); ?></title>
    <?php include_once 'style.php'; ?>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include_once 'navbar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once 'topbar.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800"><?php echo htmlspecialchars($forum['title']); ?></h1>
                        <p><?php echo htmlspecialchars($forum['description']); ?></p>
                    </div>

                    <h2>Discussions</h2>
                    <div class="comments">
                        <?php foreach ($comments as $comment): ?>
                            <div class="card mb-3 p-3 <?php echo ($comment['user_id'] == $userId) ? 'bg-primary text-white' : 'bg-light'; ?>">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <img src="<?php echo htmlspecialchars($comment['profile_picture']); ?>" alt="Profile Picture" class="rounded-circle" width="40" height="40">
                                        <strong><a href="index.php?page=user/public_profile.php&uuid=<?php echo htmlspecialchars($comment['uuid']); ?>" style="color: black; text-decoration: none;"><?php echo htmlspecialchars($comment['username']); ?></a></strong>
                                    </div>
                                    <small>Posted on <?php echo htmlspecialchars($comment['comment_created_at']); ?></small>
                                </div>
                                <p><?php echo htmlspecialchars($comment['content']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <h3>Add a Comment</h3>
                    <form method="POST" class="bg-white p-4 rounded shadow-sm">
                        <div class="form-group">
                            <textarea class="form-control" name="content" rows="4" required></textarea>
                        </div>
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <button type="submit" class="btn btn-success">Post Comment</button>
                    </form>

                    <div class="mt-4">
                        <a href="index.php?page=volunteer/list_forum.php&uuid=<?php echo $associationUUID; ?>" class="btn btn-secondary">Back to Forums</a>
                    </div>
                </div>
            </div>
            <?php include_once 'footer.php'; ?>
        </div>
    </div>
</body>

<?php include_once 'scripts.php'; ?>

</html>
