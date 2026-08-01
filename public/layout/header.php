<!DOCTYPE html>
<html lang="en">
<?php
include '../env/config.php';

$noindex = isset($noindex) && $noindex === true;
$page_description = isset($page_description) ? $page_description : 'Apex Model School - Quality Education for All. Located in Kharkhari Bypass, Motihar, Paba, Rajshahi. School Code: 484406';
$page_keywords = isset($page_keywords) ? $page_keywords : 'Apex Model School, এ্যাপেক্স মডেল স্কুল, school, education, Rajshahi, Bangladesh';
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($noindex): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php else: ?>
    <meta name="robots" content="index, follow">
    <?php endif; ?>
    <link rel="canonical" href="https:/modelschool.edu.bd<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/') ?>">
    <link rel="shortcut icon" type="image/jpg" href="<?php echo BASE_URL; ?>/uploads/images/এ্যাপেক্স মডেল স্কুল.png"/>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/fontawesome/css/all.min.css">
    <link href="<?php echo BASE_URL; ?>/library/bootstrap/css/bootstrap.min.css" rel="stylesheet" >
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Noto+Serif+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/library/css/responsive.css">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Apex Model School' ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($page_keywords) ?>">
    <meta name="author" content="Apex Model School">
    <meta name="theme-color" content="#008606">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https:/modelschool.edu.bd/">
    <meta property="og:title" content="<?= isset($page_title) ? htmlspecialchars($page_title) : 'Apex Model School' ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta property="og:image" content="https:/modelschool.edu.bd/uploads/images/logo.png">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https:/modelschool.edu.bd/">
    <meta property="twitter:title" content="<?= isset($page_title) ? htmlspecialchars($page_title) : 'Apex Model School' ?>">
    <meta property="twitter:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta property="twitter:image" content="https:/modelschool.edu.bd/uploads/images/logo.png">
    
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.json">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/uploads/images/logo.png">
    <style>
        :root {
            --primary-color: #008606;
            --primary-dark: #006b05;
            --primary-light: #01cb0b;
            --accent-color: #ffc107;
            --text-dark: #1a1a2e;
            --text-light: #ffffff;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 30px rgba(0, 134, 6, 0.2);
        }
    </style>
</head>
<body>
    <div class="main-website">
        <div class="container" style="max-width: 1200px;">
            <div class="row">
                <div class="col-lg-12">
                    <div class="header-modern">
                        <div class="header-left">
                            <div class="header-logo">
                                <img src="<?php echo BASE_URL; ?>/uploads/images/এ্যাপেক্স মডেল স্কুল.png" alt="Apex Model School Logo">
                            </div>
                            <div class="header-content">
                                <h1 class="school-name">Apex Model School</h1>
                                <p class="school-tagline">মানসম্মত শিক্ষা, উজ্জ্বল ভবিষ্যৎ</p>
                                <div class="school-info">
                                    <span><i class="fas fa-map-marker-alt"></i> Kharkhari Bypass, Motihar, Paba, Rajshahi</span>
                                    <span><i class="fas fa-school"></i>School Code: 484406 | IPEMIS Code: 113061636</span>
                                </div>
                            </div>
                        </div>
                        <div class="header-right">
                            <img src="<?php echo BASE_URL; ?>/uploads/images/bd.png" alt="Bangladesh" class="bd-flag">
                        </div>
                    </div>
                    <div class="menu-modern">
                        <div class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                            <i class="fas fa-bars"></i>
                        </div>
                        <nav style="background: #008606;">
                            <ul id="menu-list">
                                <li class="active"><a href="<?php echo BASE_URL; ?>/"><i class="fas fa-home"></i> হোম</a></li>
                                <li><a href="#"><i class="fas fa-graduation-cap"></i> একাডেমিক</a>
                                    <ul>
                                    <li><a href="<?php echo ams_public_url('teachers'); ?>"><i class="fas fa-user-tie"></i> শিক্ষকদের তথ্য</a></li>
                                    <li><a href="<?php echo ams_public_url('classrooms'); ?>"><i class="fas fa-building"></i> কক্ষ সংখ্যা</a></li>
                                    </ul>
                                </li>
                                <li><a href="#"><i class="fas fa-book"></i>শিক্ষার্থী</a>
                                    <ul class="student">
                                        <li><a href="<?php echo ams_public_url('seats'); ?>"><i class="fas fa-chair"></i> আসন সংখ্যা</a></li>
                                        <li><a href="<?php echo ams_public_url('routine'); ?>"><i class="fas fa-calendar-alt"></i> রুটিন</a></li>
                                        <li><a href="<?php echo ams_public_url('syllabus'); ?>"><i class="fas fa-list"></i> সিলেবাস</a></li>
                                        <li><a href="<?php echo ams_public_url('results'); ?>"><i class="fas fa-chart-line"></i> পরীক্ষার ফলাফল</a></li>
                                    </ul>
                                </li>
                                <li><a href="<?php echo ams_public_url('holidays'); ?>"><i class="fas fa-calendar-days"></i> ছুটির তালিকা</a></li>
                                <li><a href="<?php echo ams_public_url('admission'); ?>"><i class="fas fa-user-plus"></i> ফরমভর্তি</a></li>
                                <li><a href="<?php echo ams_public_url('contact'); ?>"><i class="fas fa-envelope"></i> যোগাযোগ</a></li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>







