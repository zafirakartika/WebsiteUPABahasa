<?php
// student/includes/sidebar.php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Student Sidebar -->
<div class="col-md-3 col-lg-2 sidebar p-0">
    <div class="text-white p-4">
        <div class="text-center mb-4">
            <i class="bi bi-mortarboard" style="font-size: 2.5rem;"></i>
            <h5 class="mt-2">UPA Bahasa</h5>
            <small>UPNVJ</small>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link <?= $current_page === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </a>
            <a class="nav-link <?= $current_page === 'elpt-registration' ? 'active' : '' ?>" href="elpt-registration.php">
                <i class="bi bi-calendar-plus me-2"></i>Daftar ELPT
            </a>
            <a class="nav-link <?= $current_page === 'test-results' ? 'active' : '' ?>" href="test-results.php">
                <i class="bi bi-file-earmark-text me-2"></i>Hasil Tes
            </a>
            <a class="nav-link <?= $current_page === 'course' ? 'active' : '' ?>" href="course.php">
                <i class="bi bi-book me-2"></i>Kursus
            </a>
            <hr class="my-3" style="border-color: rgba(255,255,255,0.3);">
            <a class="nav-link text-danger" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
            </a>
        </nav>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Logout
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="mb-3">
                    <i class="bi bi-question-circle text-warning" style="font-size: 4rem;"></i>
                </div>
                <h6 class="mb-3">Yakin ingin keluar dari sistem?</h6>
                <p class="text-muted mb-0">Anda akan dikembalikan ke halaman landing.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Batal
                </button>
                <a href="../logout.php" class="btn btn-danger px-4">
                    <i class="bi bi-box-arrow-right me-2"></i>Ya, Logout
                </a>
            </div>
        </div>
    </div>
</div>