<section id="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-4 footer-section">
                    <h4 class="footer-title">এ্যাপেক্স মডেল স্কুল</h4>
                    <p class="footer-text">
                        Kharkhari Bypass, Motihar, Paba, Rajshahi<br>
                        School Code: 484406 | IPEMIS Code: 113061636
                    </p>
                    <p class="footer-text">
                        <i class="fas fa-phone"></i> +880 1723-456789<br>
                        <i class="fas fa-envelope"></i> info@apexmodelschool.edu.bd
                    </p>
                </div>
                <div class="col-lg-4 col-md-4 footer-section">
                    <h4 class="footer-title">দ্রুত লিংক</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo BASE_URL; ?>/">প্রথম পাতা</a></li>
                        <li><a href="<?php echo ams_public_url('teachers'); ?>">শিক্ষকদের তথ্য</a></li>
                        <li><a href="<?php echo ams_public_url('admission'); ?>">ভর্তি</a></li>
                        <li><a href="<?php echo ams_public_url('contact'); ?>">যোগাযোগ</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-4 footer-section">
                    <h4 class="footer-title">লগইন</h4>
                    <ul class="footer-login-links">
                        <li><a href="<?php echo ams_student_url('login'); ?>" target="_blank"><i class="fas fa-user-graduate"></i> Student Login</a></li>

                        <li><a href="<?php echo ams_admin_url('login'); ?>" target="_blank"><i class="fas fa-user-tie"></i> Admin Login</a></li>
                    </ul>
                    <div class="social-icons">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section style="
    width: 1100px;
    margin: auto;">
        <div class="footer-bottom">
            <div class="container">
                <p class="copyright-text mb-0">Developed and Maintained by <a href="https://wedack.com" target="_blank" rel="noopener noreferrer">Wedack</a></p>
            </div>
        </div>
    </section>

    <script src="<?php echo BASE_URL; ?>/library/js/jquery-3.6.4.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/library/js/slick/slick.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/library/js/inc/jquery.meanmenu.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/library/js/myScript.js"></script>
    
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    })
                    .catch(function(err) {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            });
        }
        
        // Mobile Menu Toggle
        function toggleMobileMenu() {
            var nav = document.querySelector('.menu-modern nav');
            nav.classList.toggle('active');
        }
</script>







