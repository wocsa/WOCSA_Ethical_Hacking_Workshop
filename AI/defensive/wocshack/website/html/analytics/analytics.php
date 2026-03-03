<?php
require_once 'models/User.php'; // Include the User model
require_once 'models/Analytics.php'; // Include the Analytics model
require_once 'models/Association.php';

$analyticsController = new Analytics($pdo);
$userModel = new User($pdo);
$associationModel = new Association($pdo);
$response = '';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if the user is not logged in
    header('Location: ?page=user/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$isAdmin = $userModel->isAdmin($userId);
$isAdminInAnyAssociation = $userModel->isAdminInAnyAssociation($userId);
$user = $userModel->getUserById($userId); // Get the user details
$username = $user['username'];
$profilePicture = $user['profile_picture'] ? $user['profile_picture'] : 'uploads/default.jpg'; // Default image if no profile picture

// Fetch user role and association ID
$userData = $userModel->getUserById($userId);
$userRole = 'normal';
$associationIds = []; // Initialize with an empty array

if ($userModel->isAdminInAnyAssociation($userId)) {
    $userRole = 'association_admin';
    $associations = $associationModel->getAssociationsByRole($userId)['admin'];
    foreach ($associations as $association) {
        $associationIds[] = $association['id'];
    }
}

if ($userData['is_admin']) {
    $userRole = 'global_admin';
    $associationIds = null;
}

// Fetch statistics based on role
$statistics = $analyticsController->getRoleBasedStats($userId, $userRole, $associationIds);
$userStatistics = $analyticsController->getRoleBasedStats($userId, 'normal');

// Handle errors
if (isset($statistics['error'])) {
    $response = [
        'status' => 'error',
        'message' => $statistics['error'],
    ];
}

// Handle XML export
if (isset($_POST['export_xml'])) {
    $xmlData = $analyticsController->exportToXml($statistics, 'analytics');
    header('Content-Type: application/xml');
    header('Content-Disposition: attachment; filename="analytics.xml"');
    echo $xmlData;
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Analytics Dashboard</h1>
                    </div>

                    <!-- Response message -->
                    <?php if ($response): ?>
                        <div class="alert <?= $response['status'] ?>">
                            <?= htmlspecialchars($response['message']) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Normal User Statistics -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h4>Global</h4>
                    </div>
                    <div class="row">
                        <!-- Username Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Username</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= htmlspecialchars($userStatistics['username']) ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Associations Count Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Associations</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $userStatistics['associations_count'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tickets Count Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Tickets</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $userStatistics['tickets_count'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Feedbacks Count Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Feedbacks</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $userStatistics['feedback_count'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-comments fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($userRole === 'association_admin'): ?>
                        <!-- Association Admin Statistics -->
                            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                                <h4>My associations</h4>
                            </div>
                            <?php foreach ($statistics as $statistic): ?>

                                <div class="row">

                                <!-- Association Name Card -->
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <div class="card border-left-primary shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                        Association Name</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= htmlspecialchars($statistic['name']) ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-building fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Users Count Card -->
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <div class="card border-left-success shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                        Users</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $statistic['users_count'] ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Feedbacks Count Card -->
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <div class="card border-left-warning shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                        Feedbacks</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $statistic['feedback_count'] ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-comments fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                </div>
                            <?php endforeach; ?>


                    <?php elseif ($userRole === 'global_admin'): ?>
                        <!-- Global Admin Statistics -->
                        <div class="row">
                            <!-- Total Users Card -->
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    Total Users</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $statistics['total_users']['total_users'] ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-users fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Associations Card -->
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card border-left-success shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    Total Associations</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $statistics['total_associations']['total_associations'] ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-building fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Feedbacks Card -->
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card border-left-warning shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                    Total Feedbacks</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $statistics['total_feedbacks']['total_feedbacks'] ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-comments fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-lg-6">
                                <h4>Feedbacks by Association</h4>
                                <?php foreach ($statistics['feedbacks_by_association'] as $feedback): ?>
                                    <div class="card border-left-info shadow py-2 mb-4">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                        <?= htmlspecialchars($feedback['name']) ?></div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $feedback['feedback_count'] ?> feedbacks</div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-comments fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="col-lg-6">
                                <h4>Users by Association</h4>
                                <?php foreach ($statistics['users_by_association'] as $userCount): ?>
                                    <div class="card border-left-info shadow py-2 mb-4">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                        <?= htmlspecialchars($userCount['name']) ?></div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $userCount['user_count'] ?> users</div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Export to XML Button -->
                    <div class="row">
                        <div class="col-12 text-center m-4">
                            <form method="post">
                                <button type="submit" name="export_xml" class="btn btn-primary">Export Statistics as XML</button>
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
