<?php
$page_title = 'যোগাযোগ - Apex Model School';
include 'header.php';
?>
<style>
    .page-content {
        background-color: #f8f9fa;
        padding: 40px 20px;
        width: 85%;
        margin: auto;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    .page-header {
        background: linear-gradient(135deg, #008606 0%, #01cb0b 100%);
        color: white;
        padding: 40px 20px;
        text-align: center;
        border-radius: 15px 15px 0 0;
        margin: -20px -20px 30px;
        box-shadow: 0 4px 20px rgba(0, 134, 6, 0.3);
    }
    .page-header h1 {
        font-family: 'Poppins', sans-serif;
        font-size: 2.5rem;
        margin: 0;
        font-weight: 700;
    }
    .page-header p {
        font-family: 'Poppins', sans-serif;
        font-size: 1.1rem;
        margin: 10px 0 0;
        opacity: 0.9;
    }
    .page-title {
        color: #333;
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        font-size: 1.8rem;
        margin-bottom: 10px;
        text-align: center;
    }
    .page-subtitle {
        color: #666;
        text-align: center;
        margin-bottom: 30px;
        font-size: 1.1rem;
    }
    .contact-section {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        margin-bottom: 30px;
        transition: all 0.3s ease;
    }
    .contact-section:hover {
        box-shadow: 0 8px 30px rgba(0, 134, 6, 0.15);
    }
    .contact-section h3 {
        color: #008606;
        margin-bottom: 20px;
        border-bottom: 2px solid #008606;
        padding-bottom: 10px;
        font-family: 'Poppins', sans-serif;
    }
    .contact-info-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 20px;
        padding: 20px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9f5e9 100%);
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    .contact-info-item:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 15px rgba(0, 134, 6, 0.15);
    }
    .contact-info-item i {
        font-size: 28px;
        color: #008606;
        width: 60px;
        height: 60px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 10px rgba(0, 134, 6, 0.2);
        margin-right: 15px;
        flex-shrink: 0;
    }
    .contact-info-content h4 {
        color: #333;
        font-family: 'Poppins', sans-serif;
        margin: 0 0 5px;
        font-weight: 600;
    }
    .contact-info-content p {
        color: #666;
        margin: 0;
        font-family: 'Poppins', sans-serif;
    }
    .map-container {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
</style>

<div class="page-content">
    <div class="container" style="max-width: 1050px;">
        <h1 class="page-title">যোগাযোগ</h1>
        <p class="page-subtitle">এ্যাপেক্স মডেল স্কুলের সাথে যোগাযোগ করুন</p>
        
        <div class="row">
            <div>
                <div class="contact-section">
                    <h3><i class="fas fa-address-card"></i> যোগাযোগের তথ্য</h3>
                    
                    <div class="contact-info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div class="info-content">
                            <h4>ঠিকানা</h4>
                            <p>Kharkhari Bypass, Motihar, Paba, Rajshahi, Bangladesh</p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <i class="fas fa-phone"></i>
                        <div class="info-content">
                            <h4>ফোন</h4>
                            <p>01767-525258<br>01922-071515</p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <i class="fas fa-envelope"></i>
                        <div class="info-content">
                            <h4>ইমেইল</h4>
                            <p>info@apexmodelschool.edu.bd</p>
                        </div>
                    </div>
                                     
                    <div class="contact-info-item">
                        <i class="fas fa-clock"></i>
                        <div class="info-content">
                            <h4>অফিস সময়</h4>
                            <p>সোমবার - শুক্রবার: সকাল ৮:০০ - বিকাল ৪:০০<br>শনিবার: সকাল ৯:০০ - দুপুর ১:০০</p>
                        </div>
                    </div>
                    
                    <h4 style="margin-top: 30px; margin-bottom: 15px; color: #333;">সামাজিক মাধ্যম</h4>
                    <div class="social-links">
                        <a href="https://www.facebook.com/profile.php?id=100082713758873" target="_blank" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" title="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
