<?php
require_once 'models/Association.php';
require_once 'models/User.php';

$associations = [];

if (isset($_SESSION['user_id'])) {
    // Retrieve the associations of the logged-in user
    $userId = $_SESSION['user_id'];
    $associationModel = new Association($pdo);
    $associations = $associationModel->getAssociationsByRole($userId);
    $userModel = new User($pdo);
    $isAdmin = $userModel->isAdmin($userId);
    $isAdminInAnyAssociation = $userModel->isAdminInAnyAssociation($userId);
    $user = $userModel->getUserById($userId); // Get the user details
    $username = $user['username'];
    $profilePicture = $user['profile_picture'] ? $user['profile_picture'] : 'uploads/default.jpg'; // Default image if no profile picture
} else {
    // Redirect to the login page if the user is not logged in
    header('Location: index.php?page=user/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Associations</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Associations Management</h1>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Associations where you are an Admin</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($associations['admin'])): ?>
                                        <ul class="list-group">
                                            <?php foreach ($associations['admin'] as $association): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <a href="index.php?page=association/public_profile.php&uuid=<?php echo $association['uuid']; ?>" class="text-primary">
                                                        <?php echo htmlspecialchars($association['name']); ?>
                                                    </a>
                                                    <a href="index.php?page=association/profile.php&uuid=<?php echo $association['uuid']; ?>" class="btn btn-danger btn-sm">Administration</a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-muted">You are not an admin of any association.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Associations where you are a Member</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($associations['member'])): ?>
                                        <ul class="list-group">
                                            <?php foreach ($associations['member'] as $association): ?>
                                                <li class="list-group-item">
                                                    <a href="index.php?page=association/public_profile.php&uuid=<?php echo $association['uuid']; ?>" class="text-primary">
                                                        <?php echo htmlspecialchars($association['name']); ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-muted">You are not a member of any association.</p>
                                    <?php endif; ?>
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