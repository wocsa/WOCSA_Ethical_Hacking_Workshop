<?php
require_once 'models/Ticket.php'; // Include the Ticket model
require_once 'models/User.php'; // Include the User model
require_once 'models/TicketComment.php'; // Include the TicketComment model
require 'tools/csrf.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if the user is not logged in
    header('Location: ?page=user/login.php');
    exit();
} else {
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

$ticketModel = new Ticket($pdo);
$ticketCommentModel = new TicketComment($pdo); // Assuming you have a TicketComment model to handle ticket comments
$response = '';

// Fetch user data, including admin status
$userData = $userModel->getUserById($userId);
$isAdmin = $userData['is_admin'];

// Handle ticket submission (for non-admin users)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket']) && !$isAdmin) {

    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $url = trim($_POST['url']); // Get the URL entered by the user
    $response = $ticketModel->createTicket($userId, $title, $description, $url) ?
        ['status' => 'success', 'message' => 'Ticket created successfully.'] :
        ['status' => 'error', 'message' => 'Failed to create ticket.'];
}

// Handle ticket update (for admin users)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ticket']) && $isAdmin) {

    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    $ticketId = intval($_POST['ticket_id']);
    $status = $_POST['status'];
    $responseText = trim($_POST['response']);
    $response = $ticketModel->updateTicket($ticketId, $status, $responseText, $userId) ?
        ['status' => 'success', 'message' => 'Ticket updated successfully.'] :
        ['status' => 'error', 'message' => 'Failed to update ticket.'];
}

// Handle ticket comment submission (for both users and admins)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket_comment'])) {

    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    $ticketId = intval($_POST['ticket_id']);
    $commentText = trim($_POST['ticket_comment']);
    $response = $ticketCommentModel->addTicketComment($ticketId, $userId, $commentText) ?
        ['status' => 'success', 'message' => 'Comment added successfully.'] :
        ['status' => 'error', 'message' => 'Failed to add comment.'];
}

// Get user's tickets (for non-admin users) or all tickets (for admins)
if ($isAdmin) {
    $tickets = $ticketModel->getAllTickets();
} else {
    $tickets = $ticketModel->getUserTickets($userId);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Support Tickets</h1>
                    </div>

                    <!-- Display response messages -->
                    <?php if ($response): ?>
                        <div class="alert alert-<?= $response['status'] ?>">
                            <?= $response['message'] ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$isAdmin): ?>
                        <!-- Ticket creation form for non-admin users -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Create a Ticket</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="form-group">
                                        <input type="text" name="title" class="form-control" placeholder="Ticket Title" required>
                                    </div>
                                    <div class="form-group">
                                        <textarea name="description" class="form-control" rows="3" placeholder="Ticket Description" required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <input type="url" name="url" class="form-control" placeholder="Optional URL (e.g. link to the issue)" pattern="https?://.*" />
                                    </div>
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <button type="submit" name="submit_ticket" class="btn btn-primary">Create Ticket</button>
                                </form>
                            </div>
                        </div>

                        <!-- Display user's tickets -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Your Tickets</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($tickets)): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($tickets as $ticket): ?>
                                            <li class="list-group-item">
                                                <strong><?= htmlspecialchars($ticket['title']) ?></strong> - Status: <?= htmlspecialchars($ticket['status']) ?>
                                                <br>Description: <?= htmlspecialchars($ticket['description']) ?>

                                                <!-- Display the URL if provided -->
                                                <?php if (!empty($ticket['url'])): ?>
                                                    <br><a href="<?= htmlspecialchars($ticket['url']) ?>" target="_blank">Link to Issue</a>
                                                <?php endif; ?>

                                                <!-- Display ticket comments -->
                                                <h3 class="mt-3">Comments:</h3>
                                                <ul class="list-group list-group-flush">
                                                    <?php $ticketComments = $ticketCommentModel->getTicketComments($ticket['id']); ?>
                                                    <?php foreach ($ticketComments as $comment): ?>
                                                        <li class="list-group-item d-flex align-items-start">
                                                            <img src="<?php echo htmlspecialchars($comment['profile_picture']); ?>" alt="avatar" style="width: 100px; height: 100px; object-fit: cover; margin-right: 15px;">
                                                            
                                                            <div>
                                                                <strong>Username:</strong> <a href="index.php?page=user/public_profile.php&uuid=<?php echo htmlspecialchars($comment['uuid']); ?>"><?php echo htmlspecialchars($comment['username']); ?></a><br>
                                                                <strong>Message:</strong> <?= htmlspecialchars($comment['ticket_comment']) ?>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>

                                                <?php if ($ticket['status'] !== 'resolved'): ?>
                                                    <form method="POST" action="" class="mt-3">
                                                        <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                                        <div class="form-group">
                                                            <textarea name="ticket_comment" class="form-control" rows="2" placeholder="Add your comment..." required></textarea>
                                                        </div>
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                        <button type="submit" name="submit_ticket_comment" class="btn btn-primary btn-sm">Submit Comment</button>
                                                    </form>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-center">No tickets found.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Display tickets for admins to manage -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">All Tickets</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($tickets)): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($tickets as $ticket): ?>
                                            <li class="list-group-item">
                                                <strong><?= htmlspecialchars($ticket['title']) ?></strong> - Status: <?= htmlspecialchars($ticket['status']) ?>
                                                <br>Description: <?= htmlspecialchars($ticket['description']) ?>

                                                <!-- Display the URL if provided -->
                                                <?php if (!empty($ticket['url'])): ?>
                                                    <br><a href="<?= htmlspecialchars($ticket['url']) ?>" target="_blank">Link to Issue</a>
                                                <?php endif; ?>

                                                <!-- Display ticket comments -->
                                                <h3 class="mt-3">Comments:</h3>
                                                <ul class="list-group list-group-flush">
                                                    <?php $ticketComments = $ticketCommentModel->getTicketComments($ticket['id']); ?>
                                                    <?php foreach ($ticketComments as $comment): ?>
                                                        <li class="list-group-item d-flex align-items-start">
                                                            <img src="<?php echo htmlspecialchars($comment['profile_picture']); ?>" alt="avatar" style="width: 100px; height: 100px; object-fit: cover; margin-right: 15px;">
                                                            
                                                            <div>
                                                                <strong>Username:</strong> <a href="index.php?page=user/public_profile.php&uuid=<?php echo htmlspecialchars($comment['uuid']); ?>"><?php echo htmlspecialchars($comment['username']); ?></a><br>
                                                                <strong>Message:</strong> <?= htmlspecialchars($comment['ticket_comment']) ?>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>

                                                <?php if ($ticket['status'] !== 'resolved'): ?>
                                                    <form method="POST" action="" class="mt-3">
                                                        <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                                        <div class="form-group">
                                                            <textarea name="ticket_comment" class="form-control" rows="2" placeholder="Add your comment..." required></textarea>
                                                        </div>
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                        <button type="submit" name="submit_ticket_comment" class="btn btn-primary btn-sm">Submit Comment</button>
                                                    </form>
                                                <?php endif; ?>

                                                <!-- Admin can update ticket status multiple times -->
                                                <form method="POST" action="" class="mt-3">
                                                    <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                                    <div class="form-group">
                                                        <select name="status" class="form-control" required>
                                                            <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                                            <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                                            <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                                        </select>
                                                    </div>
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <button type="submit" name="update_ticket" class="btn btn-primary">Update Ticket</button>
                                                </form>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-center">No tickets found.</p>
                                <?php endif; ?>
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
