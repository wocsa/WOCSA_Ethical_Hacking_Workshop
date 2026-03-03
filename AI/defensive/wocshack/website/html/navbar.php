<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
        <!-- Brand icon and text for the sidebar -->
        <div class="sidebar-brand-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="sidebar-brand-text mx-3">My Association</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- User-specific options based on session -->
    <?php if (isset($_SESSION['user_id'])): ?>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading for user-specific options -->
        <div class="sidebar-heading">User Options</div>

        <!-- Profile navigation link -->
        <li class="nav-item">
            <a class="nav-link" href="?page=user/profile.php">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </li>

        <!-- Analytics navigation link -->
        <li class="nav-item">
            <a class="nav-link" href="?page=analytics/analytics.php">
                <i class="fas fa-chart-line"></i>
                <span>Analytics</span>
            </a>
        </li>

        <!-- Logout navigation link -->
        <li class="nav-item">
            <a class="nav-link" href="?page=user/logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading for association-related options -->
        <div class="sidebar-heading">Association Options</div>

        <!-- Link to view existing associations -->
        <li class="nav-item">
            <a class="nav-link" href="?page=association/list.php">
                <i class="fas fa-list"></i>
                <span>Existing Associations</span>
            </a>
        </li>

        <!-- Link to create a new association -->
        <li class="nav-item">
            <a class="nav-link" href="?page=association/create_association.php">
                <i class="fas fa-plus-circle"></i>
                <span>Create Association</span>
            </a>
        </li>

        <!-- Link to view user's associations -->
        <li class="nav-item">
            <a class="nav-link" href="?page=association/my_associations.php">
                <i class="fas fa-folder-open"></i>
                <span>My Associations</span>
            </a>
        </li>

        <!-- Conditional display for admin financial options -->
        <?php if ($isAdminInAnyAssociation): ?>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading for financial-related options -->
            <div class="sidebar-heading">Financial Options</div>

            <!-- Financial accounts navigation link -->
            <li class="nav-item">
                <a class="nav-link" href="?page=financial/account.php">
                    <i class="fas fa-wallet"></i>
                    <span>Accounts</span>
                </a>
            </li>

            <!-- Donation navigation link -->
            <li class="nav-item">
                <a class="nav-link" href="?page=financial/donation.php">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>Donation</span>
                </a>
            </li>
        <?php endif; ?>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading for event-related options -->
        <div class="sidebar-heading">Event Options</div>

        <!-- Event calendar navigation link -->
        <li class="nav-item">
            <a class="nav-link" href="?page=event/event_calendar.php">
                <i class="fas fa-calendar-alt"></i>
                <span>Event Calendar</span>
            </a>
        </li>

        <!-- Event feedback navigation link -->
        <li class="nav-item">
            <a class="nav-link" href="?page=event/event_feedback.php">
                <i class="fas fa-comments"></i>
                <span>Event Feedback</span>
            </a>
        </li>

        <!-- Admin event creation option -->
        <?php if ($isAdminInAnyAssociation): ?>
            <li class="nav-item">
                <a class="nav-link" href="?page=event/event_registration.php">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Create Event</span>
                </a>
            </li>
        <?php endif; ?>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading for support-related options -->
        <div class="sidebar-heading">Support Options</div>

        <!-- Admin support option -->
        <?php if ($isAdmin): ?>
            <li class="nav-item">
                <a class="nav-link" href="?page=support/admin.php">
                    <i class="fas fa-tools"></i>
                    <span>Administration</span>
                </a>
            </li>
        <?php endif; ?>

        <!-- FAQ support link -->
        <li class="nav-item">
            <a class="nav-link" href="?page=support/faq.php">
                <i class="fas fa-question-circle"></i>
                <span>Q&A</span>
            </a>
        </li>

        <!-- Ticket support link -->
        <li class="nav-item">
            <a class="nav-link" href="?page=support/ticket.php">
                <i class="fas fa-ticket-alt"></i>
                <span>Tickets</span>
            </a>
        </li>

    <?php else: ?>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading for login options -->
        <div class="sidebar-heading">Login Options</div>

        <!-- Register navigation link -->
        <li class="nav-item">
            <a class="nav-link" href="?page=user/register.php">
                <i class="fas fa-user-plus"></i>
                <span>Register</span>
            </a>
        </li>

        <!-- Login navigation link -->
        <li class="nav-item">
            <a class="nav-link" href="?page=user/login.php">
                <i class="fas fa-sign-in-alt"></i>
                <span>Login</span>
            </a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading for association options available without login -->
        <div class="sidebar-heading">Association Options</div>

        <!-- Existing associations link -->
        <li class="nav-item">
            <a class="nav-link" href="?page=association/list.php">
                <i class="fas fa-list"></i>
                <span>Existing Associations</span>
            </a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider">

        

        <!-- Support options for non-logged-in users -->
        <div class="sidebar-heading">Support Options</div>

        <!-- FAQ support link -->
        <li class="nav-item">
            <a class="nav-link" href="?page=support/faq.php">
                <i class="fas fa-question-circle"></i>
                <span>Q&A</span>
            </a>
        </li>
    <?php endif; ?>

    <!-- Divider for styling -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar toggler for responsive design -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->
