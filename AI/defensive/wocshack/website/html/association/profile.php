<?php

// Load the Association model
require_once 'models/Association.php';
// Load the User model
require_once 'models/User.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=user/login.php");
    exit;
} else {
    $userModel = new User($pdo);
    $userId = $_SESSION['user_id'];
    $isAdmin = $userModel->isAdmin($userId);
    $isAdminInAnyAssociation = $userModel->isAdminInAnyAssociation($userId);
    $user = $userModel->getUserById($userId); // Get the user details
    $username = $user['username'];
    $profilePicture = $user['profile_picture'] ? $user['profile_picture'] : 'uploads/default.jpg'; // Default image if no profile picture
}

// Check if an association ID is provided
if (!isset($_GET['uuid'])) {
    echo "No association specified.";
    exit;
}

$associationUUID = $_GET['uuid'];

// Create an instance of the Association class
$associationModel = new Association($pdo);

// Retrieve association information
$association = $associationModel->getAssociationByUUID($associationUUID);

// Check if the association exists
if (!$association) {
    echo "The specified association does not exist.";
    exit;
}

$associationId = $association['id'];

// Check if the user is an admin of this association
$userAssociations = $associationModel->getAssociationsByRole($userId);
$isAdminInAnyAssociation = in_array($associationId, array_column($userAssociations['admin'], 'id'));

if (!$isAdminInAnyAssociation) {
    echo "You do not have admin rights for this association.";
    exit;
}

// Handle association deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_association'])) {
    // Delete the association
    $associationModel->deleteAssociation($associationUUID);

    // Redirect the user after deletion
    header("Location: ?page=association/list.php");
    exit;
}

// Handle form submission to save updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $address = $_POST['address'];
    $contactEmail = $_POST['contact_email'];

    // Update association information
    $associationModel->updateAssociation($associationUUID, $name, $description, $address, $contactEmail);

    // Refresh association data after the update
    $association = $associationModel->getAssociationByUUID($associationUUID);
    $successMessage = "Changes saved successfully.";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Association Administration <?php echo htmlspecialchars($association['name']); ?></title>
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
                        <h1 class="h3 mb-0 text-gray-800">Association Administration: <?php echo htmlspecialchars($association['name']); ?></h1>
                    </div>

                    <?php if (isset($successMessage)): ?>
                        <div class="alert alert-success" role="alert"> <?php echo $successMessage; ?></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4 p-4">
                        <h2 class="h4 mb-3">Edit General Information</h2>
                        <form method="POST" class="mb-4">
                            <div class="form-group">
                                <label for="name">Name:</label>
                                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($association['name']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="description">Description:</label>
                                <textarea id="description" name="description" class="form-control" rows="4"><?php echo htmlspecialchars($association['description']); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="address">Address:</label>
                                <input type="text" id="address" name="address" class="form-control" value="<?php echo htmlspecialchars($association['address']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="contact_email">Contact Email:</label>
                                <input type="email" id="contact_email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($association['contact_email']); ?>">
                            </div>

                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between mb-4">
                        <a href="index.php?page=association/association_members.php&uuid=<?php echo htmlspecialchars($associationUUID); ?>" class="btn btn-info mb-2 mb-md-0">View Members</a>
                        <a href="index.php?page=volunteer/list_forum.php&uuid=<?php echo htmlspecialchars($associationUUID); ?>" class="btn btn-warning mb-2 mb-md-0">View Forums</a>
                    </div>

                    <div class="card bg-danger text-white shadow p-4">
                        <h2 class="h5 mb-3">Delete Association</h2>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this association?');">
                            <input type="hidden" name="delete_association" value="true">
                            <button type="submit" class="btn btn-light text-danger">Delete Association</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php include_once 'footer.php'; ?>
        </div>
    </div>
</body>


<?php include_once 'scripts.php'; ?>

</html>