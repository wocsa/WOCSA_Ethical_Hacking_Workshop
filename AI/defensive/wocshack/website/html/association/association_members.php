<?php
// Load the Association model
require_once 'models/Association.php';
require_once 'models/User.php';
require 'tools/csrf.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=user/login.php");
    exit;
}

// Check if an association UUID is provided
if (!isset($_GET['uuid'])) {
    echo "No association specified.";
    exit;
}

$associationUUID = $_GET['uuid'];
$userModel = new User($pdo);
$userId = $_SESSION['user_id'];
$isAdmin = $userModel->isAdmin($userId);
$isAdminInAnyAssociation = $userModel->isAdminInAnyAssociation($userId);
$user = $userModel->getUserById($userId); // Get the user details
$username = $user['username'];
$profilePicture = $user['profile_picture'] ? $user['profile_picture'] : 'uploads/default.jpg'; // Default image if no profile picture
$csrf = new csrf();
$csrfToken = $csrf->generateCSRFToken(); // Generate and store CSRF token

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
$isAdminAssociation = $userModel->isAdminInAssociation($userId, $associationId);

// Check if the user is an admin of this association
$userAssociations = $associationModel->getAssociationsByRole($userId);
$isAdminInAnyAssociation = in_array($associationId, array_column($userAssociations['admin'], 'id'));

// Retrieve association members
$members = $associationModel->getMembersByAssociationId($associationId);

// Handle member removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_member'])) {
    $memberId = $_POST['user_id'];

    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    // Remove the member
    $associationModel->deleteMember($associationId, $memberId);

    // Refresh the member list after removal
    $members = $associationModel->getMembersByAssociationId($associationId);
    $successMessage = "Member successfully removed.";
}

// Handle member export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    if ($action === 'export') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="members_' . $associationUUID . '.csv"');
        $associationModel->exportMembersToCSV($association['id']);
        exit;
    }

    // Handle member import
    if ($action === 'import' && isset($_FILES['csv_file'])) {
        $uploadedFile = $_FILES['csv_file'];

        // Use the new method to handle file upload and import
        $result = $associationModel->handleFileUploadAndImport($association['id'], $uploadedFile);

        if ($result['success']) {
            $successMessage = $result['message'];
            $addedCount = $result['added_count'];
            $notAddedUsers = $result['not_added_users'];
            $notFoundEmails = $result['not_found_emails'];
        } else {
            $errorMessage = $result['message'];
        }

        // Refresh the member list
        $members = $associationModel->getMembersByAssociationId($association['id']);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Association Members: <?php echo htmlspecialchars($association['name']); ?></title>
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
                        <h1 class="h3 mb-0 text-gray-800">Association Members: <?php echo htmlspecialchars($association['name']); ?></h1>
                    </div>

                    <?php if (isset($successMessage)): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo $successMessage; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($errorMessage)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $errorMessage; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($addedCount)): ?>
                        <div class="alert alert-info" role="alert">
                            <?php echo $addedCount; ?> users were added successfully.
                        </div>
                    <?php endif; ?>

                    <?php if (isset($notAddedUsers) && !empty($notAddedUsers)): ?>
                        <div class="alert alert-warning" role="alert">
                            <strong>Users not added:</strong>
                            <ul>
                                <?php foreach ($notAddedUsers as $user): ?>
                                    <li><?php echo htmlspecialchars($user['email']); ?> - Reason: <?php echo htmlspecialchars($user['reason']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($notFoundEmails) && !empty($notFoundEmails)): ?>
                        <div class="alert alert-warning" role="alert">
                            <strong>Emails not found in the database:</strong>
                            <ul>
                                <?php foreach ($notFoundEmails as $email): ?>
                                    <li><?php echo htmlspecialchars($email); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($members)): ?>
                        <ul class="list-group mb-4">
                            <?php foreach ($members as $member): ?>
                                <li class="list-group-item">
                                    <strong>Username:</strong> <?php echo htmlspecialchars($member['username']); ?><br>
                                    <strong>UUID:</strong> <?php echo htmlspecialchars($member['uuid']); ?>
                                    <?php if ($isAdminAssociation && $member['user_id'] !== $_SESSION['user_id']): ?>
                                        <form method="POST" class="d-inline-block float-right">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($member['user_id']); ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <button type="submit" name="remove_member" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to remove this member?');">Remove</button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No members found.</p>
                    <?php endif; ?>

                    <?php if ($isAdminAssociation): ?>
                        <h2 class="mt-4">Manage Members</h2>
                        <form method="POST" action="index.php?page=association/association_members.php&uuid=<?php echo htmlspecialchars($associationUUID); ?>" class="mb-2">
                            <input type="hidden" name="action" value="export">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <button type="submit" class="btn btn-success">Export Members</button>
                        </form>

                        <form method="POST" action="index.php?page=association/association_members.php&uuid=<?php echo htmlspecialchars($associationUUID); ?>" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="import">
                            <div class="form-group">
                                <input type="file" name="csv_file" accept=".csv" class="form-control-file" required>
                            </div>
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <button type="submit" class="btn btn-primary">Import Members</button>
                        </form>
                    <?php endif; ?>

                    <div class="mt-4">
                        <a href="index.php?page=association/profile.php&uuid=<?php echo htmlspecialchars($associationUUID); ?>" class="btn btn-secondary">Back to Administration</a>
                    </div>
                </div>
            </div>
            <?php include_once 'footer.php'; ?>
        </div>
    </div>
</body>

<?php include_once 'scripts.php'; ?>

</html>