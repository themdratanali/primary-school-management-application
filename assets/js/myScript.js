$(document).ready(function(){
    $('.slider').slick({
      dots: true,
      infinite: true,
      speed: 300,
      slidesToShow: 1,
      adaptiveHeight: true,
      autoplay:true,
      arrows: false,
    });
  
    $('.menu nav').meanmenu({
      meanScreenWidth: "480",
      meanMenuContainer: '.menu',
      meanMenuOpen: '<i class="fa-solid fa-bars"></i>',
    });
});

// Enhanced Sidebar Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    initSidebarToggle();
});

function initSidebarToggle() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (!toggleBtn || !sidebar) return;
    
    // Add click handler directly to button
    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        toggleSidebar();
    });
    
    // Toggle function
    window.toggleSidebar = function() {
        sidebar.classList.toggle('active');
        toggleBtn.classList.toggle('toggle-active');
        
        if (overlay) {
            overlay.classList.toggle('active');
        }
        
        // Save state to localStorage
        const isActive = sidebar.classList.contains('active');
        localStorage.setItem('sidebarState', isActive ? 'open' : 'closed');
    };
    
    // Close sidebar on overlay click
    if (overlay) {
        overlay.addEventListener('click', function() {
            toggleSidebar();
        });
    }
    
    // Close sidebar on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            toggleSidebar();
        }
    });
    
    // Restore sidebar state from localStorage
    const savedState = localStorage.getItem('sidebarState');
    if (savedState === 'open') {
        sidebar.classList.add('active');
        toggleBtn.classList.add('toggle-active');
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
            toggleBtn.classList.remove('toggle-active');
            if (overlay) {
                overlay.classList.remove('active');
            }
        }
    });
}
