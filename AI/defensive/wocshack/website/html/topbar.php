<!-- Topbar -->
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto">

        <!-- Nav Item - User Information -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <li class="nav-item dropdown no-arrow">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= htmlspecialchars($username); ?></span>
                    <img class="img-profile rounded-circle"
                        src="<?= htmlspecialchars($profilePicture); ?>" style="object-fit: cover; object-position: center;" alt="User Profile Picture">
                </a>
                <!-- Dropdown - User Information -->
                <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                    aria-labelledby="userDropdown">
                    <a class="dropdown-item" href="?page=user/profile.php">
                        <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                        Profile
                    </a>
                    <a class="dropdown-item" href="?page=association/my_associations.php">
                        <i class="fas fa-folder-open fa-sm fa-fw mr-2 text-gray-400"></i>
                        My Associations
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="?page=user/logout.php">
                        <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                        Logout
                    </a>
                </div>
            </li>
        <?php else: ?>
            <!-- Nav Item - Login -->
            <li class="nav-item dropdown no-arrow">
                <a class="nav-link dropdown-toggle" href="?page=user/login.php">
                    <i class="fas fa-sign-in-alt fa-fw"></i>
                    <span class="mr-2 d-none d-lg-inline text-gray-600 small">Login</span>
                </a>
            </li>
        <?php endif; ?>

    </ul>

</nav>
<!-- End of Topbar -->
