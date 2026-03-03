<?php
// Load the Association model
require_once 'models/Association.php';
// Load the User model
require_once 'models/User.php';

// Check if an association UUID is provided
if (!isset($_GET['uuid'])) {
    echo "No association specified.";
    exit;
}

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

$associationUUID = $_GET['uuid'];

// Create an instance of the Association class
$associationModel = new Association($pdo);

// Retrieve the association's information
$association = $associationModel->getAssociationByUUID($associationUUID);

// Check if the association exists
if (!$association) {
    echo "The specified association does not exist.";
    exit;
}

// Retrieve the members of the association for public display (limited if necessary)
$members = $associationModel->getMembersByAssociationId($association['id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Profile of the Association <?php echo htmlspecialchars($association['name']); ?></title>
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
                        <h1 class="h3 mb-0 text-gray-800">Public Profile of the Association</h1>
                    </div>

                    <div class="card shadow mb-4 p-4">
                        <h2 class="h4 mb-3"> <?php echo htmlspecialchars($association['name']); ?></h2>
                        <p><strong>Description:</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($association['description'])); ?></p>

                        <p><strong>Address:</strong> <?php echo htmlspecialchars($association['address']); ?></p>
                        <p><strong>Contact Email:</strong> <a href="mailto:<?php echo htmlspecialchars($association['contact_email']); ?>"> <?php echo htmlspecialchars($association['contact_email']); ?></a></p>
                    </div>

                    <div class="card shadow mb-4 p-4">
                        <h3 class="h5 mb-3">Members</h3>
                        <?php if (!empty($members)): ?>
                            <ul class="list-group">
                                <?php foreach ($members as $member): ?>
                                    <li class="list-group-item d-flex align-items-start">
                                        <img src="<?php echo htmlspecialchars($member['profile_picture']); ?>" alt="avatar" style="width: 100px; height: 100px; object-fit: cover; margin-right: 15px;">
                                        
                                        <div>
                                            <strong>Username:</strong> <?php echo htmlspecialchars($member['username']); ?><br>
                                            <strong>UUID:</strong> <?php echo htmlspecialchars($member['uuid']); ?><br>
                                            <strong>Link:</strong> <a href="index.php?page=user/public_profile.php&uuid=<?php echo htmlspecialchars($member['uuid']); ?>">Profile</a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No public members for this association.</p>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between mb-4">
                        <a href="index.php?page=volunteer/list_forum.php&uuid=<?php echo htmlspecialchars($associationUUID); ?>" class="btn btn-primary mb-2 mb-md-0">View Forum List</a>
                        <a href="index.php" class="btn btn-secondary mb-2 mb-md-0">Back to Home</a>
                    </div>
                </div>
            </div>
            <?php include_once 'footer.php'; ?>
        </div>
    </div>
</body>


<?php include_once 'scripts.php'; ?>

</html>
