<?php
$page_title = 'কর্মকর্তা কর্মচারী - Apex Model School';
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
    .staff-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        margin-bottom: 40px;
    }
    @media (max-width: 1200px) {
        .staff-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    @media (max-width: 768px) {
        .staff-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 480px) {
        .staff-grid {
            grid-template-columns: repeat(1, 1fr);
        }
    }
    .staff-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        text-align: center;
        border-top: 4px solid #667eea;
    }
    .staff-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .staff-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2rem;
        font-weight: 700;
        margin: 0 auto 20px;
        overflow: hidden;
        border: 4px solid #f0f0f0;
    }
    .staff-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .staff-name {
        font-size: 1.3rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 8px;
    }
    .staff-position {
        color: #667eea;
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 15px;
    }
    .staff-details {
        border-top: 1px solid #e0e0e0;
        padding-top: 15px;
        margin-top: 15px;
    }
    .staff-detail-item {
        margin-bottom: 10px;
        color: #666;
        font-size: 0.9rem;
    }
    .staff-detail-item strong {
        color: #333;
    }
    .staff-detail-item i {
        color: #667eea;
        width: 20px;
        margin-right: 8px;
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
</style>

<div class="page-content">
    <div class="container" style=" max-width: 1050px;">
        <h1 class="page-title">কর্মকর্তা কর্মচারী</h1>
        <p class="page-subtitle">আমাদের স্কুলের কর্মকর্তা ও কর্মচারীদের তথ্য</p>
        
        <!-- Staff content would go here -->
        <div class="staff-grid">
            <div class="staff-card">
                <div class="staff-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="staff-name"> কর্মকর্তা ১</div>
                <div class="staff-position">পদবী</div>
                <div class="staff-details">
                    <div class="staff-detail-item"><i class="fas fa-phone"></i> ০১৭১২৩৪৫৬৭৮</div>
                    <div class="staff-detail-item"><i class="fas fa-envelope"></i> email@example.com</div>
                </div>
            </div>
            <div class="staff-card">
                <div class="staff-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="staff-name"> কর্মকর্তা ২</div>
                <div class="staff-position">পদবী</div>
                <div class="staff-details">
                    <div class="staff-detail-item"><i class="fas fa-phone"></i> ০১৭১২৩৪৫৬৭৮</div>
                    <div class="staff-detail-item"><i class="fas fa-envelope"></i> email@example.com</div>
                </div>
            </div>
            <div class="staff-card">
                <div class="staff-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="staff-name"> কর্মকর্তা ৩</div>
                <div class="staff-position">পদবী</div>
                <div class="staff-details">
                    <div class="staff-detail-item"><i class="fas fa-phone"></i> ০১৭১২৩৪৫৬৭৮</div>
                    <div class="staff-detail-item"><i class="fas fa-envelope"></i> email@example.com</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
