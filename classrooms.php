<?php
$page_title = 'কক্ষ সংখ্যা - Apex Model School';
include 'header.php';
?>
<style>
    .page-content {
        background-color: #f5f5f5;
        padding: 40px 0;
        width: 85%;
        margin: auto;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    .page-title {
        color: #333;
        font-weight: 700;
        font-size: 2rem;
        margin-bottom: 10px;
        text-align: center;
    }
    .page-subtitle {
        color: #666;
        text-align: center;
        margin-bottom: 30px;
        font-size: 1.1rem;
    }
    .table-container {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 40px;
    }
    .table {
        margin: 0;
    }
    .table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .table thead th {
        border: none;
        padding: 15px;
        font-weight: 600;
        text-align: center;
    }
    .table tbody td {
        padding: 12px 15px;
        border-bottom: 1px solid #e0e0e0;
        text-align: center;
        vertical-align: middle;
    }
    .table tbody tr:hover {
        background-color: #f9f9f9;
    }
    .no-data {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
    .no-data i {
        font-size: 48px;
        display: block;
        margin-bottom: 15px;
        color: #ddd;
    }
    .room-icon {
        font-size: 1.5rem;
        color: #667eea;
    }
</style>

<div class="page-content">
    <div class="container" style=" max-width: 1050px;">
        <h1 class="page-title">কক্ষ সংখ্যা</h1>
        <p class="page-subtitle">এ্যাপেক্স মডেল স্কুলের শ্রেণীকক্ষ ও অন্যান্য কক্ষের তথ্য</p>

        <div class="table-container">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th style="width: 10%;">ক্রম নং</th>
                        <th style="width: 30%;">কক্ষের নাম/নম্বর</th>
                        <th style="width: 25%;">ধরন</th>
                        <th style="width: 20%;">আসন সংখ্যা</th>
                        <th style="width: 15%;">বর্তমান অবস্থা</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>১</td>
                        <td><strong>শ্রেণীকক্ষ ১</strong></td>
                        <td><i class="fas fa-chalkboard-teacher room-icon"></i> শ্রেণীকক্ষ</td>
                        <td>৪০ জন</td>
                        <td><span class="badge-custom badge-boys">ব্যবহৃত হচ্ছে</span></td>
                    </tr>
                    <tr>
                        <td>২</td>
                        <td><strong>শ্রেণীকক্ষ ২</strong></td>
                        <td><i class="fas fa-chalkboard-teacher room-icon"></i> শ্রেণীকক্ষ</td>
                        <td>৪০ জন</td>
                        <td><span class="badge-custom badge-boys">ব্যবহৃত হচ্ছে</span></td>
                    </tr>
                    <tr>
                        <td>৩</td>
                        <td><strong>শ্রেণীকক্ষ ৩</strong></td>
                        <td><i class="fas fa-chalkboard-teacher room-icon"></i> শ্রেণীকক্ষ</td>
                        <td>৪০ জন</td>
                        <td><span class="badge-custom badge-boys">ব্যবহৃত হচ্ছে</span></td>
                    </tr>
                    <tr>
                        <td>৪</td>
                        <td><strong>শ্রেণীকক্ষ ৪</strong></td>
                        <td><i class="fas fa-chalkboard-teacher room-icon"></i> শ্রেণীকক্ষ</td>
                        <td>৪০ জন</td>
                        <td><span class="badge-custom badge-boys">ব্যবহৃত হচ্ছে</span></td>
                    </tr>
                    <tr>
                        <td>৫</td>
                        <td><strong>শ্রেণীকক্ষ ৫</strong></td>
                        <td><i class="fas fa-chalkboard-teacher room-icon"></i> শ্রেণীকক্ষ</td>
                        <td>৪০ জন</td>
                        <td><span class="badge-custom badge-boys">ব্যবহৃত হচ্ছে</span></td>
                    </tr>
                    <tr>
                        <td>৬</td>
                        <td><strong>শ্রেণীকক্ষ ৬</strong></td>
                        <td><i class="fas fa-chalkboard-teacher room-icon"></i> শ্রেণীকক্ষ</td>
                        <td>৪০ জন</td>
                        <td><span class="badge-custom badge-boys">ব্যবহৃত হচ্ছে</span></td>
                    </tr>
                    <tr>
                        <td>৭</td>
                        <td><strong>শ্রেণীকক্ষ ৭</strong></td>
                        <td><i class="fas fa-chalkboard-teacher room-icon"></i> শ্রেণীকক্ষ</td>
                        <td>৪০ জন</td>
                        <td><span class="badge-custom badge-boys">ব্যবহৃত হচ্ছে</span></td>
                    </tr>
                    <tr>
                        <td>৮</td>
                        <td><strong>শ্রেণীকক্ষ ৮</strong></td>
                        <td><i class="fas fa-chalkboard-teacher room-icon"></i> শ্রেণীকক্ষ</td>
                        <td>৪০ জন</td>
                        <td><span class="badge-custom badge-boys">ব্যবহৃত হচ্ছে</span></td>
                    </tr>
                    <tr>
                        <td>৯</td>
                        <td><strong>পদার্থ বিজ্ঞান ল্যাব</strong></td>
                        <td><i class="fas fa-flask room-icon"></i> পরীক্ষাগার</td>
                        <td>৩০ জন</td>
                        <td><span class="badge-custom badge-boys">ব্যবহৃত হচ্ছে</span></td>
                    </tr>
                    <tr>
                        <td>১০</td>
                        <td><strong>রসায়ন ল্যাব</strong></td>
                        <td><i class="fas fa-flask room-icon"></i> পরীক্ষাগার</td>
                        <td>৩০ জন</td>
                        <td><span class="badge-custom badge-boys">ব্যবহৃত হচ্ছে</span></td>
                    </tr>
                    <tr>
                        <td>১১</td>
                        <td><strong>জীববিজ্ঞান ল্যাব</strong></td>
                        <td><i class="fas fa-leaf room-icon"></i> পরীক্ষাগার</td>
                        <td>৩০ জন</td>
                        <td><span class="badge-custom badge-boys">ব্যবহৃত হচ্ছে</span></td>
                    </tr>
                    <tr>
                        <td>১২</td>
                        <td><strong>কম্পিউটার ল্যাব</strong></td>
                        <td><i class="fas fa-desktop room-icon"></i> কম্পিউটার ল্যাব</td>
                        <td>২৫ জন</td>
                        <td><span class="badge-custom badge-boys">ব্যবহৃত হচ্ছে</span></td>
                    </tr>
                    <tr>
                        <td>১৩</td>
                        <td><strong>গ্রন্থাগার</strong></td>
                        <td><i class="fas fa-book room-icon"></i> গ্রন্থাগার</td>
                        <td>৫০ জন</td>
                        <td><span class="badge-custom badge-boys">খোলা আছে</span></td>
                    </tr>
                    <tr>
                        <td>১৪</td>
                        <td><strong>স্টাফ রুম</strong></td>
                        <td><i class="fas fa-users room-icon"></i> স্টাফ রুম</td>
                        <td>২০ জন</td>
                        <td><span class="badge-custom badge-boys">ব্যবহৃত হচ্ছে</span></td>
                    </tr>
                    <tr>
                        <td>১৫</td>
                        <td><strong>প্রশাসনিক কার্যালয়</strong></td>
                        <td><i class="fas fa-building room-icon"></i> অফিস</td>
                        <td>-</td>
                        <td><span class="badge-custom badge-boys">খোলা আছে</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
