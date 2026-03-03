<?php

// Load the Association model
require_once 'models/Association.php';
require_once 'models/User.php';
require 'tools/csrf.php';

// Create an instance of the Association class
$associationModel = new Association($pdo); // Use the already configured PDO connection

// Check if the user is logged in
$isUserLoggedIn = isset($_SESSION['user_id']);
$userId = $isUserLoggedIn ? $_SESSION['user_id'] : null;

// Check if the user is logged in and fetch associations where the user is an admin
if ($isUserLoggedIn) {
    $userModel = new User($pdo);
    $userId = $_SESSION['user_id'];
    $isAdmin = $userModel->isAdmin($userId);
    $isAdminInAnyAssociation = $userModel->isAdminInAnyAssociation($userId);
    $user = $userModel->getUserById($userId); // Get the user details
    $username = $user['username'];
    $profilePicture = $user['profile_picture'] ? $user['profile_picture'] : 'uploads/default.jpg'; // Default image if no profile picture
    $csrf = new csrf();
    $csrfToken = $csrf->generateCSRFToken(); // Generate and store CSRF token
}

// Handle the "Join" form
if ($isUserLoggedIn && isset($_POST['join_association_uuid'])) {
    $associationUUID = $_POST['join_association_uuid'];

    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    $associationId = $associationModel->getAssociationByUUID($associationUUID)['id'];

    try {
        // Use the addMembers method to add the user to the association
        $associationModel->addMembers($associationId, [$userId]);

        // Refresh the page after adding
        header("Location: index.php?page=association/list.php");
        exit;
    } catch (Exception $e) {
        // Display an error message in case of a problem
        $errorMessage = "Error adding to the association: " . $e->getMessage();
    }
}

// Handle the search query
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Retrieve all associations with search functionality
if (!empty($searchQuery)) {
    try {
        $associations = $associationModel->searchAssociations($searchQuery);
        $allAssociations = $associations;
    } catch (Exception $e) {
        $allAssociations = $associationModel->getAllAssociations();
    }
} else {
    $allAssociations = $associationModel->getAllAssociations();
}

// Retrieve the associations of the logged-in user
$userAssociations = ['admin' => [], 'member' => []];
$userAssociationIds = [];
if ($isUserLoggedIn) {
    $userAssociations = $associationModel->getAssociationsByRole($userId);
    $userAssociationIds = array_merge(
        array_column($userAssociations['admin'], 'id'),
        array_column($userAssociations['member'], 'id')
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List of Associations</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Available Associations</h1>
                    </div>

                    <!-- Search Form -->
                    <form method="GET" action="index.php" class="mb-4">
                        <input type="hidden" name="page" value="association/list.php"> <!-- Garder la page actuelle -->
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search associations..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">Search</button>
                            </div>
                        </div>
                    </form>

                    <?php if (!empty($errorMessage)): ?>
                        <p class="text-danger font-weight-bold"> <?php echo htmlspecialchars($errorMessage); ?></p>
                    <?php endif; ?>

                    <!-- Search Results Section -->
                    <h2 class="h4 mb-3">
                        <?php echo !empty($searchQuery) ? 'Search Results for "' . htmlspecialchars($searchQuery) . '"' : 'All Associations'; ?>
                    </h2>

                    <ul class="list-group mb-4">
                        <?php if (!empty($searchQuery)): ?>
                            <!-- Afficher les résultats de recherche -->
                            <?php if (!empty($allAssociations)): ?>
                                <?php foreach ($allAssociations as $association): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <a href="index.php?page=association/public_profile.php&uuid=<?php echo $association['uuid']; ?>">
                                            <?php echo htmlspecialchars($association['name']); ?>
                                        </a>
                                        <?php if ($isUserLoggedIn && !in_array($association['id'], $userAssociationIds)): ?>
                                            <form method="POST" class="d-inline-block">
                                                <input type="hidden" name="join_association_uuid" value="<?php echo $association['uuid']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <button type="submit" class="btn btn-success btn-sm">Join</button>
                                            </form>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No associations found for your search.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- Afficher toutes les associations -->
                            <?php if (!empty($allAssociations)): ?>
                                <?php foreach ($allAssociations as $association): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <a href="index.php?page=association/public_profile.php&uuid=<?php echo $association['uuid']; ?>">
                                            <?php echo htmlspecialchars($association['name']); ?>
                                        </a>
                                        <?php if ($isUserLoggedIn && !in_array($association['id'], $userAssociationIds)): ?>
                                            <form method="POST" class="d-inline-block">
                                                <input type="hidden" name="join_association_uuid" value="<?php echo $association['uuid']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <button type="submit" class="btn btn-success btn-sm">Join</button>
                                            </form>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No associations available.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>

                    <?php if ($isUserLoggedIn): ?>
                        <h2 class="h4 mb-3">My Associations</h2>
                        <h3 class="h5 text-primary">Associations where you are an admin</h3>
                        <?php if (!empty($userAssociations['admin'])): ?>
                            <ul class="list-group mb-3">
                                <?php foreach ($userAssociations['admin'] as $association): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <a href="index.php?page=association/public_profile.php&uuid=<?php echo $association['uuid']; ?>">
                                            <?php echo htmlspecialchars($association['name']); ?>
                                        </a>
                                        <a href="index.php?page=association/profile.php&uuid=<?php echo $association['uuid']; ?>" class="btn btn-warning btn-sm">Administer</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>You are not an admin of any association.</p>
                        <?php endif; ?>

                        <h3 class="h5 text-success">Associations where you are a member</h3>
                        <?php if (!empty($userAssociations['member'])): ?>
                            <ul class="list-group">
                                <?php foreach ($userAssociations['member'] as $association): ?>
                                    <li class="list-group-item">
                                        <a href="index.php?page=association/public_profile.php&uuid=<?php echo $association['uuid']; ?>">
                                            <?php echo htmlspecialchars($association['name']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>You are not a member of any association.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><a href="index.php?page=user/login.php" class="btn btn-primary">Log in</a> to see your associations.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php include_once 'footer.php'; ?>
        </div>
    </div>
</body>

<?php include_once 'scripts.php'; ?>

</html>