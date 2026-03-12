<?php
require_once 'models/Event.php';
require_once 'models/User.php';
require_once 'models/Association.php';
require 'tools/csrf.php';

$eventModel = new Event($pdo);
$userModel = new User($pdo);
$associationModel = new Association($pdo);

if (!isset($_SESSION['user_id'])) {
    header('Location: ?page=user/login.php');
    exit();
} else {
    $userModel = new User($pdo);
    $userId = $_SESSION['user_id'];
    $isAdmin = $userModel->isAdmin($userId);
    $isAdminInAnyAssociation = $userModel->isAdminInAnyAssociation($userId);
    $user = $userModel->getUserById($userId);
    $username = $user['username'];
    $profilePicture = $user['profile_picture'] ? $user['profile_picture'] : 'uploads/default.jpg';
    $userData = $userModel->getUserById($userId);
    $csrf = new csrf();
    $csrfToken = $csrf->generateCSRFToken();
}

$associations = $associationModel->getUserAssociations($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['association_id'])) {
    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    $associationId = intval($_POST['association_id']);
    $userAssociationIds = array_column($associations, 'id');
    if (!in_array($associationId, $userAssociationIds)) {
        die('Access denied: You are not part of this association.');
    }

    $events = $eventModel->getEventsByAssociation($associationId);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Calendar</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Event Calendar</h1>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Choose an Association to See Events</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="association_id">Select Association:</label>
                                    <select name="association_id" class="form-control" required>
                                        <?php foreach ($associations as $association): ?>
                                            <option value="<?= htmlspecialchars($association['id']) ?>">
                                                <?= htmlspecialchars($association['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <button type="submit" class="btn btn-primary">View Events</button>
                            </form>
                        </div>
                    </div>

                    <?php if (isset($events)): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Upcoming Events</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($events as $event): ?>
                                        <li class="list-group-item">
                                            <strong><?= htmlspecialchars($event['name']) ?></strong>
                                            <p><?= htmlspecialchars($event['description']) ?></p>
                                            <p><?= $event['event_date'] ?></p>
                                            <?php if ($event['picture']): ?>
                                                <img src="<?= htmlspecialchars($event['picture']) ?>" alt="Event Image" class="img-fluid mb-3">
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php include_once 'footer.php'; ?>
        </div>
    </div>
</body>

<?php include_once 'scripts.php'; ?>

</html>
