<?php
$page_title = 'শূন্যপদের তালিকা - Apex Model School';
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
        margin-bottom: 20px;
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
    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .status-open {
        background-color: #d1ecf1;
        color: #0c5460;
    }
    .status-soon {
        background-color: #fff3cd;
        color: #856404;
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
    .apply-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    .apply-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        color: white;
        text-decoration: none;
    }
</style>

<div class="page-content">
    <div class="container" style=" max-width: 1050px;">
        <h1 class="page-title"><i class="fas fa-briefcase"></i> শূন্যপদের তালিকা</h1>
        <p class="page-subtitle">এ্যাপেক্স মডেল স্কুলে কর্মরত হওয়ার সুযোগ</p>

        <div class="table-container">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th style="width: 10%;">ক্রম নং</th>
                        <th style="width: 30%;">পদের নাম</th>
                        <th style="width: 20%;">বিষয়</th>
                        <th style="width: 15%;">যোগ্যতা</th>
                        <th style="width: 15%;">অবস্থা</th>
                        <th style="width: 10%;">আবেদন</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>১</td>
                        <td><strong>সহকারী শিক্ষক</strong></td>
                        <td>গণিত</td>
                        <td>স্নাতক/স্নাতকোত্তর</td>
                        <td><span class="status-badge status-open">আবেদন যোগ্য</span></td>
                        <td><button class="apply-btn">আবেদন করুন</button></td>
                    </tr>
                    <tr>
                        <td>২</td>
                        <td><strong>সহকারী শিক্ষক</strong></td>
                        <td>পদার্থ বিজ্ঞান</td>
                        <td>স্নাতক/স্নাতকোত্তর</td>
                        <td><span class="status-badge status-open">আবেদন যোগ্য</span></td>
                        <td><button class="apply-btn">আবেদন করুন</button></td>
                    </tr>
                    <tr>
                        <td>৩</td>
                        <td><strong>সহকারী শিক্ষক</strong></td>
                        <td>রসায়ন</td>
                        <td>স্নাতক/স্নাতকোত্তর</td>
                        <td><span class="status-badge status-soon">শীঘ্রই আসছে</span></td>
                        <td><button class="apply-btn" disabled>অপেক্ষা করুন</button></td>
                    </tr>
                    <tr>
                        <td>৪</td>
                        <td><strong>সহকারী শিক্ষক</strong></td>
                        <td>জীববিজ্ঞান</td>
                        <td>স্নাতক/স্নাতকোত্তর</td>
                        <td><span class="status-badge status-soon">শীঘ্রই আসছে</span></td>
                        <td><button class="apply-btn" disabled>অপেক্ষা করুন</button></td>
                    </tr>
                    <tr>
                        <td>৫</td>
                        <td><strong>অফিস সহকারী</strong></td>
                        <td>-</td>
                        <td>উচ্চ মাধ্যমিক</td>
                        <td><span class="status-badge status-open">আবেদন যোগ্য</span></td>
                        <td><button class="apply-btn">আবেদন করুন</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
