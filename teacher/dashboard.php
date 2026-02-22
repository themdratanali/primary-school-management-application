<?php
require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit;
}

// Optional: route section (set by .htaccess)
$section = $_GET['section'] ?? '';
if (!is_string($section) || !preg_match('/^[A-Za-z0-9_-]{1,50}$/', $section)) {
    $section = '';
}

// Get teacher info
$teacher_id = $_SESSION['teacher_id'];
$stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard - Apex Model School</title>
    <link rel="shortcut icon" type="image/jpg" href="../assets/img/এ্যাপেক্স মডেল স্কুল.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/apex/assets/fontawesome/fontawesome-free-6.4.0-web/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/myScript.js"></script>
    
    <style>
        /* Submenu styles */
        .menu-item {
            position: relative;
        }
        
        .menu-item > a {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .toggle-icon {
            font-size: 10px;
            transition: transform 0.3s;
        }
        
        .menu-item.open .toggle-icon {
            transform: rotate(180deg);
        }
        
        .submenu {
            display: none;
            flex-direction: column;
            padding-left: 15px;
            margin-top: 5px;
            margin-bottom: 5px;
        }
        
        .menu-item.open > .submenu {
            display: flex;
        }
        
        .submenu a {
            padding: 8px 12px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            font-size: 12px;
            margin-bottom: 2px;
            transition: background 0.2s;
            white-space: nowrap;
        }
        
        .submenu a:hover {
            background: rgba(255,255,255,0.15);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 500px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .modal-content h3 {
            margin-top: 0;
            color: #333;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        
        /* Mobile Bottom Navigation Bar */
        .mobile-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(180deg, #177a03 0%, #145a02 100%);
            padding: 8px 5px;
            justify-content: space-around;
            align-items: center;
            z-index: 9999;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.2);
        }
        
        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 11px;
            padding: 5px 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
            min-width: 60px;
        }
        
        .bottom-nav-item i {
            font-size: 18px;
            margin-bottom: 3px;
        }
        
        .bottom-nav-item:hover,
        .bottom-nav-item.active {
            color: #fff;
            background: rgba(255,255,255,0.2);
        }
        
        .bottom-nav-item.active i {
            transform: scale(1.1);
        }
        
        @media (max-width: 992px) {
            .mobile-bottom-nav {
                display: flex;
            }
            .sidebar {
                z-index: 10000;
            }
            .main-content iframe {
                min-height: calc(100vh - 130px) !important;
            }
        }
        
        @media (max-width: 576px) {
            .bottom-nav-item {
                min-width: 50px;
                padding: 5px 4px;
                font-size: 10px;
            }
            .bottom-nav-item i {
                font-size: 16px;
            }
        }
    </style>

</head>

<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="sidebar" id="sidebar">
        <h4>Apex Model School</h4>
        
        <!-- Simple Menu - No Accordions -->
        <a href="overview.php" target="mainFrame">
            <i class="fas fa-chart-bar"></i> <span>Overview</span>
        </a>
        <a href="homework.php" target="mainFrame">
            <i class="fas fa-book"></i> <span>Home Work</span>
        </a>
        <a href="manage_results.php" target="mainFrame">
            <i class="fas fa-clipboard-check"></i> <span>Manage Results</span>
        </a>
        <a href="marksheet.php" target="mainFrame">
            <i class="fas fa-file-alt"></i> <span>Mark Sheet</span>
        </a>
        <a href="profile_edit.php" target="mainFrame">
            <i class="fas fa-user-edit"></i> <span>Edit Profile</span>
        </a>
        <div class="logout-link">
            <a href="javascript:void(0);" onclick="confirmLogout();">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <iframe name="mainFrame" src="overview.php" title="Teacher dashboard content" style="width: 95%; border: none; min-height: calc(100vh - 120px); margin: auto;"></iframe>
    </div>

    <!-- Mobile Bottom Navigation Bar -->
    <div class="mobile-bottom-nav" id="mobileBottomNav">
        <a href="javascript:void(0);" class="bottom-nav-item active" data-route="overview" data-src="overview.php">
            <i class="fas fa-chart-bar"></i>
            <span>Overview</span>
        </a>
        <a href="javascript:void(0);" class="bottom-nav-item" data-route="homework" data-src="homework.php">
            <i class="fas fa-book"></i>
            <span>Home Work</span>
        </a>
        <a href="javascript:void(0);" class="bottom-nav-item" data-route="manageresults" data-src="manage_results.php">
            <i class="fas fa-clipboard-check"></i>
            <span>Results</span>
        </a>
        <a href="javascript:void(0);" class="bottom-nav-item" data-route="marksheet" data-src="marksheet.php">
            <i class="fas fa-file-alt"></i>
            <span>Mark Sheet</span>
        </a>
        <a href="javascript:void(0);" class="bottom-nav-item" data-route="profile" data-src="profile_edit.php">
            <i class="fas fa-user-edit"></i>
            <span>Profile</span>
        </a>
        <a href="javascript:void(0);" class="bottom-nav-item" onclick="confirmLogout();">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>

    <!-- Profile Modal -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeProfile()">&times;</span>
            <h3>Teacher Profile</h3>
            <div class="profile-info">
                <div class="profile-photo">
                    <?php
                    $rawPhoto = $teacher['photo'] ?? '';
                    $resolvedPhoto = '';

                    if (!empty($rawPhoto)) {
                        // Try several common storage patterns to resolve a valid file path
                        $candidates = [];
                        // Stored as relative from project root, e.g. 'uploads/teachers/file.jpg'
                        $candidates[] = '../' . ltrim($rawPhoto, '/');
                        // Stored with leading ../ as used in some admin tools
                        $candidates[] = $rawPhoto;
                        // Fallback based on just the basename in the standard uploads folder
                        $candidates[] = '../uploads/teachers/' . basename($rawPhoto);

                        foreach ($candidates as $candidate) {
                            if (file_exists($candidate)) {
                                $resolvedPhoto = $candidate;
                                break;
                            }
                        }
                    }

                    if (!empty($resolvedPhoto)) {
                        echo '<img src="' . htmlspecialchars($resolvedPhoto) . '" alt="Profile">';
                    } else {
                        echo '<i class="fas fa-user-circle"></i>';
                    }
                    ?>
                </div>
                <div class="profile-details">
                    <p><strong>Name:</strong> <?= htmlspecialchars($teacher['name'] ?? 'N/A') ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($teacher['email'] ?? 'N/A') ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($teacher['phone'] ?? 'N/A') ?></p>
                    <p><strong>Education:</strong></p>
                    <?php 
                    $educationData = json_decode($teacher['education'] ?? '[]', true);
                    if (!empty($educationData) && is_array($educationData)) {
                        echo '<ul style="margin: 5px 0 10px 20px; padding: 0;">';
                        foreach ($educationData as $edu) {
                            echo '<li style="margin-bottom: 5px;">';
                            echo '<strong>' . htmlspecialchars($edu['education'] ?? '') . '</strong>';
                            if (!empty($edu['institute'])) {
                                echo ' - ' . htmlspecialchars($edu['institute']);
                            }
                            if (!empty($edu['result'])) {
                                echo ' (Result: ' . htmlspecialchars($edu['result']) . ')';
                            }
                            echo '</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo htmlspecialchars($teacher['education'] ?? 'N/A');
                    }
                    ?>
                    <div style="margin-top: 15px;">
                        <button type="button" class="btn btn-primary btn-sm" onclick="openTeacherPasswordModal()">
                            <i class="fas fa-key me-1"></i>Change Password
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div style="background: linear-gradient(135deg, #177a03 0%, #219a04 100%); color: white; padding: 15px; border-radius: 10px 10px 0 0; margin: -20px -20px 20px -20px;">
                <span class="close" onclick="closePasswordModal()" style="color: white; float: right; cursor: pointer;">&times;</span>
                <h3 style="margin: 0;"><i class="fas fa-key me-2"></i>Change Password</h3>
            </div>
            <div style="padding: 20px;">
                <input type="hidden" id="teacherId" value="<?= $teacher_id ?>">
                <div class="mb-3">
                    <label for="teacherNewPassword" class="form-label">New Password</label>
                    <input type="text" class="form-control" id="teacherNewPassword" placeholder="Enter new password">
                </div>
                <div id="teacherPasswordMessage" style="margin-top: 10px;"></div>
                <div style="margin-top: 15px; text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveTeacherPassword()" style="background-color: #177a03; border-color: #177a03;">
                        <i class="fas fa-save me-1"></i>Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications Modal -->
    <div id="notificationModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeNotifications()">&times;</span>
            <h3>Notifications</h3>
            <div class="notification-list" id="notificationList">
                <div style="text-align:center; padding: 20px;">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>

    <script>
        // Define toggleSidebar globally - this ensures it's available immediately for onclick handlers
        // but we defer element access until DOM is ready
        
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            if (!sidebar || !sidebarToggle) return;
            
            // Toggle active class for sidebar
            sidebar.classList.toggle('active');
            sidebarToggle.classList.toggle('toggle-active');
            
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('active');
            }
            
            // Add class to body on mobile
            if (window.innerWidth <= 768) {
                document.body.classList.toggle('sidebar-open');
            }
            
            // Save state
            const isActive = sidebar.classList.contains('active');
            localStorage.setItem('teacherSidebarState', isActive ? 'open' : 'closed');
        };
        
        // Logout confirmation
        function confirmLogout() {
            var result = confirm('Are you sure you want to logout?');
            if (result) {
                window.location.href = 'logout.php';
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handler for toggle button
            const sidebarToggle = document.getElementById('sidebarToggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebar);
            }
            
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const sidebar = document.getElementById('sidebar');
            
            // Close on overlay click
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', toggleSidebar);
            }
            
            // Close on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && sidebar && sidebar.classList.contains('active')) {
                    toggleSidebar();
                }
            });
            
            // Restore saved state on mobile only
            const savedState = localStorage.getItem('teacherSidebarState');
            if (savedState === 'open' && window.innerWidth <= 768) {
                sidebar.classList.add('active');
                sidebarToggle.classList.add('toggle-active');
                sidebarOverlay.classList.add('active');
                document.body.classList.add('sidebar-open');
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    // On desktop, always show sidebar
                    sidebar.classList.remove('active');
                    sidebarToggle.classList.remove('toggle-active');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.remove('active');
                    }
                    document.body.classList.remove('sidebar-open');
                }
            });
            
            // Menu toggle handlers
            document.querySelectorAll('.menu-item > a').forEach(menuToggle => {
                menuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const menuItem = menuToggle.parentElement;
                    menuItem.classList.toggle('open');
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const initialSectionFromServer = <?= json_encode($section ?: '') ?>;
            const mainFrame = document.querySelector('iframe[name="mainFrame"]');

            const routes = {
                overview: 'overview.php',
                students: '../student/view_students.php',
                manageresults: 'manage_results.php',
                // Use the shared admin/student marksheet so design stays consistent
                marksheet: 'marksheet.php',
                profile: 'profile_edit.php',
            };

            const defaultRoute = 'overview';

            function getBasePath() {
                const p = window.location.pathname;
                const idx = p.toLowerCase().indexOf('/teacher/');
                if (idx === -1) return '/';
                return p.slice(0, idx + 1);
            }

            function injectFrameStyles() {
                try {
                    const doc = mainFrame.contentDocument;
                    if (!doc) return;
                    if (doc.querySelector('link[data-dashboard-frame]')) return;

                    const link = doc.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = `${getBasePath()}assets/css/dashboard_frame.css`;
                    link.setAttribute('data-dashboard-frame', '1');
                    doc.head && doc.head.appendChild(link);
                } catch (_) {
                    // ignore
                }
            }

            function getRouteFromPath() {
                const p = window.location.pathname;
                const idx = p.toLowerCase().indexOf('/teacher/');
                if (idx === -1) return '';
                const after = p.slice(idx + '/teacher/'.length);
                const seg = after.split('/').filter(Boolean)[0] || '';
                if (seg === '' || seg.toLowerCase() === 'dashboard') return '';
                return seg;
            }

            function normalizeRoute(r) {
                if (!r || typeof r !== 'string') return '';
                return r.trim().toLowerCase();
            }

            function setActive(route) {
                document.querySelectorAll('.sidebar a[data-route]').forEach(a => a.classList.remove('active'));
                const active = document.querySelector(`.sidebar a[data-route="${route}"]`);
                if (active) active.classList.add('active');

                // Also set active for bottom nav
                document.querySelectorAll('.bottom-nav-item').forEach(a => a.classList.remove('active'));
                const bottomActive = document.querySelector(`.bottom-nav-item[data-route="${route}"]`);
                if (bottomActive) bottomActive.classList.add('active');

                // Open parent menu if submenu item
                const menuStudent = document.getElementById('menu-student');
                const menuResults = document.getElementById('menu-results');
                [menuStudent, menuResults].forEach(m => m && m.classList.remove('open'));

                const activeInSubmenu = active && active.closest('.submenu');
                if (activeInSubmenu) {
                    const parentMenu = active.closest('.menu-item');
                    if (parentMenu) parentMenu.classList.add('open');
                }
            }

            function applyRoute(route, { push } = { push: false }) {
                route = normalizeRoute(route);
                if (!routes[route]) route = defaultRoute;

                mainFrame.src = routes[route];
                setActive(route);
                localStorage.setItem('teacherCurrentRoute', route);

                const base = getBasePath();
                const nextUrl = `${base}teacher/${route === defaultRoute ? 'dashboard' : route}`;
                const state = { route };
                if (push) history.pushState(state, '', nextUrl);
                else history.replaceState(state, '', nextUrl);
            }

            mainFrame.addEventListener('load', injectFrameStyles);

            // Bottom navigation click handlers
            document.querySelectorAll('.bottom-nav-item[data-route]').forEach(a => {
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    const route = a.getAttribute('data-route');
                    const src = a.getAttribute('data-src');
                    if (!route || !src) return;
                    routes[route] = src;
                    applyRoute(route, { push: true });
                    // Close sidebar on mobile when using bottom nav
                    if (window.innerWidth <= 768) {
                        const sidebar = document.getElementById('sidebar');
                        const overlay = document.getElementById('sidebarOverlay');
                        sidebar.classList.remove('active');
                        overlay.classList.remove('active');
                    }
                });
            });

            document.querySelectorAll('.sidebar a[data-route]').forEach(a => {
                a.addEventListener('click', (e) => {
                    const route = a.getAttribute('data-route');
                    const src = a.getAttribute('data-src');
                    if (!route || !src) return;
                    e.preventDefault();
                    routes[route] = src;
                    applyRoute(route, { push: true });
                    // Auto-close sidebar on mobile
                    if (window.innerWidth <= 768) {
                        const sidebar = document.getElementById('sidebar');
                        const overlay = document.getElementById('sidebarOverlay');
                        sidebar.classList.remove('active');
                        overlay.classList.remove('active');
                    }
                });
            });

            window.addEventListener('popstate', (e) => {
                const routeFromState = e.state && e.state.route ? e.state.route : '';
                const route = routeFromState || getRouteFromPath() || defaultRoute;
                applyRoute(route, { push: false });
            });

            const routeFromUrl = normalizeRoute(getRouteFromPath());
            const saved = normalizeRoute(localStorage.getItem('teacherCurrentRoute') || '');
            const initial = routeFromUrl || normalizeRoute(initialSectionFromServer) || saved || defaultRoute;
            applyRoute(initial, { push: false });
        });
        
        function showProfile() {
            document.getElementById('profileModal').style.display = 'block';
        }

        function closeProfile() {
            document.getElementById('profileModal').style.display = 'none';
        }

        function showNotifications() {
            loadAndShowNotifications();
            document.getElementById('notificationModal').style.display = 'block';
        }

        function closeNotifications() {
            document.getElementById('notificationModal').style.display = 'none';
        }

        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout';
            }
        }

        function openTeacherPasswordModal() {
            document.getElementById('passwordModal').style.display = 'block';
            document.getElementById('teacherPasswordMessage').innerHTML = '';
            document.getElementById('teacherNewPassword').value = '';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
        }

        function saveTeacherPassword() {
            var teacherId = document.getElementById('teacherId').value;
            var newPassword = document.getElementById('teacherNewPassword').value.trim();
            var messageDiv = document.getElementById('teacherPasswordMessage');
            
            if (newPassword === '') {
                messageDiv.innerHTML = '<span style="color: red;">Password cannot be empty!</span>';
                return;
            }
            
            messageDiv.innerHTML = '<span style="color: #666;">Saving...</span>';
            
            fetch('../admin/update_teacher_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'teacher_id=' + encodeURIComponent(teacherId) + '&password=' + encodeURIComponent(newPassword)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = '<span style="color: green;">Password saved successfully!</span>';
                    setTimeout(function() {
                        closePasswordModal();
                    }, 1000);
                } else {
                    messageDiv.innerHTML = '<span style="color: red;">' + (data.error || 'Error saving password') + '</span>';
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<span style="color: red;">Error saving password!</span>';
            });
        }

        function loadAndShowNotifications() {
            fetch('../admin/get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const notificationList = document.getElementById('notificationList');
                        const badge = document.getElementById('notificationBadge');
                        
                        badge.textContent = data.unread_count;
                        
                        if (data.notifications.length === 0) {
                            notificationList.innerHTML = '<div style="text-align:center; padding: 20px; color: #666;">No notifications yet</div>';
                        } else {
                            let html = '';
                            data.notifications.forEach(notification => {
                                const date = new Date(notification.created_at).toLocaleString();
                                html += `
                                    <div class="notification-item ${notification.is_read == 0 ? 'unread' : ''}">
                                        <i class="fas fa-clipboard-check"></i>
                                        <div>
                                            <strong>${notification.title}</strong>
                                            <p>${notification.message}</p>
                                            <small style="color: #999;">${date}</small>
                                        </div>
                                    </div>
                                `;
                            });
                            notificationList.innerHTML = html;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    document.getElementById('notificationList').innerHTML = '<div style="text-align:center; padding: 20px; color: #999;">Failed to load notifications</div>';
                });
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const profileModal = document.getElementById('profileModal');
            const notificationModal = document.getElementById('notificationModal');
            if (event.target == profileModal) {
                profileModal.style.display = 'none';
            }
            if (event.target == notificationModal) {
                notificationModal.style.display = 'none';
            }
        }

        // Handle window resize
        window.addEventListener('resize', () => {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('collapsed');
            }
        });
    </script>
    
</body>

</html>
