<?php

require_once __DIR__ . '/../includes/session.php';
include '../config/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}

$section = $_GET['section'] ?? '';
if (!is_string($section) || !preg_match('/^[A-Za-z0-9_-]{0,50}$/', $section)) {
    $section = '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - Apex Model School</title>
    <link rel="shortcut icon" type="image/jpg" href="../assets/img/এ্যাপেক্স মডেল স্কুল.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/apex/assets/fontawesome/fontawesome-free-6.4.0-web/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
</head>

<body>

    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()" title="Toggle Sidebar">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="sidebar" id="sidebar">
        <h4>Apex Model School</h4>
        <a href="overview" data-route="overview" data-src="overview.php" class="active">
            <i class="fas fa-gauge-high"></i>
            <span>Overview</span>
        </a>

        <div class="menu-item">
            <a>
                <span><i class="fas fa-graduation-cap"></i> Academic</span>
                <span class="toggle-icon">🡣</span>
            </a>
            <div class="submenu">
                <a href="addbatch" data-route="addbatch" data-src="add_batch.php">
                    <i class="fas fa-layer-group"></i> Add Batch
                </a>
                <a href="addclass" data-route="addclass" data-src="add_class.php">
                    <i class="fas fa-school"></i> Add Class
                </a>
                <a href="addsubject" data-route="addsubject" data-src="add_subject.php">
                    <i class="fas fa-book-open"></i> Add Subject
                </a>
            </div>
        </div>

        <div class="menu-item">
            <a>
                <span><i class="fas fa-chalkboard-teacher"></i> Teacher</span>
                <span class="toggle-icon">🡣</span>
            </a>
            <div class="submenu">
                <a href="addteacher" data-route="addteacher" data-src="../teacher/add_teacher.php">
                    <i class="fas fa-user-plus"></i> Add Teacher
                </a>
                <a href="teachers" data-route="teachers" data-src="../teacher/teachers_list.php">
                    <i class="fas fa-users"></i> Teacher List
                </a>
            </div>
        </div>

        <div class="menu-item">
            <a>
                <span><i class="fas fa-user-tie"></i> Staff</span>
                <span class="toggle-icon">🡣</span>
            </a>
            <div class="submenu">
                <a href="addstaff" data-route="addstaff" data-src="add_staff.php">
                    <i class="fas fa-user-plus"></i> Add Staff
                </a>
                <a href="stafflist" data-route="stafflist" data-src="staff_list.php">
                    <i class="fas fa-people-group"></i> Staff List
                </a>
            </div>
        </div>

        <div class="menu-item">
            <a>
                <span><i class="fas fa-user-graduate"></i> Student</span>
                <span class="toggle-icon">🡣</span>
            </a>
            <div class="submenu">
                <a href="addstudent" data-route="addstudent" data-src="../student/add_student.php">
                    <i class="fas fa-user-plus"></i> Add Student
                </a>
                <a href="students" data-route="students" data-src="../student/view_students.php">
                    <i class="fas fa-users"></i> Student List
                </a>
            </div>
        </div>

        <div class="menu-item">
            <a>
                <span><i class="fas fa-id-card"></i> Admit Card</span>
                <span class="toggle-icon">🡣</span>
            </a>
            <div class="submenu">
                <a href="admitcard" data-route="admitcard" data-src="../student/admit_card.php">
                    <i class="fas fa-id-card"></i> Single Admit Card
                </a>
                <a href="alladmit" data-route="alladmit" data-src="admit_card_bulk.php">
                    <i class="fas fa-clone"></i> All Admit
                </a>
            </div>
        </div>

        <div class="menu-item">
            <a>
                <span><i class="fas fa-file-lines"></i> Manage Results</span>
                <span class="toggle-icon">🡣</span>
            </a>
            <div class="submenu">
                <a href="manageresults" data-route="manageresults" data-src="manage_results.php">
                    <i class="fas fa-pen-to-square"></i> Manage Results
                </a>
                <a href="marksheet" data-route="marksheet" data-src="../student/marksheet.php">
                    <i class="fas fa-file-signature"></i> Mark Sheet
                </a>
                <a href="marksheetbulk" data-route="marksheetbulk" data-src="../student/marksheet_bulk.php">
                    <i class="fas fa-file-pdf"></i> All Mark Sheet PDF
                </a>
            </div>
        </div>

        <div class="menu-item">
            <a>
                <span><i class="fas fa-folder-open"></i> Academic Files</span>
                <span class="toggle-icon">🡣</span>
            </a>
            <div class="submenu">
                <a href="routine" data-route="routine" data-src="manage_routine.php">
                    <i class="fas fa-calendar-days"></i> Manage Routine
                </a>
                <a href="syllabus" data-route="syllabus" data-src="manage_syllabus.php">
                    <i class="fas fa-book"></i> Manage Syllabus
                </a>
                <a href="examresults" data-route="examresults" data-src="manage_exam_results.php">
                    <i class="fas fa-clipboard-check"></i> Manage Exam Results
                </a>
            </div>
        </div>

        <div class="menu-item">
            <a>
                <span><i class="fas fa-coins"></i> Fee Management</span>
                <span class="toggle-icon">🡣</span>
            </a>
            <div class="submenu">
                <a href="feereceipt" data-route="feereceipt" data-src="../student/fee_receipt.php">
                    <i class="fas fa-receipt"></i> Fee Receipt
                </a>
                <a href="feesee" data-route="feesee" data-src="../student/fee_see.php">
                    <i class="fas fa-circle-info"></i> Fee See
                </a>
                <a href="feestatus" data-route="feestatus" data-src="../student/fee_status.php">
                    <i class="fas fa-list-check"></i> Fee Status
                </a>
            </div>
        </div>

        <a href="promote" data-route="promote" data-src="../student/promote_students.php">
            <i class="fas fa-arrow-trend-up"></i> Promote Students
        </a>

        <div style="margin-top: auto; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.2);">
            <a href="backup" data-route="backup" data-src="backup_database.php" style="background: rgba(255,255,255,0.1); margin-bottom: 10px;">
                <i class="fas fa-database"></i> Database Backup
            </a>
        </div>

    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="top-bar-left">
            </div>
            <div class="top-bar-right">
                <div class="icon-btn notification-btn" onclick="loadAndShowNotifications()">
                    <i class="fas fa-bell"></i>
                    <span class="badge" id="notificationBadge">0</span>
                </div>
                <div class="icon-btn profile-btn" onclick="showProfile()">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="icon-btn logout-btn" onclick="confirmLogout()">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
            </div>
        </div>
        <iframe name="mainFrame" src="overview.php" title="Admin dashboard content"></iframe>
    </div>

    <div id="profileModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeProfile()">&times;</span>
            <h3>Admin Profile</h3>
            <div class="profile-info">
                <div class="profile-photo">
                    <?php 
                    $admin_photo = $_SESSION['admin_photo'] ?? '';
                    if (!empty($admin_photo)) {
                        if (file_exists('../' . $admin_photo)) {
                            echo '<img src="../' . htmlspecialchars($admin_photo, ENT_QUOTES, 'UTF-8') . '" alt="Profile">';
                        } else {
                            echo '<i class="fas fa-user-circle"></i>';
                        }
                    } else {
                        echo '<i class="fas fa-user-circle"></i>';
                    }
                    ?>
                </div>
                <div class="profile-details">
                    <p><strong>Name:</strong> <?= htmlspecialchars($_SESSION['admin_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($_SESSION['admin_email'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($_SESSION['admin_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Username:</strong> <?= htmlspecialchars($_SESSION['admin_username'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></p>
                    <a href="profile" data-route="profile" data-src="admin_profile_edit.php" class="btn btn-sm btn-primary" style="margin-top: 10px;">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

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

    <script src="../assets/js/myScript.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const initialSectionFromServer = <?= json_encode($section ?: '') ?>;
            const mainFrame = document.querySelector('iframe[name="mainFrame"]');

            const routes = {
                overview: 'overview.php',
                addbatch: 'add_batch.php',
                addclass: 'add_class.php',
                addsubject: 'add_subject.php',
                addteacher: '../teacher/add_teacher.php',
                teachers: '../teacher/teachers_list.php',
                addstaff: 'add_staff.php',
                stafflist: 'staff_list.php',
                addstudent: '../student/add_student.php',
                students: '../student/view_students.php',
                promote: '../student/promote_students.php',
                admitcard: '../student/admit_card.php',
                manageresults: 'manage_results.php',
                marksheet: '../student/marksheet.php',
                routine: 'manage_routine.php',
                syllabus: 'manage_syllabus.php',
                examresults: 'manage_exam_results.php',
                feereceipt: '../student/fee_receipt.php',
                feesee: '../student/fee_see.php',
                feestatus: '../student/fee_status.php',
                backup: 'backup_database.php',
                profile: 'admin_profile_edit.php',
            };

            const defaultRoute = 'overview';

            function getBasePath() {
                const p = window.location.pathname;
                const idx = p.toLowerCase().indexOf('/admin/');
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
                }
            }

            function getRouteFromPath() {
                const p = window.location.pathname;
                const idx = p.toLowerCase().indexOf('/admin/');
                if (idx === -1) return '';
                const after = p.slice(idx + '/admin/'.length);
                const seg = after.split('/').filter(Boolean)[0] || '';
                if (seg === '' || seg.toLowerCase() === 'dashboard') return '';
                return seg;
            }

            function setActive(route) {
                document.querySelectorAll('.sidebar a[data-route]').forEach(a => a.classList.remove('active'));
                const active = document.querySelector(`.sidebar a[data-route="${route}"]`);
                if (active) active.classList.add('active');

                document.querySelectorAll('.menu-item').forEach(menu => {
                    const submenu = menu.querySelector('.submenu');
                    if (!submenu) return;
                    menu.classList.remove('open');
                    submenu.style.display = 'none';
                });

                const activeInSubmenu = active && active.closest('.submenu');
                if (activeInSubmenu) {
                    const parentMenu = active.closest('.menu-item');
                    const submenu = parentMenu && parentMenu.querySelector('.submenu');
                    if (parentMenu && submenu) {
                        parentMenu.classList.add('open');
                        submenu.style.display = 'flex';
                    }
                }
            }

            function normalizeRoute(r) {
                if (!r || typeof r !== 'string') return '';
                return r.trim().toLowerCase();
            }

            function applyRoute(route, { push } = { push: false }) {
                route = normalizeRoute(route);
                if (!routes[route]) route = defaultRoute;

                mainFrame.src = routes[route];
                setActive(route);
                localStorage.setItem('adminCurrentRoute', route);

                const base = getBasePath();
                const nextUrl = `${base}admin/${route === defaultRoute ? 'dashboard' : route}`;
                const state = { route };
                if (push) {
                    history.pushState(state, '', nextUrl);
                } else {
                    history.replaceState(state, '', nextUrl);
                }
            }

            mainFrame.addEventListener('load', injectFrameStyles);

            document.querySelectorAll('.sidebar a[data-route], #profileModal a[data-route]').forEach(a => {
                a.addEventListener('click', (e) => {
                    const route = a.getAttribute('data-route');
                    const src = a.getAttribute('data-src');
                    if (!route || !src) return;
                    e.preventDefault();
                    routes[route] = src;
                    applyRoute(route, { push: true });
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
            const saved = normalizeRoute(localStorage.getItem('adminCurrentRoute') || '');
            const initial = routeFromUrl || normalizeRoute(initialSectionFromServer) || saved || defaultRoute;
            applyRoute(initial, { push: false });
        });

        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        window.toggleSidebar = function() {
            sidebar.classList.toggle('active');
            sidebarToggle.classList.toggle('toggle-active');
            sidebarOverlay.classList.toggle('active');
            
            if (window.innerWidth <= 768) {
                document.body.classList.toggle('sidebar-open');
            }
            
            const isActive = sidebar.classList.contains('active');
            localStorage.setItem('sidebarState', isActive ? 'open' : 'closed');
        };
        
        sidebarOverlay.addEventListener('click', toggleSidebar);
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                toggleSidebar();
            }
        });
        
        if (window.innerWidth <= 768 && localStorage.getItem('sidebarState') === 'open') {
            sidebar.classList.add('active');
            sidebarToggle.classList.add('toggle-active');
            sidebarOverlay.classList.add('active');
            document.body.classList.add('sidebar-open');
        }

        document.querySelectorAll('.menu-item > a').forEach(menuToggle => {
            menuToggle.addEventListener('click', (e) => {
                e.preventDefault();
                const menuItem = menuToggle.parentElement;
                menuItem.classList.toggle('open');
                const submenu = menuItem.querySelector('.submenu');
                if (submenu.style.display === "flex") {
                    submenu.style.display = "none";
                } else {
                    submenu.style.display = "flex";
                }
            });
        });

        function showProfile() {
            document.getElementById('profileModal').style.display = 'block';
        }

        function closeProfile() {
            document.getElementById('profileModal').style.display = 'none';
        }

        function showNotifications() {
            document.getElementById('notificationModal').style.display = 'block';
        }

        function closeNotifications() {
            document.getElementById('notificationModal').style.display = 'none';
        }

        function loadAndShowNotifications() {
            fetch('get_notifications.php')
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
                        
                        document.getElementById('notificationModal').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    document.getElementById('notificationList').innerHTML = '<div style="text-align:center; padding: 20px; color: #999;">Failed to load notifications</div>';
                });
        }

        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout';
            }
        }

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

        window.addEventListener('resize', () => {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('collapsed');
            }
        });
    </script>

</body>

</html>
