<?php
$page_title = 'ছুটির তালিকা - Apex Model School';
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
    .holiday-type {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .holiday-type.national {
        background-color: #fff3cd;
        color: #856404;
    }
    .holiday-type.religious {
        background-color: #d1ecf1;
        color: #0c5460;
    }
    .holiday-type.special {
        background-color: #f8d7da;
        color: #721c24;
    }
    .no-data {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
</style>

<div class="page-content">
    <div class="container" style=" max-width: 1050px;">
        <h1 class="page-title">ছুটির তালিকা</h1>
        <p class="page-subtitle">শিক্ষা বছরের সকল ছুটির তালিকা এবং সময়সূচী</p>

        <div class="table-container">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th style="width: 10%;">ক্রম নং</th>
                        <th style="width: 25%;">ছুটির কারণ</th>
                        <th style="width: 20%;">শুরু তারিখ</th>
                        <th style="width: 20%;">শেষ তারিখ</th>
                        <th style="width: 15%;">ধরন</th>
                        <th style="width: 10%;">দিন</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $holidays = [
                        ['name' => 'শীতকালীন ছুটি', 'start' => '১৫ ডিসেম্বর ২০২৫', 'end' => '৩০ ডিসেম্বর ২০২৫', 'type' => 'special', 'type_name' => 'বিশেষ', 'days' => 16],
                        ['name' => 'বড়দিন', 'start' => '২৫ ডিসেম্বর ২০২৫', 'end' => '২৫ ডিসেম্বর ২০২৫', 'type' => 'religious', 'type_name' => 'ধর্মীয়', 'days' => 1],
                        ['name' => 'পহেলা বৈশাখ', 'start' => '১৪ এপ্রিল ২০২৬', 'end' => '১৪ এপ্রিল ২০২৬', 'type' => 'national', 'type_name' => 'জাতীয়', 'days' => 1],
                        ['name' => 'গ্রীষ্মকালীন ছুটি', 'start' => '০১ মে ২০২৬', 'end' => '৩১ মে ২০২৫', 'type' => 'special', 'type_name' => 'বিশেষ', 'days' => 31],
                        ['name' => 'ঈদ-উল-ফিত্র', 'start' => '১০ মে ২০২৬', 'end' => '১২ মে ২০২৬', 'type' => 'religious', 'type_name' => 'ধর্মীয়', 'days' => 3],
                        ['name' => 'ঈদ-উল-জোহা', 'start' => '১৮ জুন ২০২৬', 'end' => '২০ জুন ২০২৬', 'type' => 'religious', 'type_name' => 'ধর্মীয়', 'days' => 3],
                        ['name' => 'শ্রাবণ মাসের সপ্তাহান্তে ছুটি', 'start' => '১৫ আগস্ট ২০২৬', 'end' => '১৬ আগস্ট ২০২৬', 'type' => 'special', 'type_name' => 'বিশেষ', 'days' => 2],
                        ['name' => 'মহান স্বাধীনতা দিবস', 'start' => '২৬ মার্চ ২০২৬', 'end' => '২৬ মার্চ ২০২৬', 'type' => 'national', 'type_name' => 'জাতীয়', 'days' => 1],
                        ['name' => 'বিজয় দিবস', 'start' => '১৬ ডিসেম্বর ২০২৪', 'end' => '১৬ ডিসেম্বর ২০২৪', 'type' => 'national', 'type_name' => 'জাতীয়', 'days' => 1],
                        ['name' => 'পয়লা ফাল্গুন', 'start' => '১৪ ফেব্রুয়ারি ২০২৬', 'end' => '১৪ ফেব্রুয়ারি ২০২৬', 'type' => 'national', 'type_name' => 'জাতীয়', 'days' => 1],
                        ['name' => 'আন্তর্জাতিক নারী দিবস', 'start' => '০৮ মার্চ ২০২৬', 'end' => '০৮ মার্চ ২০২৬', 'type' => 'national', 'type_name' => 'জাতীয়', 'days' => 1],
                    ];

                    if (!empty($holidays)):
                        $count = 1;
                        foreach ($holidays as $holiday):
                    ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td><strong><?= htmlspecialchars($holiday['name']) ?></strong></td>
                            <td><?= $holiday['start'] ?></td>
                            <td><?= $holiday['end'] ?></td>
                            <td><span class="holiday-type <?= $holiday['type'] ?>"><?= $holiday['type_name'] ?></span></td>
                            <td><?= $holiday['days'] ?> দিন</td>
                        </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="6" class="no-data">
                                <i class="fas fa-inbox"></i>
                                <p>কোনো তথ্য পাওয়া যায়নি</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
