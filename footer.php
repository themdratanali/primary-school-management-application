<section id="footer" style="width: 100%;">
        <div class="container" style="max-width: 85%; margin: auto;">
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
                        <li><a href="/apex/">প্রথম পাতা</a></li>
                        <li><a href="/apex/teachers">শিক্ষকদের তথ্য</a></li>
                        <li><a href="/apex/member">কর্মকর্তা কর্মচারী</a></li>
                        <li><a href="/apex/admission">ভর্তি</a></li>
                        <li><a href="/apex/contact">যোগাযোগ</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-4 footer-section">
                    <h4 class="footer-title">লগইন</h4>
                    <ul class="footer-login-links">
                        <li><a href="/apex/student/student_login" target="_blank"><i class="fas fa-user-graduate"></i> Student Login</a></li>
                        <li><a href="/apex/teacher/login" target="_blank"><i class="fas fa-chalkboard-teacher"></i> Teacher Login</a></li>
                        <li><a href="/apex/admin/login" target="_blank"><i class="fas fa-user-tie"></i> Admin Login</a></li>
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

    <script src="/apex/assets/js/jquery-3.6.4.min.js"></script>
    <script src="/apex/assets/js/slick/slick.min.js"></script>
    <script src="/apex/assets/js/inc/jquery.meanmenu.min.js"></script>
    <script src="/apex/assets/js/myScript.js"></script>
    
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/apex/sw.js')
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
