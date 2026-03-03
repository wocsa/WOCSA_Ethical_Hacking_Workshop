<?php
require_once 'models/Forum.php';
require_once 'models/Association.php';
require_once 'models/User.php';
require 'tools/csrf.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=user/login.php");
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

// Check if the user submitted the form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $xmlContent = file_get_contents("php://input");
    $xml = simplexml_load_string($xmlContent,  'SimpleXMLElement', LIBXML_NOENT);

    if (!$xml) {
        die('Invalid XML content');
    }

    $associationUUID = (string) $xml->association_uuid;
    $title = (string) $xml->title;
    $description = (string) $xml->description;
    $csrfToken = (string) $xml->csrf_token;

    if (!$csrf->verifyCSRFToken($csrfToken)) {
        die('CSRF token validation failed');
    }

    // Create an instance of the Forum model
    $forumModel = new Forum($pdo);
    $associationModel = new Association($pdo);

    $associationId = $associationModel->getAssociationByUUID($associationUUID)['id'];

    // Validate the data and create the forum
    if (!empty($associationId) && !empty($title)) {
        $forumModel->createForum($associationId, $title, $description);
        header('Location: index.php?page=volunteer/list_forum.php&uuid=' . $associationUUID);
        exit;
    } else {
        $error = "Please fill out all required fields.";
    }
}

// Check if an association is specified
if (!isset($_GET['uuid'])) {
    echo "No association specified.";
    exit;
}

$associationUUID = $_GET['uuid'];
$associationModel = new Association($pdo);

// Retrieve the association's information
$association = $associationModel->getAssociationByUUID($associationUUID);

if (!$association) {
    echo "Association not found.";
    exit;
}

$associationId = $association['id'];
?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create a Forum</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Create a New Forum</h1>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form id="forumForm" class="bg-white p-4 rounded shadow-sm">
                        <input type="hidden" name="association_uuid" value="<?php echo $associationUUID; ?>">

                        <div class="form-group">
                            <label for="title">Forum Title:</label>
                            <input type="text" class="form-control" name="title" id="title" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description:</label>
                            <textarea class="form-control" name="description" id="description"></textarea>
                        </div>

                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                        <button type="button" onclick="submitForm()" class="btn btn-primary">Create</button>
                    </form>

                    <div class="mt-4">
                        <a href="index.php?page=volunteer/list_forum.php&uuid=<?php echo $associationUUID; ?>" class="btn btn-secondary">Back to Forum List</a>
                    </div>
                </div>
            </div>
            <?php include_once 'footer.php'; ?>
        </div>
    </div>

    <script>
        function submitForm() {
            // Get form values
            const associationUUID = document.querySelector('input[name="association_uuid"]').value;
            const title = document.querySelector('input[name="title"]').value;
            const description = document.querySelector('textarea[name="description"]').value;
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;

            // Create XML content
            const xmlContent = `
                <forum>
                    <association_uuid>${associationUUID}</association_uuid>
                    <title>${title}</title>
                    <description>${description}</description>
                    <csrf_token>${csrfToken}</csrf_token>
                </forum>
            `;

            // Create a new XMLHttpRequest
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "index.php?page=volunteer/create_forum.php", true);
            xhr.setRequestHeader("Content-Type", "application/xml");

            // Send the XML content
            xhr.send(xmlContent);

            // Handle the response
            xhr.onload = function () {
                if (xhr.status === 200) {
                    // Redirect to the forum list page on success
                    window.location.href = "index.php?page=volunteer/list_forum.php&uuid=<?php echo $associationUUID; ?>";
                } else {
                    // Display an error message
                    alert("An error occurred while creating the forum.");
                }
            };
        }
    </script>

    <?php include_once 'scripts.php'; ?>
</body>

</html>
