<?php
require_once 'models/Faq.php'; // Include the Faq model
require_once 'models/User.php'; // Include the User model
require 'tools/csrf.php';

$faqController = new Faq($pdo);
$userModel = new User($pdo);
$response = '';

// Initialize user variables
$userId = null;
$isAdmin = false;
$username = 'Guest';
$profilePicture = 'uploads/default.jpg';

// Check if the user is logged in and fetch associations where the user is an admin
if (isset($_SESSION['user_id'])) {
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

// Handle question submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_question'])) {
    if (!$userId) {
        $response = ['status' => 'error', 'message' => 'You must be logged in to ask questions.'];
    } else {

        if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
            die('CSRF token validation failed');
        }

        $question = trim($_POST['question']);
        $response = $faqController->createQuestion($userId, $question) ?
            ['status' => 'success', 'message' => 'Question submitted successfully.'] :
            ['status' => 'error', 'message' => 'Failed to submit question.'];
    }
}

// Handle answering a question (for admin users)
if (isset($_POST['answer_question'])) {
    if (!$isAdmin) {
        $response = ['status' => 'error', 'message' => 'You must be an admin to answer questions.'];
    } else {
        $faqId = intval($_POST['faq_id']);
        $answer = trim($_POST['answer']);
        $adminId = $userId; // Assuming the logged-in user is an admin

        $response = $faqController->answerQuestion($faqId, $answer, $adminId) ?
            ['status' => 'success', 'message' => 'Answer submitted successfully.'] :
            ['status' => 'error', 'message' => 'Failed to submit answer.'];
    }
}

// Display unanswered questions for admins
$unansweredQuestions = $faqController->getUnansweredQuestions();

// Display answered questions for all users
$answeredQuestions = $faqController->getAnsweredQuestions();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Q&A</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Q&A</h1>
                    </div>

                    <!-- Display response messages -->
                    <?php if ($response): ?>
                        <div class="alert alert-<?= $response['status'] ?>">
                            <?= $response['message'] ?>
                        </div>
                    <?php endif; ?>

                    <!-- Form for non-admin users to submit a question -->
                    <?php if ($userId): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Ask a Question</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="form-group">
                                        <textarea name="question" class="form-control" rows="3" required placeholder="Type your question here..."></textarea>
                                    </div>
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <button type="submit" name="submit_question" class="btn btn-primary">Submit Question</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Display answered questions for all users -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Answered Questions</h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($answeredQuestions)): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($answeredQuestions as $question): ?>
                                        <li class="list-group-item d-flex align-items-start">
                                            <img src="<?php echo htmlspecialchars($question['profile_picture']); ?>" alt="avatar" style="width: 100px; height: 100px; object-fit: cover; margin-right: 15px;">
                                            <div>
                                                <strong>Username:</strong> <a href="index.php?page=user/public_profile.php&uuid=<?php echo htmlspecialchars($question['uuid']); ?>"><?php echo htmlspecialchars($question['username']); ?></a><br>
                                                <strong>Question:</strong> <?= htmlspecialchars($question['question']) ?><br>
                                                <strong>Answer:</strong> <?= htmlspecialchars($question['answer']) ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-center">No answered questions at the moment.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Display unanswered questions for admins -->
                    <?php if ($isAdmin && !empty($unansweredQuestions)): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Unanswered Questions</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($unansweredQuestions as $question): ?>
                                        <li class="list-group-item d-flex align-items-start">
                                            <img src="<?php echo htmlspecialchars($question['profile_picture']); ?>" alt="avatar" style="width: 100px; height: 100px; object-fit: cover; margin-right: 15px;">
                                            <div>
                                            <strong>Username:</strong> <a href="index.php?page=user/public_profile.php&uuid=<?php echo htmlspecialchars($question['uuid']); ?>"><?php echo htmlspecialchars($question['username']); ?></a><br></strong>
                                            <strong>Question:</strong> <?= htmlspecialchars($question['question']) ?>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="faq_id" value="<?= $question['id'] ?>">
                                                    <div class="form-group mt-2">
                                                        <textarea name="answer" class="form-control" rows="2" required placeholder="Type your answer here..."></textarea>
                                                    </div>
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <button type="submit" name="answer_question" class="btn btn-primary btn-sm">Submit Answer</button>
                                                </form>
                                            </div>
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