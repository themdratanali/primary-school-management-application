<?php

require_once __DIR__ . '/../../env/session.php';
include '../../env/config.php';

// Auth check
if (!isset($_SESSION['admin'])) {
    ams_redirect(ams_admin_url('login'));
    exit();
}

// Get section from query string
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
    <link rel="shortcut icon" type="image/jpg" href="<?php echo BASE_URL; ?>/uploads/images/এ্যাপেক্স মডেল স্কুল.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/admin_dashboard.css">
</head>

<body>

    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()" title="Toggle Sidebar">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="sidebar" id="sidebar">
        <h4>Apex Model School</h4>
<a href="javascript:void(0);" data-route="overview" data-src=<?php echo json_encode(ams_admin_url('overview')); ?> class="active">
             <i class="fas fa-gauge-high"></i>
             <span>Overview</span>
         </a>

        <div class="menu-item">
            <a>
                <span><i class="fas fa-graduation-cap"></i> Academic</span>
                <span class="toggle-icon">🡣</span>
            </a>
            <div class="submenu">
                <a href="javascript:void(0);" data-route="addbatch" data-src=<?php echo json_encode(ams_admin_url('add_batch')); ?>>
                    <i class="fas fa-layer-group"></i> Add Batch
                </a>
<a href="javascript:void(0);" data-route="addclass" data-src=<?php echo json_encode(ams_admin_url('add_class')); ?>>
                     <i class="fas fa-school"></i> Add Class
                 </a>
                 <a href="javascript:void(0);" data-route="addsubject" data-src=<?php echo json_encode(ams_admin_url('add_subject')); ?>>
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
<a href="javascript:void(0);" data-route="addteacher" data-src=<?php echo json_encode(ams_admin_url('add_teacher')); ?>>
                     <i class="fas fa-user-plus"></i> Add Teacher
                 </a>
                 <a href="javascript:void(0);" data-route="teachers" data-src=<?php echo json_encode(ams_admin_url('teachers_list')); ?>>
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
<a href="javascript:void(0);" data-route="addstaff" data-src=<?php echo json_encode(ams_admin_url('add_staff')); ?>>
                     <i class="fas fa-user-plus"></i> Add Staff
                 </a>
                 <a href="javascript:void(0);" data-route="stafflist" data-src=<?php echo json_encode(ams_admin_url('staff_list')); ?>>
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
<a href="javascript:void(0);" data-route="addstudent" data-src=<?php echo json_encode(ams_student_url('add_student')); ?>>
                     <i class="fas fa-user-plus"></i> Add Student
                 </a>
                 <a href="javascript:void(0);" data-route="students" data-src=<?php echo json_encode(ams_student_url('view_students')); ?>>
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
<a href="javascript:void(0);" data-route="admitcard" data-src=<?php echo json_encode(ams_student_url('admit_card')); ?>>
                     <i class="fas fa-id-card"></i> Single Admit Card
                 </a>
                 <a href="javascript:void(0);" data-route="alladmit" data-src=<?php echo json_encode(ams_admin_url('admit_card_bulk')); ?>>
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
<a href="javascript:void(0);" data-route="manageresults" data-src=<?php echo json_encode(ams_admin_url('manage_results')); ?>>
                     <i class="fas fa-pen-to-square"></i> Manage Results
                 </a>
                 <a href="javascript:void(0);" data-route="marksheet" data-src=<?php echo json_encode(ams_student_url('marksheet')); ?>>
                     <i class="fas fa-file-signature"></i> Mark Sheet
                 </a>
                 <a href="javascript:void(0);" data-route="marksheetbulk" data-src=<?php echo json_encode(ams_student_url('marksheet_bulk')); ?>>
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
<a href="javascript:void(0);" data-route="routine" data-src=<?php echo json_encode(ams_admin_url('manage_routine')); ?>>
                     <i class="fas fa-calendar-days"></i> Manage Routine
                 </a>
                 <a href="javascript:void(0);" data-route="syllabus" data-src=<?php echo json_encode(ams_admin_url('manage_syllabus')); ?>>
                     <i class="fas fa-book"></i> Manage Syllabus
                 </a>
                 <a href="javascript:void(0);" data-route="examresults" data-src=<?php echo json_encode(ams_admin_url('manage_exam_results')); ?>>
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
                <a href="javascript:void(0);" data-route="overviewfee" data-src=<?php echo json_encode(ams_admin_url('overview_fee')); ?>>
                    <i class="fas fa-chart-bar"></i> Fee Overview
                </a>
                <a href="javascript:void(0);" data-route="feereceipt" data-src=<?php echo json_encode(ams_student_url('fee_receipt')); ?>>
                    <i class="fas fa-receipt"></i> Fee Receipt
                </a>
                <a href="javascript:void(0);" data-route="feesee" data-src=<?php echo json_encode(ams_student_url('fee_see')); ?>>
                    <i class="fas fa-circle-info"></i> Fee See
                </a>
                <a href="javascript:void(0);" data-route="feestatus" data-src=<?php echo json_encode(ams_student_url('fee_status')); ?>>
                    <i class="fas fa-list-check"></i> Fee Status
                </a>
            </div>
        </div>

<a href="javascript:void(0);" data-route="promote" data-src=<?php echo json_encode(ams_student_url('promote_students')); ?>>
             <i class="fas fa-arrow-trend-up"></i> Promote Students
         </a>

        <div style="margin-top: auto; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.2);">
<a href="javascript:void(0);" data-route="backup" data-src=<?php echo json_encode(ams_admin_url('backup_database')); ?> style="background: rgba(255,255,255,0.1); margin-bottom: 10px;">
                 <i class="fas fa-database"></i> Database Backup
             </a>
        </div>

    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="top-bar-left">
            </div>
            <div class="top-bar-right">
                <div class="icon-btn profile-btn" onclick="showProfile()">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="icon-btn logout-btn" onclick="confirmLogout()">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
            </div>
        </div>
        <iframe name="mainFrame" src=<?php echo json_encode(ams_admin_url('overview')); ?> title="Admin dashboard content"></iframe>
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
                        $clean_photo = preg_replace('#^\.\./#', '', $admin_photo);
                        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $clean_photo)) {
                            echo '<img src="' . htmlspecialchars(BASE_URL . '/' . ltrim($clean_photo, '/'), ENT_QUOTES, 'UTF-8') . '" alt="Profile">';
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
<a href="javascript:void(0);" data-route="profile" data-src=<?php echo json_encode(ams_admin_url('admin_profile_edit')); ?> class="btn btn-sm btn-primary" style="margin-top: 10px;">
                         <i class="fas fa-edit"></i> Edit Profile
                     </a>
                </div>
            </div>
</div>
     </div>

     <script src="<?php echo BASE_URL; ?>/library/js/myscript.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const initialSectionFromServer = <?= json_encode($section ?: '') ?>;
            const mainFrame = document.querySelector('iframe[name="mainFrame"]');
            const portalBase = <?= json_encode(rtrim(ams_url(ADMIN_ROUTE_PREFIX), '/') . '/dashboard') ?>;
            const assetBase = <?= json_encode(BASE_URL) ?>;
            const defaultRoute = 'overview';
            const routes = {};

            document.querySelectorAll('[data-route][data-src]').forEach(function(link) {
                routes[link.getAttribute('data-route')] = link.getAttribute('data-src');
            });

            function segmentFromSrc(src) {
                if (!src) return 'dashboard';
                const parts = src.split('/').filter(Boolean);
                return parts[parts.length - 1] || 'dashboard';
            }

            function routeFromSegment(seg) {
                if (!seg || seg.toLowerCase() === 'dashboard') return defaultRoute;
                const normalized = seg.toLowerCase();
                for (const key of Object.keys(routes)) {
                    if (segmentFromSrc(routes[key]).toLowerCase() === normalized) {
                        return key;
                    }
                }
                return defaultRoute;
            }

            function injectFrameStyles() {
                try {
                    const doc = mainFrame.contentDocument;
                    if (!doc) return;
                    if (doc.querySelector('link[data-dashboard-frame]')) return;

                    const link = doc.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = assetBase + '/library/css/dashboard_frame.css';
                    link.setAttribute('data-dashboard-frame', '1');
                    doc.head && doc.head.appendChild(link);
                } catch (_) {
                }
            }

            function getRouteFromPath() {
                const prefix = portalBase + '/';
                const path = window.location.pathname;
                if (!path.toLowerCase().startsWith(prefix.toLowerCase())) return '';
                const seg = path.slice(prefix.length).split('/').filter(Boolean)[0] || '';
                return routeFromSegment(seg);
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

                const segment = route === defaultRoute ? 'overview' : segmentFromSrc(routes[route]);
                mainFrame.src = portalBase + '/page/' + segment;
                setActive(route);
                localStorage.setItem('adminCurrentRoute', route);

                const nextUrl = portalBase + '/' + segment;
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
                    if (!route || !routes[route]) return;
                    e.preventDefault();
                    e.stopPropagation();
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

         function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = <?= json_encode(ams_admin_url('logout')) ?>;
            }
        }

window.onclick = function(event) {
             const profileModal = document.getElementById('profileModal');
             if (event.target == profileModal) {
                 profileModal.style.display = 'none';
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












