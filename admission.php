<?php
$page_title = 'ভর্তি - Apex Model School';
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
    .admission-info {
        background: white;
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 25px;
        border-left: 5px solid #008606;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }
    .admission-info:hover {
        box-shadow: 0 8px 30px rgba(0, 134, 6, 0.15);
        transform: translateY(-2px);
    }
    .admission-info h3 {
        color: #008606;
        margin-bottom: 15px;
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
    }
    .admission-info p {
        line-height: 1.8;
        font-size: 16px;
        color: #555;
    }
    .fee-table {
        margin-top: 20px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    .fee-table thead {
        background: linear-gradient(135deg, #008606 0%, #01cb0b 100%);
        color: white;
        font-family: 'Poppins', sans-serif;
    }
    .fee-table th, .fee-table td {
        text-align: center;
        padding: 15px;
        font-family: 'Poppins', sans-serif;
    }
    .fee-table tbody tr {
        transition: all 0.3s ease;
    }
    .fee-table tbody tr:hover {
        background-color: #f0f9f0;
    }
    .cta-button {
        display: inline-block;
        padding: 15px 40px;
        background: linear-gradient(135deg, #008606 0%, #01cb0b 100%);
        color: white;
        text-decoration: none;
        border-radius: 30px;
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 134, 6, 0.3);
    }
    .cta-button:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 134, 6, 0.4);
        color: white;
    }
    .card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-top: 30px;
    }
    .feature-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }
    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0, 134, 6, 0.2);
    }
    .feature-card i {
        font-size: 3rem;
        color: #008606;
        margin-bottom: 15px;
    }
    .feature-card h4 {
        color: #333;
        font-family: 'Poppins', sans-serif;
        margin-bottom: 10px;
    }
    .feature-card p {
        color: #666;
        font-size: 0.95rem;
    }
</style>

<div class="page-content">
    <div class="container" style="max-width: 1050px;">
        <h1 class="page-title">এ্যাপেক্স মডেল স্কুলে ভর্তি বিজ্ঞপ্তি</h1>
        <p class="page-subtitle">২০২৫ শিক্ষাবর্ষে নতুন শিক্ষার্থী ভর্তি কার্যক্রম</p>
        
        <div class="admission-info">
            <h3><i class="fas fa-info-circle"></i> ভর্তির সাধারণ তথ্য</h3>
            <p>
                এ্যাপেক্স মডেল স্কুলে ২০২৫ শিক্ষাবর্ষে নতুন শিক্ষার্থী ভর্তি কার্যক্রম শুরু হয়েছে। 
                আমাদের প্রতিষ্ঠানে পড়ালেখার জন্য আগ্রহী শিক্ষার্থীদের নির্ধারিত সময়ের মধ্যে ভর্তি ফরম পূরণ করতে হবে। 
                আমরা মানসম্মত শিক্ষা নিশ্চিত করতে প্রতিশ্রুতিবদ্ধ এবং প্রতিটি শিক্ষার্থীর সার্বিক বিকাশে সচেষ্ট। 
                আমাদের শিক্ষক মণ্ডলী অভিজ্ঞ ও যোগ্য, যারা শিক্ষার্থীদের সর্বোত্তম শিক্ষা প্রদানে নিবেদিত।
            </p>
            <p>
                <strong>ভর্তির যোগ্যতা:</strong> নির্দিষ্ট ক্লাসের জন্য ন্যূনতম বয়স এবং পূর্ববর্তী শিক্ষাগত যোগ্যতা থাকতে হবে। 
                ভর্তি প্রক্রিয়া সম্পন্ন করতে সমস্ত প্রয়োজনীয় কাগজপত্র সঠিকভাবে জমা দিতে হবে।
            </p>
            <p>
                <strong>ভর্তির সময়সীমা:</strong> নির্ধারিত তারিখের মধ্যে ভর্তি প্রক্রিয়া সম্পন্ন না করলে ভর্তি বাতিল বলে গণ্য হবে।
            </p>
        </div>

        <div class="fee-table">
            <h3 class="section-title"><i class="fas fa-coins"></i> ফি তালিকা</h3>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ক্রমিক</th>
                            <th>ক্লাস</th>
                            <th>ভর্তি ফি (টাকা)</th>
                            <th>মাসিক বেতন (টাকা)</th>
                            <th>বার্ষিক ফি (টাকা)</th>
                            <th>সেমিস্টার ফি (টাকা)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>১</td>
                            <td>প্রথম শ্রেণি</td>
                            <td>৫,০০০</td>
                            <td>১,০০০</td>
                            <td>২,০০০</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>২</td>
                            <td>দ্বিতীয় শ্রেণি</td>
                            <td>৫,০০০</td>
                            <td>১,০০০</td>
                            <td>২,০০০</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>৩</td>
                            <td>তৃতীয় শ্রেণি</td>
                            <td>৫,০০০</td>
                            <td>১,০০০</td>
                            <td>২,০০০</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>৪</td>
                            <td>চতুর্থ শ্রেণি</td>
                            <td>৫,০০০</td>
                            <td>১,০০০</td>
                            <td>২,০০০</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>৫</td>
                            <td>পঞ্চম শ্রেণি</td>
                            <td>৫,০০০</td>
                            <td>১,২০০</td>
                            <td>২,৫০০</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>৬</td>
                            <td>ষষ্ঠ শ্রেণি</td>
                            <td>৬,০০০</td>
                            <td>১,৫০০</td>
                            <td>৩,০০০</td>
                            <td>২,০০০</td>
                        </tr>
                        <tr>
                            <td>৭</td>
                            <td>সপ্তম শ্রেণি</td>
                            <td>৬,০০০</td>
                            <td>১,৫০০</td>
                            <td>৩,০০০</td>
                            <td>২,০০০</td>
                        </tr>
                        <tr>
                            <td>৮</td>
                            <td>অষ্টম শ্রেণি</td>
                            <td>৭,০০০</td>
                            <td>১,৮০০</td>
                            <td>৩,৫০০</td>
                            <td>২,৫০০</td>
                        </tr>
                        <tr>
                            <td>৯</td>
                            <td>নবম শ্রেণি</td>
                            <td>৮,০০০</td>
                            <td>২,০০০</td>
                            <td>৪,০০০</td>
                            <td>৩,০০০</td>
                        </tr>
                        <tr>
                            <td>১০</td>
                            <td>দশম শ্রেণি</td>
                            <td>৮,০০০</td>
                            <td>২,০০০</td>
                            <td>৪,০০০</td>
                            <td>৩,০০০</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="fee-table">
            <h3 class="section-title"><i class="fas fa-file-invoice-dollar"></i> অতিরিক্ত ফি</h3>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ক্রমিক</th>
                            <th>খাত</th>
                            <th>টাকা</th>
                            <th>মন্তব্য</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>১</td>
                            <td>প্রগ্রেস রিপোর্ট</td>
                            <td>২০০</td>
                            <td>বার্ষিক</td>
                        </tr>
                        <tr>
                            <td>২</td>
                            <td>অ্যাসাইনমেন্ট ফি</td>
                            <td>৩০০</td>
                            <td>সেমিস্টার</td>
                        </tr>
                        <tr>
                            <td>৩</td>
                            <td>লাইব্রেরি ফি</td>
                            <td>৫০০</td>
                            <td>বার্ষিক</td>
                        </tr>
                        <tr>
                            <td>৪</td>
                            <td>স্পোর্টস ফি</td>
                            <td>৪০০</td>
                            <td>বার্ষিক</td>
                        </tr>
                        <tr>
                            <td>৫</td>
                            <td>পরীক্ষা ফি</td>
                            <td>১,০০০</td>
                            <td>বার্ষিক</td>
                        </tr>
                        <tr>
                            <td>৬</td>
                            <td>ইউনিফর্ম</td>
                            <td>১,৫০০</td>
                            <td>একবার</td>
                        </tr>
                        <tr>
                            <td>৭</td>
                            <td>আইডি কার্ড</td>
                            <td>২০০</td>
                            <td>একবার</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="documents-list">
            <h4><i class="fas fa-clipboard-check"></i> ভর্তির জন্য প্রয়োজনীয় কাগজপত্র</h4>
            <ul>
                <li>জন্ম নিবন্ধন সনদপত্র (ফটোকপি)</li>
                <li>প্রাতিষ্ঠানিক ফটোকপি (টি.সি./প্রোভিশনাল সার্টিফিকেট)</li>
                <li>পাসপোর্ট সাইজ ছবি (৪ কপি)</li>
                <li>ছাত্র/ছাত্রীর জাতীয় পরিচয়পত্র (১৮ বছরের বেশি হলে)</li>
                <li>অভিভাবকের জাতীয় পরিচয়পত্রের ফটোকপি</li>
                <li>পূর্ববর্তী শ্রেণির মার্কশিট</li>
                <li>ভর্তি ফি ও অন্যান্য ফি জমার রসিদ</li>
                <li>স্বাস্থ্য পরীক্ষার সনদপত্র</li>
                <li>অভিভাবকের আয়ের প্রমাণপত্র</li>
                <li>বিগত বছরের ফলাফল (যদি থাকে)</li>
            </ul>
        </div>

        <div class="contact-info">
            <h5><i class="fas fa-phone-alt"></i> যোগাযোগের জন্য</h5>
            <p><strong>ফোন:</strong> +880 1723-456789</p>
            <p><strong>ইমেইল:</strong> info@apexmodelschool.edu.bd</p>
            <p><strong>ঠিকানা:</strong> Kharkhari Bypass, Motihar, Paba, Rajshahi</p>
            <p><strong>ভর্তি সংক্রান্ত যেকোনো তথ্যের জন্য কল করুন অথবা সরাসরি প্রতিষ্ঠানে যোগাযোগ করুন।</strong></p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
