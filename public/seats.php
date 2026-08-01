<?php
$page_title = 'ছাত্রছাত্রীর আসন সংখ্যা | Apex Model School';
include '../public/layout/header.php';
?>
<style>
    .page-content {
        background-color: #ffffff;
        padding: 40px 0;
        width: 1100px;
        margin: auto;
        box-shadow: 0 4px 20px rgba(160, 160, 160, 0.1);
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
    .badge-custom {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.9rem;
    }
    .badge-boys {
        background-color: #d1ecf1;
        color: #0c5460;
    }
    .badge-girls {
        background-color: #f8d7da;
        color: #721c24;
    }
    .no-data {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
    .total-row {
        font-weight: 700;
        background: linear-gradient(135deg, #f0f4ff 0%, #e8ecff 100%);
    }
</style>

<div class="page-content">
    <div class="container" style=" max-width: 1050px;">
        <h1 class="page-title">ছাত্রছাত্রীর আসন সংখ্যা</h1>
        <p class="page-subtitle">প্রতিটি শ্রেণীতে ছাত্রছাত্রীদের আসন সংখ্যা</p>
        <div class="table-container">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th style="width: 10%;">ক্রম নং</th>
                        <th style="width: 25%;">শ্রেণী</th>
                        <th style="width: 20%;">ছাত্র</th>
                        <th style="width: 20%;">ছাত্রী</th>
                        <th style="width: 15%;">মোট</th>
                        <th style="width: 10%;">অবস্থা</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>১</td>
                        <td>প্রথম শ্রেণী</td>
                        <td><span class="badge-custom badge-boys">৪০ জন</span></td>
                        <td><span class="badge-custom badge-girls">৩৫ জন</span></td>
                        <td><strong>৭৫ জন</strong></td>
                        <td><span class="badge-custom badge-boys">খালি আছে</span></td>
                    </tr>
                    <tr>
                        <td>২</td>
                        <td>দ্বিতীয় শ্রেণী</td>
                        <td><span class="badge-custom badge-boys">৪৫ জন</span></td>
                        <td><span class="badge-custom badge-girls">৩০ জন</span></td>
                        <td><strong>৭৫ জন</strong></td>
                        <td><span class="badge-custom badge-girls">পূর্ণ</span></td>
                    </tr>
                    <tr>
                        <td>৩</td>
                        <td>তৃতীয় শ্রেণী</td>
                        <td><span class="badge-custom badge-boys">৪২ জন</span></td>
                        <td><span class="badge-custom badge-girls">৩৩ জন</span></td>
                        <td><strong>৭৫ জন</strong></td>
                        <td><span class="badge-custom badge-boys">খালি আছে</span></td>
                    </tr>
                    <tr>
                        <td>৪</td>
                        <td>চতুর্থ শ্রেণী</td>
                        <td><span class="badge-custom badge-boys">৪৮ জন</span></td>
                        <td><span class="badge-custom badge-girls">২৭ জন</span></td>
                        <td><strong>৭৫ জন</strong></td>
                        <td><span class="badge-custom badge-girls">পূর্ণ</span></td>
                    </tr>
                    <tr>
                        <td>৫</td>
                        <td>পঞ্চম শ্রেণী</td>
                        <td><span class="badge-custom badge-boys">৫০ জন</span></td>
                        <td><span class="badge-custom badge-girls">২৫ জন</span></td>
                        <td><strong>৭৫ জন</strong></td>
                        <td><span class="badge-custom badge-girls">পূর্ণ</span></td>
                    </tr>
                    <tr>
                        <td>৬</td>
                        <td>ষষ্ঠ শ্রেণী</td>
                        <td><span class="badge-custom badge-boys">৫০ জন</span></td>
                        <td><span class="badge-custom badge-girls">২৫ জন</span></td>
                        <td><strong>৭৫ জন</strong></td>
                        <td><span class="badge-custom badge-girls">পূর্ণ</span></td>
                    </tr>
                    <tr>
                        <td>৭</td>
                        <td>সপ্তম শ্রেণী</td>
                        <td><span class="badge-custom badge-boys">৫০ জন</span></td>
                        <td><span class="badge-custom badge-girls">২৫ জন</span></td>
                        <td><strong>৭৫ জন</strong></td>
                        <td><span class="badge-custom badge-girls">পূর্ণ</span></td>
                    </tr>
                    <tr>
                        <td>৮</td>
                        <td>অষ্টম শ্রেণী</td>
                        <td><span class="badge-custom badge-boys">৪৫ জন</span></td>
                        <td><span class="badge-custom badge-girls">৩০ জন</span></td>
                        <td><strong>৭৫ জন</strong></td>
                        <td><span class="badge-custom badge-boys">খালি আছে</span></td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="2">মোট</td>
                        <td><strong>৩৭০ জন</strong></td>
                        <td><strong>২৩০ জন</strong></td>
                        <td><strong>৬০০ জন</strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../public/layout/footer.php'; ?>
