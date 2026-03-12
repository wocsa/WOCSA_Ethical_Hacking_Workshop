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
    $associationId = intval($_POST['association_id']);
    if ($associationModel->isUserInAssociation($userId, $associationId) || $userModel->isAdminInAssociation($userId, $associationId)) {
        $pastEvents = $eventModel->getPastEventsByAssociation($associationId);
    } else {
        $error_message = 'You do not have permission to view events for this association.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback'])) {
    $eventId = intval($_POST['event_id']);
    $feedback = $_POST['feedback'];
    $event = $eventModel->getEventById($eventId);
    $associationId = $event['association_id'];

    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    if ($associationModel->isUserInAssociation($userId, $associationId) || $userModel->isAdminInAssociation($userId, $associationId)) {
        if ($eventModel->addFeedback($eventId, $userId, $feedback)) {
            $success_message = 'Feedback submitted successfully.';
        } else {
            $error_message = 'Failed to submit feedback.';
        }
    } else {
        $error_message = 'You do not have permission to submit feedback for this event.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Feedback</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Event Feedback</h1>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php elseif ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Choose an Association to Give Feedback</h6>
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
                                <button type="submit" class="btn btn-primary">View Past Events</button>
                            </form>
                        </div>
                    </div>

                    <?php if (isset($pastEvents)): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Past Events</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($pastEvents as $event): ?>
                                        <li class="list-group-item">
                                            <strong><?= htmlspecialchars($event['name']) ?></strong>
                                            <p><?= htmlspecialchars($event['description']) ?></p>
                                            <p><?= $event['event_date'] ?></p>
                                            <?php if ($event['picture']): ?>
                                                <img src="<?= htmlspecialchars($event['picture']) ?>" alt="Event Image" class="img-fluid mb-3">
                                            <?php endif; ?>

                                            <h3 class="mt-3">Feedbacks</h3>
                                            <ul class="list-group list-group-flush">
                                                <?php
                                                $feedbacks = $eventModel->getEventFeedbacks($event['id']);
                                                foreach ($feedbacks as $feedback): ?>
                                                    <li class="list-group-item d-flex align-items-start">
                                                        <img src="<?php echo htmlspecialchars($feedback['profile_picture']); ?>" alt="avatar" style="width: 100px; height: 100px; object-fit: cover; margin-right: 15px;">
                                                        <div>
                                                            <strong>Username:</strong> <a href="index.php?page=user/public_profile.php&uuid=<?php echo htmlspecialchars($feedback['uuid']); ?>"><?= htmlspecialchars($feedback['username']) ?></a><br>
                                                            <strong>Message:</strong> <?= htmlspecialchars($feedback['feedback']) ?>
                                                        </div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>

                                            <form method="POST" class="mt-3">
                                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                                <div class="form-group">
                                                    <textarea name="feedback" class="form-control" rows="3" placeholder="Your feedback" required></textarea>
                                                </div>
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <button type="submit" class="btn btn-primary">Submit Feedback</button>
                                            </form>
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
