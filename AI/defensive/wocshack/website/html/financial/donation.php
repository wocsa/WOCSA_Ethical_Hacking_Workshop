<?php
require_once 'models/Financial.php';
require_once 'models/User.php';
require_once 'models/Association.php';
require 'tools/csrf.php';

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
    $csrf = new csrf();
    $csrfToken = $csrf->generateCSRFToken();
}

$associationModel = new Association($pdo);
$adminAssociations = $associationModel->getUserAssociationsWhereAdmin($userId);

if (empty($adminAssociations)) {
    die("You are not an admin for any associations.");
}

$error_message = '';
$donatorName = '';
$amount = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['association_id'])) {
    $associationId = intval($_POST['association_id']);
    $donatorName = trim($_POST['donator_name']);
    $amount = $_POST['amount'];

    $isAssociationAdmin = false;
    foreach ($adminAssociations as $association) {
        if ($association['id'] == $associationId) {
            $isAssociationAdmin = true;
            break;
        }
    }

    if (!$isAssociationAdmin) {
        $error_message = "You are not administrator.";
    } else {
        if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
            die('CSRF token validation failed');
        }

        if (empty($donatorName) || empty($amount) || !is_numeric($amount) || $amount <= 0) {
            $error_message = 'Please enter a valid name and donation amount!';
        } else {
            $associationData = $associationModel->getAssociationById($associationId);
            $associationName = $associationData['name'];
            $financial = new Financial($pdo, $associationName, $donatorName, (float)$amount);
            $outputPath = '/tmp/donation_receipt_' . time() . '.pdf';
            $financial->generatePDF($outputPath);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="donation_receipt.pdf"');
            readfile($outputPath);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make a Donation</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Generate a Donation Receipt</h1>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Receipt Details</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="association_id">Select Association:</label>
                                    <select name="association_id" id="association_id" class="form-control" required>
                                        <?php foreach ($adminAssociations as $association): ?>
                                            <option value="<?= htmlspecialchars($association['id']) ?>">
                                                <?= htmlspecialchars($association['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="donator_name">Donor Name:</label>
                                    <input type="text" id="donator_name" name="donator_name" class="form-control" value="<?= htmlspecialchars($donatorName) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="amount">Donation Amount:</label>
                                    <input type="number" id="amount" name="amount" class="form-control" value="<?= htmlspecialchars($amount) ?>" step="0.01" min="0" required>
                                </div>
                                
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <button type="submit" class="btn btn-primary">Generate Receipt</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once 'footer.php'; ?>
        </div>
    </div>
</body>

<?php include_once 'scripts.php'; ?>

</html>
