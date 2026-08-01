<?php
$page_title = "হোম | Apex Model School";
include '../public/layout/header.php';
?>

<div class="main-content" style="width: 1100px; margin: auto;">
<div class="popup-overlay" id="appPopup">
    <div class="popup-content">
        <div class="popup-header">
            <button class="popup-close" onclick="closePopup()"><i class="fas fa-times"></i></button>
            <img src="<?php echo BASE_URL; ?>/uploads/images/logo.png" alt="Apex Model School Logo" style="width: 60px; height: 60px; border-radius: 50%; margin-bottom: 10px; background: white; padding: 3px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        </div>
        <div class="popup-body">
            <p class="popup-title-text">স্কুলের সকল তথ্য আপনার মোবাইলে!</p>
            <p class="popup-text">আমাদের অ্যাপ ডাউনলোড করুন এবং সর্বদা আপডেট থাকুন। ফলাফল, নোটিশ, রুটিন সবকিছু এক ক্লিকে।</p>
            <div class="popup-features">
                <span class="popup-feature"><i class="fas fa-chart-line"></i> ফলাফল</span>
                <span class="popup-feature"><i class="fas fa-bell"></i> নোটিশ</span>
                <span class="popup-feature"><i class="fas fa-calendar-alt"></i> রুটিন</span>
                <span class="popup-feature"><i class="fas fa-home"></i> ছুটি</span>
            </div>
            
            <div class="popup-buttons">
                <a href="#" class="app-btn app-btn-android" onclick="installPWA()">
                    <i class="fab fa-android"></i> Android App
                </a>
                <a href="#" class="app-btn app-btn-ios" onclick="installPWA()">
                    <i class="fab fa-apple"></i> iOS App
                </a>
            </div>
            <p class="popup-note">স্কুলের সকল তথ্য পেতে অ্যাপ ডাউনলোড করুন</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if popup was already shown in this session
    if (!sessionStorage.getItem('apex_popup_shown')) {
        setTimeout(function() {
            document.getElementById('appPopup').style.display = 'flex';
        }, 1500);
        sessionStorage.setItem('apex_popup_shown', 'true');
    }
});

function closePopup() {
    document.getElementById('appPopup').style.display = 'none';
}

let deferredPrompt;

window.addEventListener('beforeinstallprompt', function(e) {
    e.preventDefault();
    deferredPrompt = e;
    // Show popup even if PWA is available
    setTimeout(function() {
        if (!sessionStorage.getItem('apex_popup_shown')) {
            document.getElementById('appPopup').style.display = 'flex';
        }
    }, 1500);
});

function installPWA() {
    if (deferredPrompt) {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(function(choiceResult) {
            if (choiceResult.outcome === 'accepted') {
                console.log('User accepted the A2HS prompt');
                closePopup();
            }
            deferredPrompt = null;
        });
    } else {
        alert('অ্যাপটি ইনস্টল করতে:\n\nChrome: থ্রি-ডট মেনু > Install App\nSafari: Share > Add to Home Screen');
        closePopup();
    }
}
</script>

<div class="main-content" style="width: 1100px; margin: auto;">
    <div class="slider">
        <div class="hero-slider">
            <div class="slider-wrapper">
                <div class="slide active">
                    <img src="<?php echo BASE_URL; ?>/uploads/images/photo-4.jpg" alt="School Photo">
                    <div class="slide-overlay"></div>
                    <div class="slide-content">
                        <h2>স্বাগতম</h2>
                        <p>আমরা মানসম্মত শিক্ষা প্রদান করি</p>
                    </div>
                </div>
                <div class="slide">
                    <img src="<?php echo BASE_URL; ?>/uploads/images/photo-1.jpg" alt="School Photo">
                    <div class="slide-overlay"></div>
                    <div class="slide-content">
                        <h2>আধুনিক শিক্ষা</h2>
                        <p>উন্নত প্রযুক্তির সাথে শিক্ষা</p>
                    </div>
                </div>
                <div class="slide">
                    <img src="<?php echo BASE_URL; ?>/uploads/images/photo-2.jpg" alt="School Photo">
                    <div class="slide-overlay"></div>
                    <div class="slide-content">
                        <h2>সুন্দর পরিবেশ</h2>
                        <p>শিশুদের জন্য নিরাপদ স্থান</p>
                    </div>
                </div>
                <div class="slide">
                    <img src="<?php echo BASE_URL; ?>/uploads/images/photo-3.jpg" alt="School Photo">
                    <div class="slide-overlay"></div>
                    <div class="slide-content">
                        <h2>ভবিষ্যত নেতা</h2>
                        <p>আজকের শিক্ষার্থী আগামীর নেতা</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        const prevBtn = document.querySelector('.prev-btn');
        const nextBtn = document.querySelector('.next-btn');
        let currentSlide = 0;
        const slideInterval = 5000; // 5 seconds auto change
        let autoSlide;

        function showSlide(n) {
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            currentSlide = (n + slides.length) % slides.length;
            slides[currentSlide].classList.add('active');
            dots[currentSlide].classList.add('active');
        }

        function nextSlide() {
            showSlide(currentSlide + 1);
        }

        function prevSlide() {
            showSlide(currentSlide - 1);
        }

        // Button click events
        prevBtn.addEventListener('click', () => {
            prevSlide();
            resetInterval();
        });

        nextBtn.addEventListener('click', () => {
            nextSlide();
            resetInterval();
        });

        // Dot click events
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
                resetInterval();
            });
        });

        function resetInterval() {
            clearInterval(autoSlide);
            autoSlide = setInterval(nextSlide, slideInterval);
        }

        // Start auto-sliding
        autoSlide = setInterval(nextSlide, slideInterval);
    });
    </script>
    
    <div class="notice-area ">
        <div class="notice-bg nfs">
            <h4 class="notice-title">নোটিশ </h4>
        </div>
        <div class="scroll-box">
            <ul>
                <li><a href="<?php echo ams_public_url('admission'); ?>"><i class="fa-solid fa-circle-arrow-right"></i> ভর্তি সম্পর্কে জানুন</a></li>
                <li><a href="<?php echo ams_public_url('routine'); ?>"><i class="fa-solid fa-circle-arrow-right"></i> ক্লাস রুটিন ডাউনলোড করুন </a></li>
                <li><a href="#"><i class="fa-solid fa-circle-arrow-right"></i> আমাদের শিক্ষা প্রতিষ্ঠানে স্বাগতম </a></li>
                <li><a href="#"><i class="fa-solid fa-circle-arrow-right"></i> জরুরী নোটিশ সমূহ </a></li>
            </ul>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-md-3 col-sm-12">
            <div class="notice-board-red clear">
                <div class="notice-red">
                    <ul>
                        <li><i class="fa-solid fa-angles-right"></i> <a href="<?php echo ams_public_url('syllabus'); ?>">সিলেবাস</a></li>
                        <li><i class="fa-solid fa-angles-right"></i> <a href="<?php echo ams_public_url('routine'); ?>">রুটিন</a></li>
                        <li><i class="fa-solid fa-angles-right"></i> <a href="<?php echo ams_public_url('results'); ?>">পরীক্ষার ফলাফল</a></li>
                        <li><i class="fa-solid fa-angles-right"></i> <a href="<?php echo ams_public_url('holidays'); ?>">ছুটির তালিকা</a></li>
                    </ul>
                </div>
            </div>

            <div class="notice-board clear">
                <div class="notice-bg">
                    <h4 class="notice-title">গুরুত্বপূর্ন নিয়মাবলী</h4>
                </div>
                <div class="notice-content" style="height: 250px";>
                    <ul class="scroll-notice">
                        <li><i class="fa-solid fa-angles-right"></i> <a href="">নির্দিষ্ট ইউনিফর্ম বাধ্যতামূলক।</a></li>
                        <li><i class="fa-solid fa-angles-right"></i> <a href="">সময়মতো স্কুলে আসা।</a></li>
                        <li><i class="fa-solid fa-angles-right"></i> <a href="">স্কুলে শৃঙ্খলা ও সম্মান রক্ষা।</a></li>
                        <li><i class="fa-solid fa-angles-right"></i> <a href="">ক্লাসে মনোযোগ ও অনুমতি নিয়ে কথা বলা।</a></li>
                        <li><i class="fa-solid fa-angles-right"></i> <a href="">নিয়মিত হোমওয়ার্ক জমা দেওয়া।</a></li>
                        <li><i class="fa-solid fa-angles-right"></i> <a href="">বইপত্র ও স্কুলের সম্পদ নষ্ট না করা।</a></li>
                        <li><i class="fa-solid fa-angles-right"></i> <a href="">পরস্পরের প্রতি সৌহার্দ্যপূর্ণ আচরণ।</a></li>
                        <li><i class="fa-solid fa-angles-right"></i> <a href="">অনুপস্থিতি ও ছুটির জন্য অনুমতি নেয়া।</a></li>
                        <li><i class="fa-solid fa-angles-right"></i> <a href="">মোবাইল আনা নিষিদ্ধ।</a></li>
                    </ul>
                </div>
            </div>

            <div class="notice-board clear">
                <div class="notice-bg">
                    <h4 class="notice-title">নোটিশ বোর্ড</h4>
                </div>
                <div class="notice-content">
                    <ul class="scroll-notice2">
                        <li><i class="fa-solid fa-angles-right"></i> <a href=""> প্রতি মাসের ৩০ তারিখ মধ্যে ফি পরিশোধ করতে হবে।</a></li>
                        <li><i class="fa-solid fa-angles-right"></i> <a href=""> শিক্ষার্থীর উপস্থিতি ৮০% এর কম হলে, পরীক্ষায় অংশগ্রহণের অনুমতি পাবে না।</a></li>
                        <li><i class="fa-solid fa-angles-right"></i> <a href=""> পরীক্ষায় অনুত্তীর্ণ হলে শিক্ষার্থীকে একই শ্রেণিতে পুনরায় অধ্যয়ন করতে হবে।</a></li>
                        <li><i class="fa-solid fa-angles-right"></i> <a href=""> প্রতি সপ্তাহে বৃহস্পতিবার শিক্ষার্থীদের অভিভাবকদের নিয়ে সভা অনুষ্ঠিত হয়, যেখানে সকল শিক্ষার্থীর অভিভাবকদের উপস্থিত থাকা বাধ্যতামূলক।</a></li>
                    </ul>
                </div>
            </div>
            <div class="notice-board clear">
                <div class="notice-bg">
                    <h4 class="notice-title">গুরুত্বপূর্ণ তথ্য</h4>
                </div>
                <div class="notice-content">
                    <ul>
                        <li><i class="fa-solid fa-angles-right"></i> মোবাইল: 01767-525258</li>                                     
                        <li><i class="fa-solid fa-angles-right"></i> মোবাইল: 01922-071515</li> 
                        <li><i class="fa-solid fa-angles-right"></i> ইমেইল: </li>   
                        <li><i class="fa-solid fa-angles-right"></i> <a href="https:/modelschool.vercel.app/">ওয়েবসাইট</a></li>
                                                            
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-9 col-md-9 col-sm-12">
            <div class="history-area clear ">
                <div class="notice-bg">
                    <h4 class="notice-title" style="text-align: left;">প্রতিষ্ঠানের ইতিহাস</h4>
                </div>
                <div class="notice-box">
                    <img  src="<?php echo BASE_URL; ?>/uploads/images/photo-5.jpg" class="notice-img" alt="School History">

                    <p class="notice-text">এ্যাপেক্স মডেল স্কুল প্রতিষ্ঠিত হয় ২০১৭ সালে। বিদ্যালয়ের লক্ষ্য হল মানসম্পন্ন শিক্ষা প্রদান, ছাত্রদের শারীরিক ও মানসিক উন্নয়ন, এবং নৈতিক মূল্যবোধ সৃষ্টি করা। স্কুলটি আধুনিক শিক্ষণ পদ্ধতি ব্যবহার করে এবং বিভিন্ন এক্সট্রা-কারিকুলার কার্যক্রমের সুযোগ দেয়। এ্যাপেক্স মডেল স্কুল দেশের অন্যতম সেরা শিক্ষা প্রতিষ্ঠান হিসেবে পরিচিতি লাভ করেছে।</p>
                </div>
            </div>

            <div class="speech-area clear">
                <div class="left-50">
                    <div class="notice-bg">
                        <h4 class="notice-title" style="text-align: left;">প্রধান শিক্ষকের</h4>
                    </div>
                    <div class="notice-box">
                        <img src="<?php echo BASE_URL; ?>/uploads/images/photo.webp" class="notice-img" alt="Chairman">
                        <p class="notice-text">এ্যাপেক্স মডেল স্কুল এ আপনাদের স্বাগতম! আমাদের লক্ষ্য হল মানসম্মত শিক্ষা প্রদান, যেখানে আপনারা শারীরিক, মানসিক ও সামাজিক দক্ষতা গড়ে তুলতে পারবেন। শিক্ষার পাশাপাশি নৈতিক মূল্যবোধ ও সামাজিক দায়িত্ববোধ গড়ে তোলা অত্যন্ত গুরুত্বপূর্ণ। কঠোর পরিশ্রম ও অধ্যবসায়ের মাধ্যমে আপনারা সফল হতে পারবেন। আমাদের শিক্ষকদের সহায়তায়, আমি বিশ্বাস করি, আপনারা ভবিষ্যতে জ্ঞান ও সাফল্যের পথে এগিয়ে যাবেন।</p>
                    </div>
                </div>
                <div class="right-50">
                    <div class="notice-bg">
                        <h4 class="notice-title" style="text-align: left;">সভাপতির</h4>
                    </div>
                    <div class="notice-box">
                        <img src="<?php echo BASE_URL; ?>/uploads/images/photo.webp" class="notice-img" alt="Chairman">
                        <p class="notice-text">এ্যাপেক্স মডেল স্কুল-এ আপনাদের স্বাগতম। আমাদের উদ্দেশ্য হল মানসম্মত শিক্ষা প্রদান, যা শিক্ষার্থীদের দক্ষতা ও নৈতিক মূল্যবোধ গড়ে তুলতে সহায়ক হবে। সুসংহত পরিবেশে শিক্ষা লাভ করলে শিক্ষার্থীরা সামগ্রিকভাবে বিকশিত হয়। আমরা সবসময় শিক্ষার মান উন্নয়নে কাজ করছি এবং নতুন ধারণা ও কার্যক্রমে অগ্রসর হতে প্রতিশ্রুতিবদ্ধ। আমি আশা করি, সকলের সহযোগিতায় এ্যাপেক্স মডেল স্কুল নতুন সাফল্য অর্জন করবে এবং শিক্ষার্থীরা তাদের প্রতিভা বিকাশের সুযোগ পাবেন।</p>
                    </div>
                </div>
            </div>
            <div class="info-area clear">
                <div class="left-50">
                    <div class="student info">
                        <div class="notice-bg">
                            <h4 class="notice-title" style="text-align: left;">ছাত্রছাত্রীদের তথ্য</h4>
                        </div>
                        <div class="info-box">
                            <img src="<?php echo BASE_URL; ?>/uploads/images/menu01.jpg" alt="Student Information">
                            <nav>
                                <ul>
                                    <li><i class="fa-solid fa-check"></i><a href="<?php echo ams_public_url('routine'); ?>"> রুটিন</a></li>
                                    <li><i class="fa-solid fa-check"></i><a href="<?php echo ams_public_url('syllabus'); ?>"> সিলেবাস</a></li>
                                    <li><i class="fa-solid fa-check"></i><a href="<?php echo ams_public_url('results'); ?>"> পরীক্ষার ফলাফল</a></li>
                                    <li><i class="fa-solid fa-check"></i><a href="<?php echo ams_public_url('seats'); ?>"> ছাত্রছাত্রীর আসন সংখ্যা</a></li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                    <div class="download info ">
                        <div class="notice-bg bg-red">
                            <h4 class="notice-title" style="text-align: left;">ডাউনলোড</h4>
                        </div>
                        <div class="info-box">
                            <img src="<?php echo BASE_URL; ?>/uploads/images/menu03.jpg" alt="Downloads">
                            <nav>
                                <ul>
                                    <li><i class="fa-solid fa-check"></i><a href="<?php echo ams_public_url('routine'); ?>"> ক্লাস - পরীক্ষার রুটিন</a></li>
                                    <li><i class="fa-solid fa-check"></i><a href="<?php echo ams_public_url('syllabus'); ?>"> সিলেবাস</a></li>
                                    <li><i class="fa-solid fa-check"></i><a href="<?php echo ams_public_url('admission'); ?>"> ভর্তি তথ্য</a></li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
                <div class="right-50">
                    <div class="teacher info">
                        <div class="notice-bg">
                            <h4 class="notice-title" style="text-align: left;">শিক্ষকদের তথ্য</h4>
                        </div>
                        <div class="info-box">
                            <img src="<?php echo BASE_URL; ?>/uploads/images/menu02.jpg" alt="Teacher Information">
                            <nav>
                                <ul>
                                    <li><i class="fa-solid fa-check"></i><a href="<?php echo ams_public_url('teachers'); ?>"> শিক্ষকবৃন্দ </a></li>
                                    <li><i class="fa-solid fa-check"></i><a href="<?php echo ams_public_url('staff'); ?>"> কর্মকর্তা কর্মচারী </a></li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                    <div class="academic info">
                        <div class="notice-bg bg-sky">
                            <h4 class="notice-title" style="text-align: left;">একাডেমিক তথ্য</h4>
                        </div>
                        <div class="info-box">
                            <img src="<?php echo BASE_URL; ?>/uploads/images/menu04.jpg" alt="Academic Information">
                            <nav>
                                <ul>
                                    <li><i class="fa-solid fa-check"></i><a href="<?php echo ams_public_url('classrooms'); ?>"> কক্ষ সংখ্যা</a></li>
                                    <li><i class="fa-solid fa-check"></i><a href="<?php echo ams_public_url('holidays'); ?>"> ছুটির তালিকা</a></li>
                                    <li><i class="fa-solid fa-check"></i><a href="<?php echo ams_public_url('seats'); ?>"> ছাত্রছাত্রীর আসন সংখ্যা</a></li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../public/layout/footer.php'; ?>