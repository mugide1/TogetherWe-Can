        <?php if(isset($_SESSION['user_id'])): ?>
        </div>
        <?php endif; ?>

<script>
// Desktop: Toggle sidebar collapse/expand with arrow
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    var mainContent = document.getElementById('mainContent');
    
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
    
    // Save state for desktop
    if (sidebar.classList.contains('collapsed')) {
        localStorage.setItem('sidebarCollapsed', 'true');
    } else {
        localStorage.setItem('sidebarCollapsed', 'false');
    }
}

// Mobile: Show/hide sidebar with hamburger
function toggleMobileSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('mobileOverlay');
    
    sidebar.classList.toggle('mobile-visible');
    
    if (sidebar.classList.contains('mobile-visible')) {
        overlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
    } else {
        overlay.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function closeMobileSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('mobileOverlay');
    
    sidebar.classList.remove('mobile-visible');
    overlay.style.display = 'none';
    document.body.style.overflow = '';
}

// Load saved state for desktop only
document.addEventListener('DOMContentLoaded', function() {
    // Only apply desktop collapse on screens larger than mobile
    if (window.innerWidth > 768) {
        var sidebarCollapsed = localStorage.getItem('sidebarCollapsed');
        var sidebar = document.getElementById('sidebar');
        var mainContent = document.getElementById('mainContent');
        
        if (sidebarCollapsed === 'true') {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }
    }
});

// Handle window resize
window.addEventListener('resize', function() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('mobileOverlay');
    
    if (window.innerWidth > 768) {
        // Desktop mode
        sidebar.classList.remove('mobile-visible');
        if (overlay) overlay.style.display = 'none';
        document.body.style.overflow = '';
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
        <?php if(isset($_SESSION['user_id'])): ?>
        </div>
    </div>
        <?php endif; ?>

<script>
// Desktop: Toggle sidebar collapse/expand
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('collapsed');
    
    // Save state for desktop
    if (sidebar.classList.contains('collapsed')) {
        localStorage.setItem('sidebarCollapsed', 'true');
    } else {
        localStorage.setItem('sidebarCollapsed', 'false');
    }
}

// Mobile: Show/hide sidebar
function toggleMobileSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('mobileOverlay');
    
    sidebar.classList.toggle('mobile-visible');
    
    if (sidebar.classList.contains('mobile-visible')) {
        overlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
    } else {
        overlay.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function closeMobileSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('mobileOverlay');
    
    sidebar.classList.remove('mobile-visible');
    overlay.style.display = 'none';
    document.body.style.overflow = '';
}

// Load saved state for desktop only
document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth > 768) {
        var sidebarCollapsed = localStorage.getItem('sidebarCollapsed');
        var sidebar = document.getElementById('sidebar');
        
        if (sidebarCollapsed === 'true') {
            sidebar.classList.add('collapsed');
        }
    }
});

// Handle window resize
window.addEventListener('resize', function() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('mobileOverlay');
    
    if (window.innerWidth > 768) {
        sidebar.classList.remove('mobile-visible');
        if (overlay) overlay.style.display = 'none';
        document.body.style.overflow = '';
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
        <?php if(isset($_SESSION['user_id'])): ?>
        </div>
    </div>
        <?php endif; ?>

<script>
// Desktop: Toggle sidebar collapse/expand
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('collapsed');
    
    // Save state for desktop
    if (sidebar.classList.contains('collapsed')) {
        localStorage.setItem('sidebarCollapsed', 'true');
    } else {
        localStorage.setItem('sidebarCollapsed', 'false');
    }
}

// Mobile: Show/hide sidebar
function toggleMobileSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('mobileOverlay');
    
    sidebar.classList.toggle('mobile-visible');
    
    if (sidebar.classList.contains('mobile-visible')) {
        overlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
    } else {
        overlay.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function closeMobileSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('mobileOverlay');
    
    sidebar.classList.remove('mobile-visible');
    overlay.style.display = 'none';
    document.body.style.overflow = '';
}

// Load saved state for desktop only
document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth > 768) {
        var sidebarCollapsed = localStorage.getItem('sidebarCollapsed');
        var sidebar = document.getElementById('sidebar');
        
        if (sidebarCollapsed === 'true') {
            sidebar.classList.add('collapsed');
        }
    }
});

// Handle window resize
window.addEventListener('resize', function() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('mobileOverlay');
    
    if (window.innerWidth > 768) {
        sidebar.classList.remove('mobile-visible');
        if (overlay) overlay.style.display = 'none';
        document.body.style.overflow = '';
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
