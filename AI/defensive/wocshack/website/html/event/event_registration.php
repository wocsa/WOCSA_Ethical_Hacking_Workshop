<?php
require_once 'models/Event.php';
require_once 'models/User.php';
require_once 'models/Association.php';
require 'tools/csrf.php';

$eventModel = new Event($pdo);
$userModel = new User($pdo);
$associationModel = new Association($pdo);
$error_message = '';
$success_message = '';
$csrf = new csrf();
$csrfToken = $csrf->generateCSRFToken(); // Generate and store CSRF token

// Check if the user is logged in and get the user ID
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to create events.");
} else {
    $userId = $_SESSION['user_id'];
    $isAdmin = $userModel->isAdmin($userId);
    $isAdminInAnyAssociation = $userModel->isAdminInAnyAssociation($userId);
    $user = $userModel->getUserById($userId); // Get the user details
    $username = htmlspecialchars($user['username']);
    $profilePicture = $user['profile_picture'] ? htmlspecialchars($user['profile_picture']) : 'uploads/default.jpg'; // Default image if no profile picture
}

// Fetch associations where the user is an admin
$adminAssociations = $associationModel->getUserAssociationsWhereAdmin($userId);

// Handle POST request for event creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['association_id'])) {
    // Validate CSRF token
    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    $associationId = intval($_POST['association_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $eventDate = $_POST['event_date'];

    // Check if the user is an admin of the submitted association
    $isValidAssociation = false;
    foreach ($adminAssociations as $association) {
        if ($association['id'] == $associationId) {
            $isValidAssociation = true;
            break;
        }
    }

    if (!$isValidAssociation) {
        $error_message = "You are not authorized to create events for this association.";
    } else {
        // Initialize picture variable
        $uploadResult = '';
        $errorUpload = 0;

        // Handle file upload for event picture
        if (!empty($_FILES['picture']['name'])) {
            if ($_FILES['picture']['error'] == 1) {
                $error_message = "Error in image upload. Try another image.";
                $uploadResult = "Failed to upload file. Please try again.";
                $errorUpload = 1;
            } else {
                $targetDir = "uploads/";
                $uploadResult = $userModel->validateAndUploadProfilePicture($_FILES['picture'], $targetDir);
            }
        }

        if ($errorUpload != 1) {
            if (str_starts_with($uploadResult, 'Uploads')) {
                if ($eventModel->createEvent($name, $description, $eventDate, $uploadResult, $associationId)) {
                    $success_message = "Event created successfully.";
                } else {
                    $error_message = "Failed to create event.";
                }
            } else {
                $error_message = $uploadResult;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Create Event</h1>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Event Details</h6>
                        </div>
                        <div class="card-body">
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
                            <form method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="association_id">Select Association:</label>
                                    <select name="association_id" class="form-control" required>
                                        <?php foreach ($adminAssociations as $association): ?>
                                            <option value="<?= htmlspecialchars($association['id']) ?>">
                                                <?= htmlspecialchars($association['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="name">Event Name:</label>
                                    <input type="text" name="name" class="form-control" placeholder="Event Name" required>
                                </div>

                                <div class="form-group">
                                    <label for="description">Event Description:</label>
                                    <textarea name="description" class="form-control" rows="3" placeholder="Event Description" required></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="event_date">Event Date and Time:</label>
                                    <input type="datetime-local" name="event_date" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label for="picture">Event Picture:</label>
                                    <input type="file" name="picture" class="form-control-file" accept="image/*" required>
                                </div>

                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                                <button type="submit" class="btn btn-primary">Create Event</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once 'footer.php'; ?>
        </div>
    </div>

    <?php include_once 'scripts.php'; ?>
</body>

</html>
