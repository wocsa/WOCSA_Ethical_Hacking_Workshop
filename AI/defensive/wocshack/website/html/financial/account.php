<?php
require_once 'models/Financial.php';
require_once 'models/User.php';
require_once 'models/Association.php';
require 'tools/csrf.php';

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

$associationModel = new Association($pdo);

// Fetch associations where the user is an admin
$adminAssociations = $associationModel->getUserAssociationsWhereAdmin($userId);
if (empty($adminAssociations)) {
    die("You are not an admin for any associations.");
}

$error_message = '';
$success_message = '';
$associationId = '';
$accountNumber = '';
$bankName = '';
$routingNumber = '';
$transactionAmount = '';
$transactionDescription = '';
$donatorName = '';
$calculation_result = '';

// Automatically select the first association if none is selected
if (!isset($_POST['association_id']) && !empty($adminAssociations)) {
    $associationId = $adminAssociations[0]['id'];
    $_POST['association_id'] = $associationId; // Simulate form submission
}

// Handle form submission for adding a bank account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_account'])) {
    $associationId = intval($_POST['association_id']);
    $accountNumber = trim($_POST['account_number']);
    $bankName = trim($_POST['bank_name']);
    $routingNumber = trim($_POST['routing_number']);

    // Check if user is admin
    $isAssociationAdmin = false;
    foreach ($adminAssociations as $association) {
        if ($association['id'] == $associationId) {
            $isAssociationAdmin = true;
            break;
        }
    }
    // No admin
    if (!$isAssociationAdmin) {
        $error_message = "You are not administrator.";
    }
    else {


        if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
            die('CSRF token validation failed');
        }

        if (empty($accountNumber) || empty($bankName) || empty($routingNumber)) {
            $error_message = 'Please enter all account details!';
        } else {
            $associationData = $associationModel->getAssociationById($associationId);
            $associationName = $associationData['name'];

            $financial = new Financial($pdo, $associationName, '', 0);
            try {
                $financial->addBankAccount($accountNumber, $bankName, $routingNumber);
                $success_message = 'Bank account details added successfully!';
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
        }
    }
}

// Handle form submission for adding a transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_transaction'])) {
    $associationId = intval($_POST['association_id']);
    $transactionAmount = trim($_POST['transaction_amount']);
    $transactionDescription = trim($_POST['transaction_description']);
    $donatorName = trim($_POST['donator_name']);

    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    if (empty($transactionAmount) || !is_numeric($transactionAmount) || empty($transactionDescription) || empty($donatorName)) {
        $error_message = 'Please enter a valid transaction amount!';
    } else {
        $associationData = $associationModel->getAssociationById($associationId);
        $associationName = $associationData['name'];

        $financial = new Financial($pdo, $associationName, '', 0);
        $financial->addTransaction($transactionAmount, $transactionDescription, $donatorName);

        $success_message = 'Transaction added successfully!';
    }
}

// Handle form submission for deleting a transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_transaction'])) {
    $associationId = intval($_POST['association_id']);
    $transactionId = intval($_POST['transaction_id']);

    if (!$csrf->verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    if (empty($transactionId)) {
        $error_message = 'Invalid transaction ID!';
    } else {
        $associationData = $associationModel->getAssociationById($associationId);
        $associationName = $associationData['name'];

        $financial = new Financial($pdo, $associationName, '', 0);
        try {
            $financial->deleteTransaction($transactionId);
            $success_message = 'Transaction deleted successfully!';
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// Fetch bank account details if an association is selected
$selectedAssociation = null;
$bankAccount = null;
$totalAmount = 0;
if (isset($_POST['association_id'])) {
    $associationId = intval($_POST['association_id']);
    $associationData = $associationModel->getAssociationById($associationId);
    $associationName = $associationData['name'];

    $financial = new Financial($pdo, $associationName, '', 0);
    $bankAccount = $financial->getBankAccount();
    $totalAmount = $financial->getTotalAmount();
}

// Handle calculator input
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    $financial = new Financial($pdo, $associationName, '', $totalAmount);
    $calculation_result = $financial->computeCalc($_POST['amount_simulator'] ?? '');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bank Account</title>
    <?php include_once 'style.php'; ?>
    <!-- Custom styles for this page -->
    <link href="startbootstrap-sb-admin-2-gh-pages/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script>
        function reloadForm() {
            document.getElementById('associationForm').submit();
        }
    </script>
    <style>
        .row-equal-height {
            display: flex;
            flex-wrap: wrap;
        }
        .row-equal-height > [class*='col-'] {
            display: flex;
            flex-direction: column;
        }
    </style>
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
                        <h1 class="h3 mb-0 text-gray-800">Manage Bank Account</h1>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Select Association</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="associationForm">
                                <div class="form-group">
                                    <label for="association_id">Select Association:</label>
                                    <select name="association_id" id="association_id" class="form-control" required onchange="reloadForm()">
                                        <?php foreach ($adminAssociations as $association): ?>
                                            <option value="<?= htmlspecialchars($association['id']) ?>" <?= isset($associationId) && $associationId == $association['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($association['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php if ($bankAccount): ?>


                        <div class="row row-equal-height">
                        <div class="col-12">
                        <div class="row">
                            <!-- Earnings (Monthly) Card Example -->
                            <div class="col-xl-6 col-md-6 mb-4">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    Account number</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= htmlspecialchars($bankAccount['account_number']) ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-credit-card fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pending Requests Card Example -->
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card border-left-warning shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                    Bank name</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= htmlspecialchars($bankAccount['bank_name']) ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-university fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Earnings (Annual) Card Example -->
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card border-left-success shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    Total amount</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">$<?= number_format((float)($totalAmount ?? 0), 2) ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                        <div class="col-8">
                            <div class="card shadow mb-4 h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Add Transaction</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="association_id" value="<?= htmlspecialchars($associationId) ?>">
                                        <div class="form-group">
                                            <label for="transaction_amount">Transaction Amount:</label>
                                            <input type="number" id="transaction_amount" name="transaction_amount" class="form-control" value="" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="transaction_description">Transaction Description:</label>
                                            <input type="text" id="transaction_description" name="transaction_description" class="form-control" value="" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="donator_name">Donator or Receivor Name:</label>
                                            <input type="text" id="donator_name" name="donator_name" class="form-control" value="" required>
                                        </div>
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <button type="submit" name="add_transaction" class="btn btn-primary">Add Transaction</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card shadow mb-4 h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Calculator</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="association_id" value="<?= htmlspecialchars($associationId) ?>">
                                        <div class="form-group">
                                            <label for="amount_simulator">What do you want to add or remove from your current bank account ?</label>
                                            <input type="text" id="amount_simulator" name="amount_simulator" class="form-control" value="<?= htmlspecialchars($_POST['amount_simulator'] ?? '') ?>" required>
                                        </div>
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <button type="submit" name="calculate" class="btn btn-primary">Calculate</button>
                                    </form>
                                    <hr>
                                    <p><b>Calculation Result:</b> <?= htmlspecialchars($calculation_result ?? '', ENT_QUOTES) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Transactions history</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Transaction ID</th>
                                                    <th>Donator Name</th>
                                                    <th>Amount</th>
                                                    <th>Description</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tfoot>
                                                <tr>
                                                    <th>Transaction ID</th>
                                                    <th>Donator Name</th>
                                                    <th>Amount</th>
                                                    <th>Description</th>
                                                    <th>Action</th>
                                                </tr>
                                            </tfoot>
                                            <tbody>
                                                <?php foreach ($financial->getTransactions() as $transaction): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($transaction['id']) ?></td>
                                                        <td><?= htmlspecialchars($transaction['donator_name']) ?></td>
                                                        <td>$<?= number_format((float)$transaction['amount'], 2) ?></td>
                                                        <td><?= htmlspecialchars($transaction['description']) ?></td>
                                                        <td>
                                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this transaction?');">
                                                                <input type="hidden" name="association_id" value="<?= htmlspecialchars($associationId) ?>">
                                                                <input type="hidden" name="transaction_id" value="<?= htmlspecialchars($transaction['id']) ?>">
                                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                                <button type="submit" name="delete_transaction" class="btn btn-danger btn-sm">Delete</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                    <?php else: ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Add Bank Account</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="association_id" value="<?= htmlspecialchars($associationId) ?>">
                                    <div class="form-group">
                                        <label for="account_number">Account Number:</label>
                                        <input type="text" id="account_number" name="account_number" class="form-control" value="<?= htmlspecialchars($accountNumber) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="bank_name">Bank Name:</label>
                                        <input type="text" id="bank_name" name="bank_name" class="form-control" value="<?= htmlspecialchars($bankName) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="routing_number">Routing Number:</label>
                                        <input type="text" id="routing_number" name="routing_number" class="form-control" value="<?= htmlspecialchars($routingNumber) ?>" required>
                                    </div>
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <button type="submit" name="add_account" class="btn btn-primary">Add Bank Account</button>
                                </form>
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
<!-- Page level plugins -->
<script src="startbootstrap-sb-admin-2-gh-pages/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="startbootstrap-sb-admin-2-gh-pages/vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Page level custom scripts -->
<script src="startbootstrap-sb-admin-2-gh-pages/js/demo/datatables-demo.js"></script>

</html>
