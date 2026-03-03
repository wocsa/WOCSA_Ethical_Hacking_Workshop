<?php

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if the user is not logged in
    header('Location: ?page=user/login.php');
    exit();
}

require_once 'models/Association.php'; // Include the Association class here
require_once 'models/User.php'; // Include the User class here
require 'tools/csrf.php';

$userModel = new User($pdo);
$userId = $_SESSION['user_id'];
$isAdmin = $userModel->isAdmin($userId);
$isAdminInAnyAssociation = $userModel->isAdminInAnyAssociation($userId);
$user = $userModel->getUserById($userId); // Get the user details
$username = $user['username'];
$profilePicture = $user['profile_picture'] ? $user['profile_picture'] : 'uploads/default.jpg'; // Default image if no profile picture
$csrf = new csrf();
$csrfToken = $csrf->generateCSRFToken(); // Generate and store CSRF token


// Variables for error and success messages
$errorMsg = '';
$successMsg = '';

$associationModel = new Association($pdo);
$name = ''; // Initialize name
$description = ''; // Initialize description
$address = ''; // Initialize address
$contactEmail = ''; // Initialize contactEmail

// Handle the association creation form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $contactEmail = trim($_POST['contactEmail']);
    $userId = $_SESSION['user_id']; // Get the ID of the logged-in user

    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    if (empty($name) || empty($description) || empty($address) || empty($contactEmail)) {
        $errorMsg = "All fields are required.";
    } else if (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Please enter a valid email address.";
    } else {
        try {
            $associationId = $associationModel->createAssociation($name, $description, $address, $contactEmail, $userId);

            if ($associationId) {
                // Récupérer l'UUID de l'association ... (sans url encode, vu que je l'utilise dans le header :D)
                $association = $associationModel->getAssociationById($associationId);
                $associationUUID = $association['uuid'];

                // Redirection vers la page de l'association avec le uuid :D !!
                header("Location: index.php?page=association/profile.php&uuid=" . urlencode($associationUUID));
                exit;
            } else {
                $errorMsg = "Error while creating the association.";
            }
        } catch (Exception $e) {
            $errorMsg = "Error while creating the association: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an Association</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Create an Association</h1>
                    </div>

                    <?php if (!empty($errorMsg)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $errorMsg; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($successMsg)): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo $successMsg; ?>
                        </div>
                    <?php endif; ?>

                    <form action="?page=association/create_association.php" method="POST" class="bg-light p-4 rounded shadow">
                        <div class="form-group">
                            <label for="name">Association Name:</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="description">Description:</label>
                            <textarea id="description" name="description" class="form-control"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="address">Address:</label>
                            <input type="text" id="address" name="address" class="form-control" value="<?php echo isset($address) ? htmlspecialchars($address) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="contactEmail">Contact Email:</label>
                            <input type="email" id="contactEmail" name="contactEmail" class="form-control" value="<?php echo isset($contactEmail) ? htmlspecialchars($contactEmail) : ''; ?>">
                        </div>

                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                        <button type="submit" class="btn btn-primary">Create Association</button>
                    </form>
                </div>
            </div>
            <?php include_once 'footer.php'; ?>
        </div>
    </div>
</body>

<?php include_once 'scripts.php'; ?>

</html>
